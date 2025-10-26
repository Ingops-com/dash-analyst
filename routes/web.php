<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\ProgramListController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MasterListController;
use App\Http\Controllers\UserDocumentsController;

Route::redirect('/', '/login');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/lista-empresas', [CompanyController::class, 'index'])->name('companies');
    Route::get('/programas', [ProgramListController::class, 'index'])->name('programs.index');
    Route::get('/programa/{id}', [ProgramController::class, 'show'])->name('program.view');
    Route::post('/programa/{id}/generate-pdf', [ProgramController::class, 'generatePdf'])->name('programs.generatePdf');
    
    // Admin routes
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/lista-usuarios', [UserController::class, 'index'])->name('users.list');
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
