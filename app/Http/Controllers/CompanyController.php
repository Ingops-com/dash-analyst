<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
                // Convert stored paths to public URLs so frontend can render images directly
                'logo_izquierdo' => $c->logo_izquierdo ? Storage::url($c->logo_izquierdo) : null,
                'logo_derecho' => $c->logo_derecho ? Storage::url($c->logo_derecho) : null,
                'logo_pie_de_pagina' => $c->logo_pie_de_pagina ? Storage::url($c->logo_pie_de_pagina) : null,
                'logos' => [], // Para compatibilidad (puede usarse en el futuro)
                'email' => $c->correo,
                'status' => $c->habilitado ? 'activa' : 'inactiva',
                // programs se completará después
                'programs' => []
            ];
        })->toArray();

        // Marcar empresas asignadas al usuario y filtrar si no es admin
        $user = $request->user();
        $role = strtolower((string)($user->rol ?? ''));
        $isadmin = in_array($role, ['super-admin']);
        $assignedCompanyIds = DB::table('company_user')
            ->where('user_id', $user->id)
            ->pluck('company_id')
            ->toArray();

        foreach ($companies as &$co) {
            $co['assigned'] = in_array($co['id'], $assignedCompanyIds);
        }
        unset($co);

        if (! $isadmin) {
            $companies = array_values(array_filter($companies, fn ($co) => $co['assigned']));
        }

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

    public function update(Request $request, $id)
    {
        $company = Company::findOrFail($id);

        // Validar datos
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'nit' => 'sometimes|string|max:255',
            'representative' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:255',
            'address' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255',
            'activities' => 'sometimes|string',
            'version' => 'sometimes|string|max:255',
            'startDate' => 'sometimes|date',
            'endDate' => 'sometimes|date',
            'status' => 'sometimes|in:activa,inactiva',
            'website' => 'sometimes|nullable|url|max:255',
            'altPhone' => 'sometimes|nullable|string|max:255',
            'city' => 'sometimes|nullable|string|max:255',
            'country' => 'sometimes|nullable|string|max:255',
            'industry' => 'sometimes|nullable|string|max:255',
            'employeesRange' => 'sometimes|nullable|string|max:255',
            'notes' => 'sometimes|nullable|string',
            'revisado_por' => 'sometimes|nullable|string|max:255',
            'aprobado_por' => 'sometimes|nullable|string|max:255',
            'logos.*' => 'sometimes|image|mimes:jpeg,jpg,png|max:5120', // 5MB max
        ]);

        // Mapear campos del frontend al backend
        $updateData = [];
        if (isset($validated['name'])) $updateData['nombre'] = $validated['name'];
        if (isset($validated['nit'])) $updateData['nit_empresa'] = $validated['nit'];
        if (isset($validated['representative'])) $updateData['representante_legal'] = $validated['representative'];
        if (isset($validated['phone'])) $updateData['telefono'] = $validated['phone'];
        if (isset($validated['address'])) $updateData['direccion'] = $validated['address'];
        if (isset($validated['email'])) $updateData['correo'] = $validated['email'];
        if (isset($validated['activities'])) $updateData['actividades'] = $validated['activities'];
        if (isset($validated['version'])) $updateData['version'] = $validated['version'];
        if (isset($validated['startDate'])) $updateData['fecha_inicio'] = $validated['startDate'];
        if (isset($validated['endDate'])) $updateData['fecha_verificacion'] = $validated['endDate'];
        if (isset($validated['status'])) $updateData['habilitado'] = $validated['status'] === 'activa';
        if (isset($validated['revisado_por'])) $updateData['revisado_por'] = $validated['revisado_por'];
        if (isset($validated['aprobado_por'])) $updateData['aprobado_por'] = $validated['aprobado_por'];

        // Manejar subida de logos
        if ($request->hasFile('logos')) {
            $logos = $request->file('logos');
            
            // Guardar nuevos logos (solo reemplazar los que se envían)
            if (isset($logos[0])) {
                // Eliminar logo izquierdo anterior si existe
                if ($company->logo_izquierdo) Storage::disk('public')->delete($company->logo_izquierdo);
                $path = $logos[0]->store('logos', 'public');
                $updateData['logo_izquierdo'] = $path;
            }
            if (isset($logos[1])) {
                // Eliminar logo derecho anterior si existe
                if ($company->logo_derecho) Storage::disk('public')->delete($company->logo_derecho);
                $path = $logos[1]->store('logos', 'public');
                $updateData['logo_derecho'] = $path;
            }
            if (isset($logos[2])) {
                // Eliminar logo pie de página anterior si existe
                if ($company->logo_pie_de_pagina) Storage::disk('public')->delete($company->logo_pie_de_pagina);
                $path = $logos[2]->store('logos', 'public');
                $updateData['logo_pie_de_pagina'] = $path;
            }
        }

        $company->update($updateData);

        return redirect()->back()->with('success', 'Empresa actualizada correctamente');
    }
}
