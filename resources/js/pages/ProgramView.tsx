import { useState, useMemo, ChangeEvent } from 'react'
import AppLayout from '@/layouts/app-layout'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Progress } from '@/components/ui/progress'
import { Badge } from '@/components/ui/badge'
import { PlusCircle, Eye, Upload, FileCheck2, XCircle, Images, FileText, FileDigit, File, Trash2 } from 'lucide-react'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger, DialogFooter } from '@/components/ui/dialog'

// Tipos
export type AnnexType = 'IMAGES' | 'PDF' | 'WORD' | 'XLSX' | 'FORMATO'
interface Poe { id: number; date: string }
interface Annex { id: number; name: string; type: AnnexType; files: File[] }
interface Program { id: number; name: string; annexes: Annex[]; poes: Poe[] }

// Mock
const programData: Program = {
  id: 1,
  name: 'Programa de Limpieza y Desinfección',
  annexes: [
    { id: 1, name: 'Certificado de Fumigación', type: 'PDF', files: [] },
    { id: 2, name: 'Factura de Insumos', type: 'XLSX', files: [] },
    { id: 3, name: 'Registro Fotográfico', type: 'IMAGES', files: [] },
    { id: 4, name: 'Checklist Interno', type: 'FORMATO', files: [] },
    { id: 5, name: 'Memorando Aprobación', type: 'WORD', files: [] },
  ],
  poes: [],
}

// Helpers
const typeLabel: Record<AnnexType, string> = {
  IMAGES: 'Imágenes',
  PDF: 'PDF',
  WORD: 'Word',
  XLSX: 'Excel',
  FORMATO: 'Formato',
}
const typeAccept: Record<AnnexType, string> = {
  IMAGES: 'image/*',
  PDF: 'application/pdf',
  WORD: '.doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
  XLSX: '.xls,.xlsx,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
  FORMATO: '',
}
const typeIcon: Record<AnnexType, any> = {
  IMAGES: Images,
  PDF: FileText,
  WORD: FileText,
  XLSX: FileDigit,
  FORMATO: File,
}

