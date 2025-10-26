<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\MasterListController;
use App\Http\Controllers\UserDocumentsController;
use App\Http\Controllers\ProgramController;
use Illuminate\Support\Facades\Route;

// Admin routes - todos requieren auth y rol=admin
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/programas', [ProgramController::class, 'index'])->name('programs.index');
    Route::get('/lista-usuarios', [UserController::class, 'index'])->name('users.list');
    Route::get('/listado-maestro', [MasterListController::class, 'index'])->name('master.list');
    Route::get('/documentos-empresas', [UserDocumentsController::class, 'index'])->name('user.documents');
});