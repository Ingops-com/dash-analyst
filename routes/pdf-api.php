<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PdfConversionController;

/*
|--------------------------------------------------------------------------
| API Routes - PDF Conversion Service
|--------------------------------------------------------------------------
|
| Estas rutas replican la funcionalidad de la API Python de conversión
| Office a PDF usando LibreOffice localmente.
|
*/

// Health check endpoint
Route::get('/health', [PdfConversionController::class, 'health']);

// Conversión de archivos Office a PDF
Route::post('/convert', [PdfConversionController::class, 'convert']);
