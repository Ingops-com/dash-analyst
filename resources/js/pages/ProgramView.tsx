import { useState, useMemo, ChangeEvent } from 'react';
import { usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Progress } from '@/components/ui/progress';
import { Badge } from '@/components/ui/badge';
import {
    PlusCircle, Eye, Upload, FileCheck2, XCircle, Images, FileText, FileDigit, File, Trash2, FileDown
} from 'lucide-react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';

// Tipos, Datos y Helpers (sin cambios)
export type AnnexType = 'IMAGES' | 'PDF' | 'WORD' | 'XLSX' | 'FORMATO';
interface Poe { id: number; date: string }
interface Annex { id: number; name: string; type: AnnexType; files: any[] }
interface Program { id: number; name: string; annexes: Annex[]; poes: Poe[] }
const mockProgramData: Program = {
  id: 1, name: 'Programa de Limpieza y Desinfección',
  annexes: [
    { id: 1, name: 'Certificado de Fumigación', type: 'PDF', files: [] },
    { id: 2, name: 'Factura de Insumos', type: 'XLSX', files: [] },
    { id: 3, name: 'Registro Fotográfico', type: 'IMAGES', files: [] },
    { id: 4, name: 'Checklist Interno', type: 'FORMATO', files: [] },
    { id: 5, name: 'Memorando Aprobación', type: 'WORD', files: [] },
  ], poes: [],
};
const typeLabel: Record<AnnexType, string> = { IMAGES: 'Imágenes', PDF: 'PDF', WORD: 'Word', XLSX: 'Excel', FORMATO: 'Formato' };
const typeAccept: Record<AnnexType, string> = { IMAGES: 'image/*', PDF: 'application/pdf', WORD: '.doc,.docx', XLSX: '.xls,.xlsx', FORMATO: '' };
const typeIcon: Record<AnnexType, any> = { IMAGES: Images, PDF: FileText, WORD: FileText, XLSX: FileDigit, FORMATO: File };

