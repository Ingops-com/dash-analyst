<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ProgramController;


Route::redirect('/', '/login');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', fn() => Inertia::render('dashboard'))->name('dashboard');
    Route::get('/lista-empresas', fn() => Inertia::render('Companies'))->name('companies');
    Route::get('/programa/{id}/generate-pdf', [ProgramController::class, 'generatePdf'])->name('programs.generatePdf');
    // Nueva ruta para la vista de un programa específico
    Route::get('/programa/{id}', fn() => Inertia::render('ProgramView'))->name('program.view');
    // La corrección es cambiar GET por POST:
    Route::post('/programa/generate-pdf', [App\Http\Controllers\ProgramController::class, 'generatePdf'])->name('programs.generatePdf');
});
// En routes/web.php
Route::get('/check-zip', function () {
    phpinfo();
});


require __DIR__ . '/auth.php';
require __DIR__ . '/settings.php';
