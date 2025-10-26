<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;

Route::redirect('/', '/login');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/lista-empresas', [CompanyController::class, 'index'])->name('companies');
    Route::get('/programa/{id}', [ProgramController::class, 'show'])->name('program.view');
    Route::post('/programa/{id}/generate-pdf', [ProgramController::class, 'generatePdf'])->name('programs.generatePdf');
});
// En routes/web.php
Route::get('/check-zip', function () {
    phpinfo();
});


require __DIR__ . '/auth.php';
require __DIR__ . '/settings.php';
