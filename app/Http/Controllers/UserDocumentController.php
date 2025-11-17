<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Program;
use App\Models\Annex;
use App\Models\CompanyAnnexSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class UserDocumentController extends Controller
{
    /**
     * Lista programas que tienen documento generado para la empresa seleccionada.
     */
    public function index(Request $request, Company $company)
    {
        $disk = Storage::disk('public');
        $programs = Program::orderBy('nombre')->get();

        $items = [];
        foreach ($programs as $program) {
            $baseRoot = 'company-documents/company_' . $company->id . '/program_' . $program->id;
            $pdfRel = $baseRoot . '/current.pdf';
            $docxRel = $baseRoot . '/current.docx';
            $hasPdf = $disk->exists($pdfRel);
            $hasDocx = $disk->exists($docxRel);
            $items[] = [
                'program' => [
                    'id' => $program->id,
                    'nombre' => $program->nombre,
                    'codigo' => $program->codigo,
                ],
                'has_pdf' => $hasPdf,
                'has_docx' => $hasDocx,
                'pdf_url' => $hasPdf ? route('public.storage', ['path' => $pdfRel]) : null,
                'docx_url' => $hasDocx ? route('public.storage', ['path' => $docxRel]) : null,
            ];
        }

        return Inertia::render('user/documents/index', [
            'company' => [
                'id' => $company->id,
                'nombre' => $company->nombre,
            ],
            'items' => $items,
        ]);
    }

    /**
     * Muestra detalle de un programa: anexos y preview PDF.
     */
    public function show(Request $request, Company $company, Program $program)
    {
        $disk = Storage::disk('public');
        $baseRoot = 'company-documents/company_' . $company->id . '/program_' . $program->id;
        $pdfRel = $baseRoot . '/current.pdf';
        $docxRel = $baseRoot . '/current.docx';
        $hasPdf = $disk->exists($pdfRel);
        $hasDocx = $disk->exists($docxRel);

        // Obtener anexos del programa y submissions de la empresa
    $annexIds = DB::table('program_annexes')->where('program_id', $program->id)->pluck('annex_id')->toArray();
        $annexes = Annex::whereIn('id', $annexIds)->orderBy('id')->get(['id','nombre','codigo_anexo','content_type']);

        $submissions = CompanyAnnexSubmission::where('company_id', $company->id)
            ->where('program_id', $program->id)
            ->orderByDesc('updated_at')
            ->get(['id','annex_id','file_name','file_path','mime_type','content_text','updated_at']);

        return Inertia::render('user/documents/show', [
            'company' => [
                'id' => $company->id,
                'nombre' => $company->nombre,
            ],
            'program' => [
                'id' => $program->id,
                'nombre' => $program->nombre,
                'codigo' => $program->codigo,
            ],
            'preview' => [
                'has_pdf' => $hasPdf,
                'has_docx' => $hasDocx,
                'pdf_url' => $hasPdf ? route('public.storage', ['path' => $pdfRel]) : null,
                'docx_url' => $hasDocx ? route('public.storage', ['path' => $docxRel]) : null,
            ],
            'annexes' => $annexes,
            'submissions' => $submissions,
        ]);
    }
}
