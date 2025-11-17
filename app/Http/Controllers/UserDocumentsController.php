<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class UserDocumentsController extends Controller
{
    /**
     * Vista de administrador: empresas, sus programas asignados y documento generado.
     */
    public function index(Request $request)
    {
        // Cargar empresas habilitadas con campos básicos
        $companies = Company::orderBy('nombre')
            ->get(['id','nombre','correo','nit_empresa','logo_izquierdo']);

        $disk = Storage::disk('public');

        $data = [];
        foreach ($companies as $company) {
            // Programas asignados a la empresa (únicos por programa) evitando seleccionar columnas del pivot
            $programs = Program::select('programs.id', 'programs.nombre', 'programs.codigo')
                ->join('company_program_config as cpc', 'programs.id', '=', 'cpc.program_id')
                ->where('cpc.company_id', $company->id)
                ->distinct()
                ->orderBy('programs.nombre')
                ->get();

            $programItems = [];
            foreach ($programs as $program) {
                $baseRoot = 'company-documents/company_' . $company->id . '/program_' . $program->id;
                $docxRel = $baseRoot . '/current.docx';
                $pdfRel = $baseRoot . '/current.pdf';
                $hasDocx = $disk->exists($docxRel);
                $hasPdf = $disk->exists($pdfRel);

                $programItems[] = [
                    'id' => $program->id,
                    'nombre' => $program->nombre,
                    'codigo' => $program->codigo,
                    'has_docx' => $hasDocx,
                    'has_pdf' => $hasPdf,
                    'docx_url' => $hasDocx ? route('public.storage', ['path' => $docxRel]) : null,
                    'pdf_url' => $hasPdf ? route('public.storage', ['path' => $pdfRel]) : null,
                ];
            }

            $data[] = [
                'company' => [
                    'id' => $company->id,
                    'nombre' => $company->nombre,
                    'correo' => $company->correo,
                    'nit_empresa' => $company->nit_empresa,
                    'logo' => $company->logo_izquierdo ? route('public.storage', ['path' => $company->logo_izquierdo]) : null,
                ],
                'programs' => $programItems,
            ];
        }

        return Inertia::render('UserDocuments', [
            'items' => $data,
        ]);
    }
}