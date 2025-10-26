<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Models\Company;
use App\Models\Program;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        // Traer empresas
        $companies = Company::select([
            'id',
            'nombre',
            'nit_empresa',
            'representante_legal',
            'fecha_inicio',
            'fecha_verificacion',
            'version',
            'telefono',
            'direccion',
            'actividades',
            'logo_izquierdo',
            'logo_derecho',
            'logo_pie_de_pagina',
            'correo',
            'habilitado'
        ])->get()->map(function ($c) {
            return [
                'id' => $c->id,
                'name' => $c->nombre,
                'nit' => $c->nit_empresa,
                'representative' => $c->representante_legal,
                'startDate' => $c->fecha_inicio ? $c->fecha_inicio->format('Y-m-d') : null,
                'endDate' => $c->fecha_verificacion ? $c->fecha_verificacion->format('Y-m-d') : null,
                'version' => $c->version,
                'phone' => $c->telefono,
                'address' => $c->direccion,
                'activities' => $c->actividades,
                'logos' => [$c->logo_derecho, $c->logo_izquierdo, $c->logo_pie_de_pagina],
                'email' => $c->correo,
                'status' => $c->habilitado ? 'activa' : 'inactiva',
                // programs se completará después
                'programs' => []
            ];
        })->toArray();

        // Para cada empresa obtener programas asignados y calcular progreso
        foreach ($companies as &$company) {
            $companyId = $company['id'];
            // Obtener los programas asignados a la empresa desde company_program_config
            $programRows = DB::table('company_program_config')
                ->where('company_id', $companyId)
                ->select('program_id')
                ->distinct()
                ->get()
                ->pluck('program_id')
                ->toArray();

            $programs = [];
            foreach ($programRows as $progId) {
                $prog = Program::find($progId);
                if (!$prog) continue;

                // contar anexos configurados para esta empresa y programa
                $annexIds = DB::table('company_program_config')
                    ->where('company_id', $companyId)
                    ->where('program_id', $progId)
                    ->pluck('annex_id')
                    ->toArray();

                $totalAnnexes = count($annexIds);
                $completedAnnexes = 0;
                if ($totalAnnexes > 0) {
                    $completedAnnexes = DB::table('company_annex_submissions')
                        ->where('company_id', $companyId)
                        ->whereIn('annex_id', $annexIds)
                        ->where('status', 'Aprobado')
                        ->count();
                }

                $progress = $totalAnnexes ? round(($completedAnnexes / $totalAnnexes) * 100) : 0;

                $programs[] = [
                    'id' => $prog->id,
                    'code' => $prog->codigo,
                    'name' => $prog->nombre,
                    'progress' => (int) $progress,
                ];
            }

            $company['programs'] = $programs;
        }

        return Inertia::render('Companies', [
            'companies' => $companies
        ]);
    }
}
