<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Models\Company;
use App\Models\Program;
use App\Models\Annex;
// Usamos la tabla pivote existente para empresa-programa-anexo

class MasterListController extends Controller
{
    public function index()
    {
        // Empresas: mapear nit_empresa como id_empresa para el filtro actual del front
        $companies = Company::select('id', 'nombre', DB::raw('nit_empresa as id_empresa'))
            ->orderBy('nombre')
            ->get();

        // Programas base
        $programs = Program::select('id', 'nombre', 'codigo', 'tipo')
            ->orderBy('nombre')
            ->get();

        // Anexos aplanados con programId desde la tabla pivote program_annexes
        $annexes = DB::table('program_annexes')
            ->join('annexes', 'annexes.id', '=', 'program_annexes.annex_id')
            ->select(
                'annexes.id',
                'annexes.nombre',
                'annexes.codigo_anexo',
                DB::raw('program_annexes.program_id as programId')
            )
            ->orderBy('program_annexes.program_id')
            ->orderBy('annexes.id')
            ->get();

        // Configuración guardada: construir { companyId: { programId: [annexId, ...] } }
        // Fuente de verdad: tabla pivote existente 'company_program_config'
        $configs = DB::table('company_program_config')
            ->select('company_id', 'program_id', 'annex_id')
            ->get();

        $masterConfig = [];
        foreach ($configs as $row) {
            $cid = (string)$row->company_id;
            $pid = (string)$row->program_id;
            if (!isset($masterConfig[$cid])) $masterConfig[$cid] = [];
            if (!isset($masterConfig[$cid][$pid])) $masterConfig[$cid][$pid] = [];
            $masterConfig[$cid][$pid][] = (int)$row->annex_id;
        }

        return Inertia::render('MasterList', [
            'companies' => $companies,
            'programs' => $programs,
            'annexes' => $annexes,
            'masterConfig' => (object)$masterConfig,
        ]);
    }

    public function saveConfig(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'config' => 'required|array', // { programId: [annexId, ...] }
        ]);

        $companyId = (int)$data['company_id'];
        $config = $data['config'];

        DB::transaction(function () use ($companyId, $config) {
            // Limpiar configuración previa de la empresa en la tabla pivote
            DB::table('company_program_config')->where('company_id', $companyId)->delete();

            $rows = [];
            foreach ($config as $programId => $annexIds) {
                if (!is_array($annexIds)) continue;
                foreach ($annexIds as $annexId) {
                    $rows[] = [
                        'company_id' => $companyId,
                        'program_id' => (int)$programId,
                        'annex_id' => (int)$annexId,
                    ];
                }
            }
            if (count($rows)) {
                DB::table('company_program_config')->insert($rows);
            }
        });

        return back()->with('success', 'Configuración guardada');
    }
}