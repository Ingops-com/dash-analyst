<?php

namespace App\Http\Controllers;

use App\Models\Program;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProgramListController extends Controller 
{
    protected function getProgramColor($tipo)
    {
        return match(strtolower($tipo ?? '')) {
            'iso 22000' => 'bg-blue-500',
            'psb' => 'bg-green-500',
            'invima' => 'bg-red-500',
            default => 'bg-gray-500',
        };
    }
    public function index()
    {
        // Cargar programas con los anexos (eager loading)
        $programs = Program::with(['annexes'])->get()->map(function($program) {
            return [
                'id' => $program->id,
                'nombre' => $program->nombre,
                'version' => $program->version ?? '1.0',
                'codigo' => $program->codigo ?? 'P-' . str_pad($program->id, 3, '0', STR_PAD_LEFT),
                'fecha' => optional($program->created_at)->format('Y-m-d'),
                'tipo' => $program->tipo ?? 'General',
                'color' => $this->getProgramColor($program->tipo),
                'annexes' => $program->annexes->map(function($annex) {
                    return [
                        'id' => $annex->id,
                        'nombre' => $annex->nombre,
                        'codigo_anexo' => $annex->codigo_anexo ?? 'A-' . str_pad($annex->id, 3, '0', STR_PAD_LEFT),
                        'tipo' => $annex->tipo ?? 'FORMATO',
                        'consecutivo' => $annex->id, // o un campo específico si existe
                        'programId' => $annex->pivot->program_id
                    ];
                })->values(),
            ];
        });

        return Inertia::render('Programs', [
            'programs' => $programs
        ]);
    }
}