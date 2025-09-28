<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::redirect('/', '/login');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', fn () => Inertia::render('dashboard'))->name('dashboard');
    Route::get('/lista-empresas', fn () => Inertia::render('Companies'))->name('companies');
    
    // Nueva ruta para la vista de un programa específico
    Route::get('/programa/{id}', fn () => Inertia::render('ProgramView'))->name('program.view');
});


require __DIR__.'/auth.php';
require __DIR__.'/settings.php';