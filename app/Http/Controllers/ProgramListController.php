<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\Annex;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'version' => 'nullable|string|max:255',
            'codigo' => 'required|string|max:255|unique:programs,codigo',
            'fecha' => 'nullable|date',
            'tipo' => 'required|in:ISO 22000,PSB,Invima',
            'template_path' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:1000',
        ]);

        $program = Program::create($validated);

        return redirect()->route('programs.index')->with('success', 'Programa creado exitosamente');
    }

    public function update(Request $request, $id)
    {
        $program = Program::findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'version' => 'nullable|string|max:255',
            'codigo' => 'required|string|max:255|unique:programs,codigo,' . $id,
            'fecha' => 'nullable|date',
            'tipo' => 'required|in:ISO 22000,PSB,Invima',
            'template_path' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:1000',
        ]);

        $program->update($validated);

        return redirect()->route('programs.index')->with('success', 'Programa actualizado exitosamente');
    }

    public function storeAnnex(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'codigo_anexo' => 'required|string|max:255|unique:annexes,codigo_anexo',
            // Enforce enum values to match DB schema and avoid truncation
            'tipo' => 'required|in:ISO 22000,PSB,Invima',
            'placeholder' => 'nullable|string|max:255',
            'programIds' => 'required|array|min:1',
            'programIds.*' => 'exists:programs,id',
        ]);

        // Create the annex
        $annex = Annex::create([
            'nombre' => $validated['nombre'],
            'codigo_anexo' => $validated['codigo_anexo'],
            'placeholder' => $validated['placeholder'] ?? null,
            'tipo' => $validated['tipo'],
            // Valid enum values: 'En revisión', 'Aprobado', 'Obsoleto'
            'status' => 'En revisión',
        ]);

        // Attach to programs using direct DB insert (pivot table has no timestamps or id)
        $pivotData = array_map(function($programId) use ($annex) {
            return [
                'program_id' => $programId,
                'annex_id' => $annex->id,
            ];
        }, $validated['programIds']);

        DB::table('program_annexes')->insert($pivotData);

        return redirect()->route('programs.index')->with('success', 'Anexo creado exitosamente');
    }

    public function updateAnnex(Request $request, $id)
    {
        $annex = Annex::findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'codigo_anexo' => 'required|string|max:255|unique:annexes,codigo_anexo,' . $id,
            'tipo' => 'required|in:ISO 22000,PSB,Invima',
            'placeholder' => 'nullable|string|max:255',
            'programIds' => 'required|array|min:1',
            'programIds.*' => 'exists:programs,id',
        ]);

        // Update the annex
        $annex->update([
            'nombre' => $validated['nombre'],
            'codigo_anexo' => $validated['codigo_anexo'],
            'placeholder' => $validated['placeholder'] ?? null,
            'tipo' => $validated['tipo'],
        ]);

        // Update program relationships
        // First, delete existing relationships
        DB::table('program_annexes')->where('annex_id', $annex->id)->delete();

        // Then, insert new relationships
        $pivotData = array_map(function($programId) use ($annex) {
            return [
                'program_id' => $programId,
                'annex_id' => $annex->id,
            ];
        }, $validated['programIds']);

        DB::table('program_annexes')->insert($pivotData);

        return redirect()->route('programs.index')->with('success', 'Anexo actualizado exitosamente');
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
                'template_path' => $program->template_path,
                'description' => $program->description,
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