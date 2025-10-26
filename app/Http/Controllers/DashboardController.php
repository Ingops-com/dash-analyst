<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Program;
use App\Models\Annex;
use App\Models\CompanyAnnexSubmission;
use App\Models\CompanyPoeRecord;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // Obtener empresas con sus logos
        // Traer campos originales (sin alias) para mantener los castings de Eloquent
        $companies = Company::select([
            'id',
            'nombre',
            'habilitado',
            'created_at',
            'logo_izquierdo',
            'logo_derecho',
            'logo_pie_de_pagina',
        ])
        ->get()
        ->map(function($company) {
            return [
                'id' => $company->id,
                'nombre' => $company->nombre,
                // habilitado viene como 0/1, mantener el mapeo a 'activa'/'inactiva'
                'status' => $company->habilitado ? 'activa' : 'inactiva',
                'logos' => [
                    $company->logo_derecho,
                    $company->logo_izquierdo,
                    $company->logo_pie_de_pagina,
                ],
                // created_at es instancia Carbon cuando no se renombra en el select
                'fechaRegistro' => $company->created_at ? $company->created_at->format('Y-m-d') : null,
            ];
        });

        // Obtener programas con sus anexos y POEs
        // Eager-load companies so we can provide a companyId for the frontend
        $programs = Program::with(['annexes', 'poes', 'companies'])
            ->select('id', 'nombre')
            ->get()
            ->map(function($program) {
                return [
                    'id' => $program->id,
                    'name' => $program->nombre,
                    // tomamos la primera empresa vinculada (si existe) para mantener compatibilidad con la UI
                    'companyId' => optional($program->companies->first())->id,
                    'annexes' => $program->annexes->map(function($annex) {
                        return [
                            'id' => $annex->id,
                            'name' => $annex->nombre,
                            'type' => $this->getAnnexType($annex->tipo),
                            'files' => CompanyAnnexSubmission::where('annex_id', $annex->id)
                                ->where('status', 'Aprobado')
                                ->get()
                                ->map(fn($submission) => ['name' => basename($submission->file_name)])
                                ->toArray(),
                        ];
                    })->toArray(),
                    // Mapear los POEs del programa (la fecha la tomamos del registro del POE)
                    'poes' => $program->poes->map(function($poe) {
                        return [
                            'id' => $poe->id,
                            'date' => $poe->created_at ? $poe->created_at->format('Y-m-d') : null,
                        ];
                    })->toArray(),
                ];
            });

        return Inertia::render('dashboard', [
            'companies' => $companies,
            'programs' => $programs
        ]);
    }

    private function getAnnexType($tipo)
    {
        return match ($tipo) {
            'ISO 22000' => 'PDF',
            'PSB' => 'XLSX',
            'Invima' => 'FORMATO',
            default => 'PDF'
        };
    }
}