export default function ProgramView() {
  // Usar props enviados por el servidor si existen
  const { props } = usePage()
  const serverProgram = (props as any).program ?? mockProgramData
  const [program, setProgram] = useState<Program>(serverProgram as Program);
  const [uploadOpenFor, setUploadOpenFor] = useState<number | null>(null);
  const [viewOpenFor, setViewOpenFor] = useState<{ kind: 'ANNEX' | 'POE' | null; id?: number }>({ kind: null });
  const [isGenerating, setIsGenerating] = useState(false);

  // Lógica de la vista (sin cambios)
  const progress = useMemo(() => {
    const annexesForProgress = program.annexes.filter(a => a.type !== 'FORMATO');
    const total = annexesForProgress.length;
    if (!total) return 0;
    const completed = annexesForProgress.filter(a => a.files.length > 0).length;
    return Math.round((completed / total) * 100);
  }, [program]);
  const handleAddPoe = () => setProgram(prev => ({ ...prev, poes: [{ id: Date.now(), date: new Date().toLocaleDateString('es-CO') }, ...prev.poes] }));
  const handleAnnexUpload = (annexId: number, e: ChangeEvent<HTMLInputElement>) => {
    const files = e.target.files ? Array.from(e.target.files) : [];
    setProgram(prev => ({...prev, annexes: prev.annexes.map(a => a.id === annexId ? { ...a, files: a.type === 'IMAGES' ? files : files.slice(0, 1) } : a)}));
    setUploadOpenFor(null);
  };
  const clearAnnexFiles = (annexId: number) => setProgram(prev => ({ ...prev, annexes: prev.annexes.map(a => a.id === annexId ? { ...a, files: [] } : a) }));
  const openViewAnnex = (annex: Annex) => setViewOpenFor({ kind: 'ANNEX', id: annex.id });
  const openViewPoe = (poe: Poe) => setViewOpenFor({ kind: 'POE', id: poe.id });
  const currentAnnex = program.annexes.find(a => a.id === viewOpenFor.id);
  const totalAnnex = program.annexes.filter(a => a.type !== 'FORMATO').length;
  const completedAnnex = program.annexes.filter(a => a.type !== 'FORMATO' && a.files.length > 0).length;

    const handleGeneratePdf = async () => {
        setIsGenerating(true);

      const formData = new FormData();
      // Preferir company enviada por el servidor via Inertia (props.company). Si no existe, usar valor por defecto de prueba.
      const serverCompany = (props as any).company as any | undefined;
      const company = serverCompany ?? {
        id: 1,
        name: 'XYZ TECH SOLUTIONS',
        nit: '987654-3',
        address: 'Calle Secundaria 456',
        activities: 'Desarrollo de Software',
        representative: 'Pedro Ramírez'
      };

      formData.append('company_id', (company.id ?? 1).toString());
      formData.append('company_name', company.name);
      formData.append('company_nit', company.nit);
      formData.append('company_address', company.address);
      formData.append('company_activities', company.activities);
      formData.append('company_representative', company.representative);

      program.annexes.forEach((annex, index) => {
    if (annex.files.length > 0) {
      // enviar el id del anexo (requerido por la validación del servidor)
      formData.append(`anexos[${index}][id]`, annex.id.toString());
      // por ahora enviamos el primer archivo (el servidor espera un solo archivo por anexo)
      formData.append(`anexos[${index}][archivo]`, annex.files[0]);
    }
    });

    try {
        // --- INICIO DE LA LÓGICA CSRF CORREGIDA ---
        const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
        if (!csrfTokenMeta) {
            throw new Error("El token CSRF no se encontró. Asegúrate de que `resources/views/app.blade.php` tenga la etiqueta <meta name=\"csrf-token\">.");
        }
        const csrfToken = (csrfTokenMeta as HTMLMetaElement).content;
        // --- FIN DE LA LÓGICA CSRF ---

        const response = await fetch(`/programa/${program.id}/generate-pdf`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken, // Se envía la "llave secreta"
                'Accept': 'application/json', // Buena práctica para recibir errores en JSON
            },
            body: formData,
        });

        if (response.status === 419) {
            throw new Error("La sesión ha expirado. Por favor, refresca la página e inténtalo de nuevo.");
        }
        if (!response.ok) {
             const errorData = await response.json().catch(() => ({ message: `Error del servidor: ${response.status}` }));
            throw new Error(errorData.message);
        }

        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `Plan-Generado.docx`;
        a.click();
        window.URL.revokeObjectURL(url);
        a.remove();
    } catch (error) {
        console.error("Error al generar PDF:", error);
        alert(error instanceof Error ? error.message : "Ocurrió un error inesperado.");
    } finally {
        setIsGenerating(false);
    }
  };

  const getFileUrl = (f: any) => {
    if (!f) return ''
    if (typeof f === 'object' && 'url' in f) return f.url
    try {
      return URL.createObjectURL(f as unknown as Blob)
    } catch {
      return ''
    }
  }

  return (
    <AppLayout>
      <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-xl font-semibold">{program.name}</h1>
            <p className="text-sm text-muted-foreground">Gestión de anexos y POES</p>
          </div>

          {/* --- ÁREA DE ACCIONES (CON BOTÓN MODIFICADO) --- */}
          <div className="flex items-center gap-4">
              <Button variant="default" onClick={handleGeneratePdf} disabled={isGenerating}>
                  <FileDown className="h-4 w-4 mr-2" />
                  {isGenerating ? 'Generando...' : 'Generar Documento'}
              </Button>
              <div className="text-right">
                  <p className="text-lg font-bold">{progress}% Completado</p>
                  <Progress value={progress} className="w-48 mt-1" />
                  <p className="text-[11px] text-muted-foreground mt-1">*El porcentaje no incluye POES ni anexos de tipo FORMATO.</p>
              </div>
          </div>
        </div>

        {/* --- RESTO DE LA VISTA (SIN CAMBIOS) --- */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-4">
            {/* POES */}
            <Card className="lg:col-span-1">
                <CardHeader className="flex flex-row items-center justify-between">
                    <CardTitle>POES</CardTitle>
                    <Button size="sm" onClick={handleAddPoe}><PlusCircle className="h-4 w-4 mr-2" />Agregar</Button>
                </CardHeader>
                <CardContent className="space-y-3 max-h-[420px] overflow-y-auto">
                    {program.poes.length === 0 && <div className="text-sm text-muted-foreground">Aún no hay POES registrados.</div>}
                    {program.poes.map(poe => (
                        <Card key={poe.id} className="border-muted">
                            <CardContent className="p-3 flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <Badge variant="secondary">POE</Badge>
                                    <p className="text-sm">Fecha: {poe.date}</p>
                                </div>
                                <Button variant="outline" size="icon" onClick={() => openViewPoe(poe)} title="Visualizar">
                                    <Eye className="h-4 w-4" />
                                </Button>
                            </CardContent>
                        </Card>
                    ))}
                </CardContent>
            </Card>

            {/* Anexos */}
            <Card className="lg:col-span-2">
                <CardHeader className="flex flex-row items-center justify-between">
                    <div>
                        <CardTitle>Anexos del Programa</CardTitle>
                        <CardDescription>Sube los documentos requeridos.</CardDescription>
                    </div>
                    <div className="text-sm text-muted-foreground">{completedAnnex}/{totalAnnex}</div>
                </CardHeader>
                <CardContent className="space-y-4 max-h-[420px] overflow-y-auto">
                    {program.annexes.map(annex => {
                        const Icon = typeIcon[annex.type];
                        const isCompleted = annex.files.length > 0;
                        const fileLabel = annex.type === 'IMAGES' ? `${annex.files.length} imagen(es)` : annex.files[0]?.name || 'Sin archivo';
                        return (
                            <Card key={annex.id} className="border-muted/60">
                                <CardContent className="p-4 flex items-center justify-between gap-3">
                                    <div className="min-w-0">
                                        <div className="flex items-center gap-2">
                                            <Icon className="h-4 w-4 text-muted-foreground" />
                                            <p className="font-semibold truncate" title={annex.name}>{annex.name}</p>
                                            <Badge variant="outline" className="text-[11px]">{typeLabel[annex.type]}</Badge>
                                        </div>
                                        <div className={`mt-1 flex items-center gap-2 text-sm ${isCompleted ? 'text-emerald-600' : 'text-red-600'}`}>
                                            {isCompleted ? <FileCheck2 className="h-4 w-4"/> : <XCircle className="h-4 w-4"/>}
                                            <span className="truncate" title={fileLabel}>{fileLabel}</span>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Button variant="outline" size="sm" onClick={() => openViewAnnex(annex)} disabled={annex.type !== 'FORMATO' && annex.files.length === 0}>
                                            <Eye className="h-4 w-4 mr-2"/>Ver
                                        </Button>
                                        {annex.type !== 'FORMATO' && (
                                            <Dialog open={uploadOpenFor === annex.id} onOpenChange={(o) => setUploadOpenFor(o ? annex.id : null)}>
                                                <DialogTrigger asChild>
                                                    <Button variant="default" size="sm"><Upload className="h-4 w-4 mr-2"/>Subir</Button>
                                                </DialogTrigger>
                                                <DialogContent>
                                                    <DialogHeader>
                                                        <DialogTitle>Subir anexo: {annex.name}</DialogTitle>
                                                    </DialogHeader>
                                                    <Input type="file" accept={typeAccept[annex.type]} multiple={annex.type==='IMAGES'} onChange={(e) => handleAnnexUpload(annex.id, e)} />
                                                </DialogContent>
                                            </Dialog>
                                        )}
                                        {annex.files.length > 0 && (
                                            <Button variant="ghost" size="icon" onClick={() => clearAnnexFiles(annex.id)} title="Quitar archivos">
                                                <Trash2 className="h-4 w-4"/>
                                            </Button>
                                        )}
                                    </div>
                                </CardContent>
                            </Card>
                        )
                    })}
                </CardContent>
            </Card>
        </div>
      </div>

      {/* --- MODAL VISUALIZADOR (Sin cambios) --- */}
      <Dialog open={!!viewOpenFor.kind} onOpenChange={(o) => !o && setViewOpenFor({ kind: null })}>
        <DialogContent className="sm:max-w-[800px] max-h-[85vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>
              {viewOpenFor.kind === 'POE' && 'POE – Vista previa'}
              {viewOpenFor.kind === 'ANNEX' && currentAnnex && `${currentAnnex.name}`}
            </DialogTitle>
          </DialogHeader>
          {viewOpenFor.kind === 'ANNEX' && currentAnnex && currentAnnex.type === 'IMAGES' && currentAnnex.files.length > 0 && (
            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
              {currentAnnex.files.map((f, i) => (
                <div key={i} className="relative group border rounded-lg overflow-hidden">
                  <img src={getFileUrl(f)} alt={`img-${i}`} className="w-full h-40 object-cover" />
                </div>
              ))}
            </div>
          )}
          {viewOpenFor.kind === 'ANNEX' && currentAnnex && currentAnnex.type === 'PDF' && currentAnnex.files[0] && (
            <iframe title="pdf" src={getFileUrl(currentAnnex.files[0])} className="w-full h-[70vh] rounded border" />
          )}
          {viewOpenFor.kind === 'ANNEX' && currentAnnex && (currentAnnex.type === 'WORD' || currentAnnex.type === 'XLSX') && currentAnnex.files[0] && (
            <div className="flex flex-col items-center gap-3 p-6 border rounded-lg bg-muted/30">
              <p>No hay vista previa.</p>
              <a href={getFileUrl(currentAnnex.files[0])} download={currentAnnex.files[0].name ? currentAnnex.files[0].name : 'archivo'}>
                Descargar {currentAnnex.files[0].name ?? 'archivo'}
              </a>
            </div>
          )}
          {(viewOpenFor.kind === 'POE' || (currentAnnex && currentAnnex.type === 'FORMATO')) && (
            <div className="p-6 border rounded-lg bg-muted/30 text-center">
              <p>Esta vista estará disponible pronto.</p>
            </div>
          )}
        </DialogContent>
      </Dialog>
    </AppLayout>
  );
}