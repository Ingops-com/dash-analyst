<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\ProgramListController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MasterListController;
use App\Http\Controllers\UserDocumentsController;
use App\Http\Controllers\UserDocumentController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\ChatController;

Route::redirect('/', '/login');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Rutas del módulo de Recordatorios/Calendario
    Route::resource('reminders', ReminderController::class);
    Route::get('/reminders/calendar/events', [ReminderController::class, 'calendar'])->name('reminders.calendar');
    Route::get('/reminders-notifications', [ReminderController::class, 'notifications'])->name('reminders.notifications');
    Route::post('/reminders-notifications/{id}/read', [ReminderController::class, 'markAsRead'])->name('reminders.notifications.read');
    Route::post('/reminders-notifications/read-all', [ReminderController::class, 'markAllAsRead'])->name('reminders.notifications.readAll');
    
    Route::get('/lista-empresas', [CompanyController::class, 'index'])->name('companies');
    // Allow both PUT and POST for update to avoid method spoofing issues with FormData in some environments
    Route::match(['put', 'post'], '/companies/{id}', [CompanyController::class, 'update'])->name('companies.update');
    Route::get('/programas', [ProgramListController::class, 'index'])->name('programs.index');
    Route::post('/programas', [ProgramListController::class, 'store'])->name('programs.store');
    Route::put('/programas/{id}', [ProgramListController::class, 'update'])->name('programs.update');
    Route::post('/anexos', [ProgramListController::class, 'storeAnnex'])->name('annexes.store');
    Route::put('/anexos/{id}', [ProgramListController::class, 'updateAnnex'])->name('annexes.update');
    Route::get('/programa/{id}', [ProgramController::class, 'show'])->name('program.view');
    Route::post('/programa/{id}/generate-pdf', [ProgramController::class, 'generatePdf'])->name('programs.generatePdf');
    Route::post('/programa/{programId}/annex/{annexId}/upload', [ProgramController::class, 'uploadAnnex'])->name('program.annex.upload');
    Route::delete('/programa/{programId}/annex/{annexId}/clear', [ProgramController::class, 'clearAnnexFiles'])->name('program.annex.clear');
    Route::delete('/programa/{programId}/annex/{annexId}/file/{submissionId}', [ProgramController::class, 'deleteAnnexFile'])->name('program.annex.file.delete');
    
    // Servir archivos del disco público con restricción de descarga
    Route::get('/public-storage/{path}', function (string $path) {
        // Seguridad básica: evitar path traversal
        if (str_contains($path, '..')) abort(400, 'Ruta inválida');
        if (!Storage::disk('public')->exists($path)) abort(404);
        
        $absolute = Storage::disk('public')->path($path);
        
        // Verificar si es un documento de empresa (company-documents)
        if (str_starts_with($path, 'company-documents/')) {
            $user = Auth::user();
            if (!$user) {
                abort(401, 'No autorizado');
            }
            
            $userRole = strtolower($user->rol ?? '');
            $isAdminOrSuperAdmin = ($userRole === 'admin' || $userRole === 'super-admin');
            
            // Si es PDF y el usuario NO es admin/super-admin, permitir visualización pero no descarga
            if (str_ends_with(strtolower($path), '.pdf') && !$isAdminOrSuperAdmin) {
                // Servir el PDF para visualización en iframe (sin header de descarga)
                return response()->file($absolute, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
                    // Evitar cachear PDFs viejos en algunos navegadores/iframes
                    'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                    'Pragma' => 'no-cache',
                ]);
            }
            
            // Para DOCX u otros archivos, solo admin/super-admin pueden acceder
            if (!$isAdminOrSuperAdmin) {
                abort(403, 'No tienes permisos para descargar este tipo de documento');
            }
        }
        
        // Devolver el archivo con headers adecuados
        return response()->file($absolute);
    })->where('path', '.*')->name('public.storage');
    
    // admin routes
    Route::middleware(['role:admin,super-admin'])->group(function () {
        Route::get('/lista-usuarios', [UserController::class, 'index'])->name('users.list');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
        Route::put('/users/{id}/permissions', [UserController::class, 'updateCompanyPermissions'])->name('users.permissions.update');
        Route::put('/users/{id}/companies', [UserController::class, 'updateCompanies'])->name('users.companies.update');
        Route::put('/users/{id}/status', [UserController::class, 'updateStatus'])->name('users.status.update');
        Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::get('/listado-maestro', [MasterListController::class, 'index'])->name('master.list');
        Route::post('/listado-maestro/config', [MasterListController::class, 'saveConfig'])->name('master.config');
        Route::get('/documentos-empresas', [UserDocumentsController::class, 'index'])->name('user.documents');
    });

    // Documentos de usuario: listado y vista por programa (con preview PDF)
    Route::get('/user/companies/{company}/documents', [UserDocumentController::class, 'index'])->name('user.company.documents');
    Route::get('/user/companies/{company}/programs/{program}/document', [UserDocumentController::class, 'show'])->name('user.company.program.document');

    // Conversaciones internas
    Route::get('/chats', [ChatController::class, 'index'])->name('chats.index');
    Route::post('/chats', [ChatController::class, 'store'])->name('chats.store');
    Route::get('/chats/{conversation}', [ChatController::class, 'show'])->name('chats.show');
    Route::post('/chats/{conversation}/messages', [ChatController::class, 'storeMessage'])->name('chats.messages.store');

    // Enlace simple para usuarios finales: resuelve su primera empresa asignada
    Route::get('/mis-documentos', function () {
        $user = Auth::user();
        if (!$user) abort(403);
        $companyId = DB::table('company_user')->where('user_id', $user->id)->orderBy('company_id')->value('company_id');
        if (!$companyId) {
            // Si no tiene empresa, enviarlo a dashboard con aviso simple
            return redirect()->route('dashboard');
        }
        return redirect()->route('user.company.documents', ['company' => $companyId]);
    })->middleware(['role:usuario,user'])->name('user.my.documents');
});
// En routes/web.php
Route::get('/check-zip', function () {
    phpinfo();
});


require __DIR__ . '/auth.php';
require __DIR__ . '/settings.php';
