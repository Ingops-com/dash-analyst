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

Route::redirect('/', '/login');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
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
    // Servir archivos del disco público sin depender del symlink (útil en Windows/dev server)
    Route::get('/public-storage/{path}', function (string $path) {
        // Seguridad básica: evitar path traversal
        if (str_contains($path, '..')) abort(400, 'Ruta inválida');
        if (!Storage::disk('public')->exists($path)) abort(404);
        $absolute = Storage::disk('public')->path($path);
        // Devolver el archivo con headers adecuados
        return response()->file($absolute);
    })->where('path', '.*')->name('public.storage');
    
    // Admin routes
    Route::middleware(['role:admin,super-admin'])->group(function () {
        Route::get('/lista-usuarios', [UserController::class, 'index'])->name('users.list');
        Route::put('/users/{id}/companies', [UserController::class, 'updateCompanies'])->name('users.companies.update');
        Route::get('/listado-maestro', [MasterListController::class, 'index'])->name('master.list');
        Route::get('/documentos-empresas', [UserDocumentsController::class, 'index'])->name('user.documents');
    });
});
// En routes/web.php
Route::get('/check-zip', function () {
    phpinfo();
});


require __DIR__ . '/auth.php';
require __DIR__ . '/settings.php';
