<?php

namespace App\Services;

use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Shared\Converter;
use Illuminate\Support\Facades\Storage;

class DocumentHeaderService
{
    /**
     * Crear el header personalizado para un documento basado en la imagen proporcionada
     * 
     * Header estructura:
     * ┌────────────────────────────────────────────────────────┐
     * │ [Logo Izq]  TÍTULO DEL DOCUMENTO  [Logo Der] INV 002  │
     * ├────────────────────────────────────────────────────────┤
     * │ Revisado por: XXX  │  Dirección: XXX                   │
     * │ Aprobado por: XXX  │  Versión XX  ene-24  Código: XXX │
     * └────────────────────────────────────────────────────────┘
     * 
     * @param Section $section Sección del documento donde se agregará el header
     * @param array $company Datos de la empresa
     * @param array $program Datos del programa
     * @return void
     */
    public function createCustomHeader(Section $section, array $company, array $program)
    {
        // Crear header
        $header = $section->addHeader();
        
        // ===== FILA 1: Logos y Título =====
        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80,
            'alignment' => Jc::CENTER,
            'width' => 100 * 50, // 100% width
        ];
        
        $table = $header->addTable($tableStyle);
        $table->addRow(Converter::cmToTwip(3)); // Altura de fila: 3cm
        
        // Celda 1: Logo izquierdo
        $cellLogoLeft = $table->addCell(Converter::cmToTwip(3), ['valign' => 'center']);
        $this->addLogo($cellLogoLeft, $company['logo_izquierdo'] ?? null, 2.5);
        
        // Celda 2: Título del documento
        $cellTitle = $table->addCell(Converter::cmToTwip(11), ['valign' => 'center']);
        $textRun = $cellTitle->addTextRun(['alignment' => Jc::CENTER]);
        $textRun->addText(
            strtoupper($program['nombre'] ?? 'DOCUMENTO'),
            ['bold' => true, 'size' => 14, 'name' => 'Arial']
        );
        
        // Celda 3: Logo derecho + Código
        $cellLogoRight = $table->addCell(Converter::cmToTwip(3.5), ['valign' => 'center']);
        $this->addLogo($cellLogoRight, $company['logo_derecho'] ?? null, 2);
        // Agregar código del programa
        $cellLogoRight->addTextBreak();
        $textRun = $cellLogoRight->addTextRun(['alignment' => Jc::CENTER]);
        $textRun->addText(
            $program['codigo'] ?? 'INV 002',
            ['bold' => true, 'size' => 10, 'name' => 'Arial']
        );
        
        // ===== FILA 2: Información detallada =====
        $table->addRow(Converter::cmToTwip(2.5)); // Altura de fila: 2.5cm
        
        // Celda izquierda: Revisado por y Aprobado por
        $cellLeft = $table->addCell(Converter::cmToTwip(8.5), [
            'valign' => 'top',
            'gridSpan' => 2
        ]);
        
        // Revisado por
        $textRun = $cellLeft->addTextRun(['alignment' => Jc::LEFT]);
        $textRun->addText('Revisado por: ', ['bold' => true, 'size' => 9, 'name' => 'Arial']);
        $textRun->addText(
            'ING. ' . ($company['revisado_por'] ?? $company['encargado_sgc'] ?? 'No especificado'),
            ['size' => 9, 'name' => 'Arial']
        );
        
        $cellLeft->addTextBreak();
        
        // Dirección
        $textRun = $cellLeft->addTextRun(['alignment' => Jc::LEFT]);
        $textRun->addText('Dirección del establecimiento ', ['bold' => true, 'size' => 9, 'name' => 'Arial']);
        $textRun->addText(
            $company['direccion'] ?? 'No especificada',
            ['size' => 9, 'name' => 'Arial']
        );
        
        // Celda derecha: Aprobado por, Versión y Código
        $cellRight = $table->addCell(Converter::cmToTwip(9), ['valign' => 'top']);
        
        // Aprobado por
        $textRun = $cellRight->addTextRun(['alignment' => Jc::LEFT]);
        $textRun->addText('Aprobado por:', ['bold' => true, 'size' => 9, 'name' => 'Arial']);
        $cellRight->addTextBreak();
        $textRun = $cellRight->addTextRun(['alignment' => Jc::CENTER]);
        $textRun->addText(
            'Ing.' . ($company['aprobado_por'] ?? $company['representante_legal'] ?? 'No especificado'),
            ['size' => 9, 'name' => 'Arial']
        );
        
        $cellRight->addTextBreak();
        
        // Versión y fecha
        $fecha = isset($company['fecha_inicio']) ? date('M-y', strtotime($company['fecha_inicio'])) : 'ene-24';
        $textRun = $cellRight->addTextRun(['alignment' => Jc::CENTER]);
        $textRun->addText(
            'Versión ' . ($company['version'] ?? '01') . '      ' . $fecha,
            ['size' => 9, 'name' => 'Arial']
        );

        $cellRight->addTextBreak();

        // Código
        $textRun = $cellRight->addTextRun(['alignment' => Jc::CENTER]);
        $textRun->addText(
            'Código: ' . ($program['codigo'] ?? 'PSB-SCIA-01'),
            ['size' => 9, 'name' => 'Arial']
        );
    }
    
    /**
     * Agregar logo a una celda
     * 
     * @param \PhpOffice\PhpWord\Element\Cell $cell
     * @param string|null $logoPath Ruta del logo en storage
     * @param float $widthCm Ancho en centímetros
     * @return void
     */
    private function addLogo($cell, ?string $logoPath, float $widthCm = 2.5)
    {
        if (!$logoPath) {
            // Si no hay logo, dejar espacio en blanco
            $cell->addText('', ['size' => 1]);
            return;
        }
        
        // Intentar obtener la ruta física del archivo
        try {
            // Si el path viene de la BD como 'logos/filename.png'
            if (Storage::disk('public')->exists($logoPath)) {
                $fullPath = Storage::disk('public')->path($logoPath);
            } else {
                // Fallback: intentar con la ruta directa
                $fullPath = storage_path('app/public/' . $logoPath);
            }
            
            if (file_exists($fullPath)) {
                $cell->addImage(
                    $fullPath,
                    [
                        'width' => Converter::cmToPixel($widthCm),
                        'height' => Converter::cmToPixel($widthCm * 0.8), // Ratio 1:0.8
                        'alignment' => Jc::CENTER,
                    ]
                );
            } else {
                // Si el archivo no existe, poner texto placeholder
                $cell->addText('[Logo]', ['size' => 8, 'color' => '999999']);
            }
        } catch (\Exception $e) {
            // En caso de error, agregar placeholder
            $cell->addText('[Logo]', ['size' => 8, 'color' => '999999']);
        }
    }
    
    /**
     * Agregar el logo del pie de página
     * 
     * @param Section $section
     * @param string|null $logoPath
     * @return void
     */
    public function addFooterLogo(Section $section, ?string $logoPath)
    {
        if (!$logoPath) {
            return;
        }
        
        $footer = $section->addFooter();
        
        try {
            if (Storage::disk('public')->exists($logoPath)) {
                $fullPath = Storage::disk('public')->path($logoPath);
            } else {
                $fullPath = storage_path('app/public/' . $logoPath);
            }
            
            if (file_exists($fullPath)) {
                $footer->addImage(
                    $fullPath,
                    [
                        'width' => Converter::cmToPixel(3),
                        'height' => Converter::cmToPixel(2),
                        'alignment' => Jc::CENTER,
                    ]
                );
            }
        } catch (\Exception $e) {
            // Silenciar errores en el footer
        }
    }
}
