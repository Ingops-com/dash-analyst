<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::redirect('/', '/login');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', fn () => Inertia::render('dashboard'))->name('dashboard');
    Route::get('/lista-usuarios', fn () => Inertia::render('UsersList'))->name('users.list');
    Route::get('/documentos-usuarios', fn () => Inertia::render('UserDocuments'))->name('users.documents');
    Route::get('/empresas', fn () => Inertia::render('Companies'))->name('companies');
    Route::get('/documentos-programas', fn () => Inertia::render('ProgramDocuments'))->name('programs.documents');
    Route::get('/listado-maestro', fn () => Inertia::render('MasterList'))->name('master.list');
    
    Route::resource('users', UserController::class);
    Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
});


require __DIR__.'/auth.php';
require __DIR__.'/settings.php';