export default function ProgramView() {
  const [program, setProgram] = useState<Program>(programData)
  const [uploadOpenFor, setUploadOpenFor] = useState<number | null>(null)
  const [viewOpenFor, setViewOpenFor] = useState<{ kind: 'ANNEX' | 'POE' | null; id?: number }>({ kind: null })

  // Progreso: solo anexos que no sean FORMATO, POES no cuentan
  const progress = useMemo(() => {
    const annexesForProgress = program.annexes.filter(a => a.type !== 'FORMATO')
    const total = annexesForProgress.length
    if (!total) return 0
    const completed = annexesForProgress.filter(a => a.files.length > 0).length
    return Math.round((completed / total) * 100)
  }, [program])

  const handleAddPoe = () => {
    const newPoe: Poe = { id: Date.now(), date: new Date().toLocaleDateString('es-CO') }
    setProgram(prev => ({ ...prev, poes: [newPoe, ...prev.poes] }))
  }

  const handleAnnexUpload = (annexId: number, e: ChangeEvent<HTMLInputElement>) => {
    const files = e.target.files ? Array.from(e.target.files) : []
    setProgram(prev => ({
      ...prev,
      annexes: prev.annexes.map(a => {
        if (a.id !== annexId) return a
        if (a.type === 'IMAGES') return { ...a, files }
        return { ...a, files: files.slice(0, 1) }
      })
    }))
    setUploadOpenFor(null)
  }

  const clearAnnexFiles = (annexId: number) => {
    setProgram(prev => ({
      ...prev,
      annexes: prev.annexes.map(a => a.id === annexId ? { ...a, files: [] } : a)
    }))
  }

  const openViewAnnex = (annex: Annex) => {
    setViewOpenFor({ kind: 'ANNEX', id: annex.id })
  }

  const openViewPoe = (poe: Poe) => {
    setViewOpenFor({ kind: 'POE', id: poe.id })
  }

  const currentAnnex = program.annexes.find(a => a.id === viewOpenFor.id)
  const CurrentAnnexIcon = currentAnnex ? typeIcon[currentAnnex.type] : null

  // Totales anexos
  const totalAnnex = program.annexes.filter(a => a.type !== 'FORMATO').length
  const completedAnnex = program.annexes.filter(a => a.type !== 'FORMATO' && a.files.length > 0).length

  return (
    <AppLayout>
      <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-xl font-semibold">{program.name}</h1>
            <p className="text-sm text-muted-foreground">Gestión de anexos y POES</p>
          </div>
          <div className="text-right">
            <p className="text-lg font-bold">{progress}% Completado</p>
            <Progress value={progress} className="w-48 mt-1" />
            <p className="text-[11px] text-muted-foreground mt-1">*El porcentaje no incluye POES ni anexos de tipo FORMATO.</p>
          </div>
        </div>

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
                <CardDescription>Sube los documentos requeridos. Admite imágenes múltiples, PDF, Word, Excel, y formatos internos.</CardDescription>
              </div>
              <div className="text-sm text-muted-foreground">{completedAnnex}/{totalAnnex}</div>
            </CardHeader>
            <CardContent className="space-y-4 max-h-[420px] overflow-y-auto">
              {program.annexes.map(annex => {
                const Icon = typeIcon[annex.type]
                const isCompleted = annex.files.length > 0
                const fileLabel =
                  annex.type === 'IMAGES' ? `${annex.files.length} imagen(es)` :
                  annex.files[0]?.name || 'Sin archivo'

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
                              <div className="py-2 space-y-2">
                                <p className="text-sm text-muted-foreground">Tipo admitido: {typeLabel[annex.type]} {annex.type==='IMAGES' ? '(múltiples archivos)' : ''}</p>
                                <Input type="file" accept={typeAccept[annex.type]} multiple={annex.type==='IMAGES'} onChange={(e) => handleAnnexUpload(annex.id, e)} />
                              </div>
                              <DialogFooter>
                                <Button variant="outline" onClick={() => setUploadOpenFor(null)}>Cerrar</Button>
                              </DialogFooter>
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

      {/* Viewer modal */}
      <Dialog open={!!viewOpenFor.kind} onOpenChange={(o) => !o && setViewOpenFor({ kind: null })}>
        <DialogContent className="sm:max-w-[800px] max-h-[85vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>
              {viewOpenFor.kind === 'POE' && 'POE – Vista previa'}
              {viewOpenFor.kind === 'ANNEX' && currentAnnex && `${currentAnnex.name}`}
            </DialogTitle>
          </DialogHeader>

          {/* Imagenes mejoradas */}
          {viewOpenFor.kind === 'ANNEX' && currentAnnex && currentAnnex.type === 'IMAGES' && currentAnnex.files.length > 0 && (
            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
              {currentAnnex.files.map((f, i) => (
                <div key={i} className="relative group border rounded-lg overflow-hidden">
                  <img src={URL.createObjectURL(f)} alt={`img-${i}`} className="w-full h-40 object-cover transition-transform group-hover:scale-105" />
                </div>
              ))}
            </div>
          )}

          {/* PDF */}
          {viewOpenFor.kind === 'ANNEX' && currentAnnex && currentAnnex.type === 'PDF' && currentAnnex.files[0] && (
            <iframe title="pdf" src={URL.createObjectURL(currentAnnex.files[0])} className="w-full h-[70vh] rounded border" />
          )}

          {/* WORD / XLSX */}
          {viewOpenFor.kind === 'ANNEX' && currentAnnex && (currentAnnex.type === 'WORD' || currentAnnex.type === 'XLSX') && currentAnnex.files[0] && (
            <div className="flex flex-col items-center gap-3 p-6 border rounded-lg bg-muted/30">
              <p className="text-sm text-muted-foreground">No hay vista previa para este tipo de archivo.</p>
              <a href={URL.createObjectURL(currentAnnex.files[0])} download={currentAnnex.files[0].name} className="inline-flex items-center gap-2 px-3 py-2 rounded border">
                <File className="h-4 w-4"/> Descargar {currentAnnex.files[0].name}
              </a>
            </div>
          )}

          {/* FORMATO / POE */}
          {(viewOpenFor.kind === 'POE' || (currentAnnex && currentAnnex.type === 'FORMATO')) && (
            <div className="p-6 border rounded-lg bg-muted/30 text-center">
              <p className="text-sm text-muted-foreground">Esta vista estará disponible pronto. Aquí podrás diligenciar/visualizar el formato directamente.</p>
            </div>
          )}
        </DialogContent>
      </Dialog>      
      
      {/* Viewer modal */}
      <Dialog open={!!viewOpenFor.kind} onOpenChange={(o) => !o && setViewOpenFor({ kind: null })}>
        <DialogContent className="sm:max-w-[800px] max-h-[85vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>
              {viewOpenFor.kind === 'POE' && 'POE – Vista previa'}
              {viewOpenFor.kind === 'ANNEX' && currentAnnex && `${currentAnnex.name}`}
            </DialogTitle>
          </DialogHeader>

          {/* Imagenes mejoradas */}
          {viewOpenFor.kind === 'ANNEX' && currentAnnex && currentAnnex.type === 'IMAGES' && currentAnnex.files.length > 0 && (
            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
              {currentAnnex.files.map((f, i) => (
                <div key={i} className="relative group border rounded-lg overflow-hidden">
                  <img src={URL.createObjectURL(f)} alt={`img-${i}`} className="w-full h-40 object-cover transition-transform group-hover:scale-105" />
                </div>
              ))}
            </div>
          )}

          {/* PDF */}
          {viewOpenFor.kind === 'ANNEX' && currentAnnex && currentAnnex.type === 'PDF' && currentAnnex.files[0] && (
            <iframe title="pdf" src={URL.createObjectURL(currentAnnex.files[0])} className="w-full h-[70vh] rounded border" />
          )}

          {/* WORD / XLSX */}
          {viewOpenFor.kind === 'ANNEX' && currentAnnex && (currentAnnex.type === 'WORD' || currentAnnex.type === 'XLSX') && currentAnnex.files[0] && (
            <div className="flex flex-col items-center gap-3 p-6 border rounded-lg bg-muted/30">
              <p className="text-sm text-muted-foreground">No hay vista previa para este tipo de archivo.</p>
              <a href={URL.createObjectURL(currentAnnex.files[0])} download={currentAnnex.files[0].name} className="inline-flex items-center gap-2 px-3 py-2 rounded border">
                <File className="h-4 w-4"/> Descargar {currentAnnex.files[0].name}
              </a>
            </div>
          )}

          {/* FORMATO / POE */}
          {(viewOpenFor.kind === 'POE' || (currentAnnex && currentAnnex.type === 'FORMATO')) && (
            <div className="p-6 border rounded-lg bg-muted/30 text-center">
              <p className="text-sm text-muted-foreground">Esta vista estará disponible pronto. Aquí podrás diligenciar/visualizar el formato directamente.</p>
            </div>
          )}
        </DialogContent>
      </Dialog>

      {/* ===== Vista previa del documento final (encabezado / pie) ===== */}
      {(() => {
        const company = {
          name: 'XYZ TECH SOLUTIONS',
          nit: '987654-3',
          address: 'Calle Secundaria 456, Ciudad ABC',
          reviewer: 'ING. Gloria Marcela Cabrejo Moreno',
          approver: 'Pedro Ramírez',
          version: '1.5',
          date: '28/09/2025',
          logos: {
            left: '/images/logo_izq.png',
            right: '/images/logo_der.png',
          },
          footerNote:
            'ELABORADO POR SOCIEDAD COLOMBIANA DE INGENIEROS DE ALIMENTOS MAIL: GYC.CONSULTORESEINGENIEROS@GMAIL.COM  CEL:3504764764. BOGOTÁ - COLOMBIA.',
        }
        return (
          <div className="mt-6">
            <Card>
              <CardHeader className="text-center">
                <CardTitle>Encabezado (vista previa)</CardTitle>
                <CardDescription>Así se verá en el archivo final con los datos de la empresa.</CardDescription>
              </CardHeader>
              <CardContent>
                {/* Marco del encabezado */}
                <div className="border rounded-lg overflow-hidden">
                  {/* Fila superior (franja / logos derecha) */}
                  <div className="grid grid-cols-12 border-b">
                    <div className="col-span-9 h-16" />
                    <div className="col-span-3 flex items-center justify-center border-l h-16">
                      <img src={company.logos.right} alt="logo-right" className="h-12 object-contain" />
                    </div>
                  </div>

                  {/* Fila datos */}
                  <div className="grid grid-cols-12">
                    {/* Columna izquierda (logo) */}
                    <div className="col-span-3 flex items-center justify-center border-r p-3">
                      <img src={company.logos.left} alt="logo-left" className="h-20 object-contain" />
                    </div>

                    {/* Columna centro (revisado / dirección) */}
                    <div className="col-span-6">
                      <div className="grid grid-rows-2">
                        <div className="border-b p-3 flex items-center justify-between gap-4">
                          <span className="font-semibold">Revisado Por:</span>
                          <span className="text-sm text-right">{company.reviewer}</span>
                        </div>
                        <div className="p-3 flex items-center justify-between gap-4">
                          <span className="font-semibold">DIRECCIÓN:</span>
                          <span className="text-sm text-right">{company.address}</span>
                        </div>
                      </div>
                    </div>

                    {/* Columna derecha (version/fecha/nit) */}
                    <div className="col-span-3 border-l">
                      <div className="grid grid-rows-3">
                        <div className="border-b p-3 text-right text-sm">{company.version}</div>
                        <div className="border-b p-3 text-right text-sm">{company.date}</div>
                        <div className="p-3 text-right text-sm">{company.nit}</div>
                      </div>
                    </div>
                  </div>
                </div>

                {/* Pie de página */}
                <div className="mt-8 text-center">
                  <h3 className="text-lg font-semibold mb-3">Pie de página:</h3>
                  <div className="mx-auto max-w-4xl border rounded-lg p-6 bg-muted/30">
                    <p className="text-sm">Documento de uso exclusivo de: <span className="font-semibold">{company.name}</span></p>
                    <p className="text-sm mt-4">{company.footerNote}</p>
                  </div>
                 
                </div>
              </CardContent>
            </Card>
          </div>
        )
      })()}
    </AppLayout>
  )
}
