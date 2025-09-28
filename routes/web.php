<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::redirect('/', '/login');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', fn () => Inertia::render('dashboard'))->name('dashboard');
    Route::get('/lista-empresas', fn () => Inertia::render('Companies'))->name('companies');
});


require __DIR__.'/auth.php';
require __DIR__.'/settings.php';