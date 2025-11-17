import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';

interface Company { id: number; nombre: string }
interface Program { id: number; nombre: string; codigo?: string }
interface Preview { has_pdf: boolean; has_docx: boolean; pdf_url?: string | null; docx_url?: string | null }
interface Annex { id: number; nombre: string; codigo_anexo?: string; content_type?: string }
interface Submission { id: number; annex_id: number; file_name?: string | null; file_path?: string | null; mime_type?: string | null; content_text?: string | null; updated_at?: string }

export default function UserDocumentShow({ company, program, preview, annexes, submissions }: { company: Company; program: Program; preview: Preview; annexes: Annex[]; submissions: Submission[] }) {
  const subsByAnnex = new Map<number, Submission[]>(
    Object.entries(
      submissions.reduce((acc: Record<number, Submission[]>, s) => {
        acc[s.annex_id] = acc[s.annex_id] || [];
        acc[s.annex_id].push(s);
        return acc;
      }, {})
    ).map(([k, v]) => [Number(k), v])
  );

  return (
    <AppLayout>
      <Head title={`Documento ${program.nombre} - ${company.nombre}`} />
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <div>
            <div className="text-sm text-muted-foreground">{program.codigo}</div>
            <h1 className="text-2xl font-semibold">{program.nombre}</h1>
            <div className="text-sm text-muted-foreground">Empresa: {company.nombre}</div>
          </div>
          <div className="flex gap-2">
            <Link href={`/user/companies/${company.id}/documents`}>
              <Button variant="outline">Volver</Button>
            </Link>
            {preview.docx_url && (
              <a href={preview.docx_url} target="_blank" rel="noreferrer">
                <Button variant="secondary">Descargar DOCX</Button>
              </a>
            )}
            {preview.pdf_url && (
              <a href={preview.pdf_url} target="_blank" rel="noreferrer">
                <Button>Abrir PDF</Button>
              </a>
            )}
          </div>
        </div>

        <div className="grid gap-6 lg:grid-cols-2">
          <Card className="p-4">
            <h2 className="text-lg font-medium mb-2">Anexos</h2>
            <Separator className="mb-4" />
            <div className="space-y-3">
              {annexes.map((a) => {
                const list = subsByAnnex.get(a.id) || [];
                const hasContent = list.length > 0;
                return (
                  <div key={a.id} className="flex items-start justify-between border rounded p-3">
                    <div>
                      <div className="font-medium">{a.nombre}</div>
                      <div className="text-xs text-muted-foreground">{a.codigo_anexo} · {a.content_type}</div>
                    </div>
                    <div className="text-sm">
                      {hasContent ? (
                        <span className="text-green-600">con contenido</span>
                      ) : (
                        <span className="text-amber-600">sin contenido</span>
                      )}
                    </div>
                  </div>
                );
              })}
            </div>
          </Card>

          <Card className="p-4">
            <h2 className="text-lg font-medium mb-2">Vista previa PDF</h2>
            <Separator className="mb-4" />
            {preview.has_pdf && preview.pdf_url ? (
              <iframe src={preview.pdf_url} className="w-full h-[75vh] border rounded" title="Vista previa del documento" />
            ) : (
              <div className="text-sm text-muted-foreground">Aún no hay un PDF generado para este programa.</div>
            )}
          </Card>
        </div>
      </div>
    </AppLayout>
  );
}
