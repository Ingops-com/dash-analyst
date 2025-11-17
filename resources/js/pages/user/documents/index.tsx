import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';

interface ProgramItem {
  program: { id: number; nombre: string; codigo?: string };
  has_pdf: boolean;
  has_docx: boolean;
  pdf_url?: string | null;
  docx_url?: string | null;
}

export default function UserDocumentsIndex({ company, items }: { company: { id: number; nombre: string }; items: ProgramItem[] }) {
  return (
  <AppLayout>
      <Head title={`Documentos de ${company.nombre}`} />
      <div className="space-y-6">
        <h1 className="text-2xl font-semibold">Documentos por Programa</h1>
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          {items.map((it) => (
            <Card key={it.program.id} className="p-4 flex flex-col gap-3">
              <div>
                <div className="text-sm text-muted-foreground">{it.program.codigo}</div>
                <div className="text-lg font-medium">{it.program.nombre}</div>
              </div>
              <div className="text-sm">
                <div>
                  Estado PDF: {it.has_pdf ? <span className="text-green-600">disponible</span> : <span className="text-amber-600">no generado</span>}
                </div>
                <div>
                  Estado DOCX: {it.has_docx ? <span className="text-green-600">disponible</span> : <span className="text-amber-600">no generado</span>}
                </div>
              </div>
              <div className="flex gap-2 mt-auto">
                <Link href={`/user/companies/${company.id}/programs/${it.program.id}/document`} className="w-full">
                  <Button className="w-full" variant="default">Ver</Button>
                </Link>
                {it.pdf_url && (
                  <a href={it.pdf_url} target="_blank" rel="noreferrer" className="w-full">
                    <Button className="w-full" variant="secondary">Abrir PDF</Button>
                  </a>
                )}
              </div>
            </Card>
          ))}
        </div>
      </div>
    </AppLayout>
  );
}
