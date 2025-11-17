import { useState, useMemo, ChangeEvent, useEffect } from 'react';
import { usePage, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { RichTextEditor } from '@/components/RichTextEditor';
import { Progress } from '@/components/ui/progress';
import { Badge } from '@/components/ui/badge';
import {
    PlusCircle, Eye, Upload, FileCheck2, XCircle, Images, FileText, FileDigit, File, Trash2, FileDown
} from 'lucide-react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';

// Tipos, Datos y Helpers (sin cambios)
export type AnnexType = 'IMAGES' | 'PDF' | 'WORD' | 'XLSX' | 'FORMATO';
interface Poe { id: number; date: string }
interface AnnexFile { id?: number; name?: string; url?: string; mime?: string; uploaded_at?: string }
interface Annex { 
    id: number; 
    name: string; 
    code?: string; 
    uploaded_at?: string; 
    type: AnnexType; 
    content_type?: string; 
    content_text?: string; 
    table_columns?: string[];
    table_header_color?: string;
    table_data?: Record<string, string>[];
    files: AnnexFile[] 
}
interface Program { id: number; name: string; annexes: Annex[]; poes: Poe[] }
const typeLabel: Record<AnnexType, string> = { IMAGES: 'Imágenes', PDF: 'PDF', WORD: 'Word', XLSX: 'Excel', FORMATO: 'Formato' };
const typeAccept: Record<AnnexType, string> = { IMAGES: 'image/*', PDF: 'application/pdf', WORD: '.doc,.docx', XLSX: '.xls,.xlsx', FORMATO: '' };
const typeIcon: Record<AnnexType, any> = { IMAGES: Images, PDF: FileText, WORD: FileText, XLSX: FileDigit, FORMATO: File };

export default function ProgramView() {
  // Usar props enviados por el servidor si existen
  const { props } = usePage()
  const serverProgram = (props as any).program ?? null

  // Si no llega programa desde el servidor, no mostrar datos de prueba; avisar claramente
  if (!serverProgram) {
    return (
      <AppLayout>
        <div className="p-6">
          <h1 className="text-lg font-semibold">Programa no encontrado</h1>
          <p className="text-sm text-muted-foreground">No se recibieron datos del servidor para este programa. Verifica la ruta o la carga de datos en el backend.</p>
        </div>
      </AppLayout>
    )
  }

  const [program, setProgram] = useState<Program>(serverProgram as Program);
  const [uploadOpenFor, setUploadOpenFor] = useState<number | null>(null);
  const [viewOpenFor, setViewOpenFor] = useState<{ kind: 'ANNEX' | 'POE' | null; id?: number }>({ kind: null });
  const [isGenerating, setIsGenerating] = useState(false);
  const [textContent, setTextContent] = useState<Record<number, string>>({});
  const [tableData, setTableData] = useState<Record<number, Record<string, string>[]>>({});

  // Obtener company_id dinámicamente
  const serverCompany = (props as any).company as any | undefined;
  const companyId = serverCompany?.id ?? 1;

  // Sincronizar el estado local con los datos del servidor cuando cambien
  useEffect(() => {
    if (serverProgram) {
      setProgram(serverProgram as Program);
      // Cargar content_text existente para anexos de texto
      const textMap: Record<number, string> = {};
      const tableMap: Record<number, Record<string, string>[]> = {};
      (serverProgram as Program).annexes.forEach(annex => {
        if (annex.content_type === 'text' && annex.content_text) {
          textMap[annex.id] = annex.content_text;
        }
        if (annex.content_type === 'table' && annex.table_data) {
          tableMap[annex.id] = annex.table_data;
        }
      });
      setTextContent(textMap);
      setTableData(tableMap);
    }
  }, [serverProgram]);

  // Lógica de la vista (sin cambios)
  const progress = useMemo(() => {
    const annexesForProgress = program.annexes.filter(a => a.type !== 'FORMATO');
    const total = annexesForProgress.length;
    if (!total) return 0;
    const completed = annexesForProgress.filter(a => {
      if (a.content_type === 'text') return !!a.content_text;
      if (a.content_type === 'table') return (tableData[a.id] && tableData[a.id].length > 0);
      return a.files.length > 0;
    }).length;
    return Math.round((completed / total) * 100);
  }, [program, tableData]);
  const handleAddPoe = () => setProgram(prev => ({ ...prev, poes: [{ id: Date.now(), date: new Date().toLocaleDateString('es-CO') }, ...prev.poes] }));
  
  const handleAnnexUpload = async (annexId: number, e: ChangeEvent<HTMLInputElement>) => {
    const files = e.target.files ? Array.from(e.target.files) : [];
    if (files.length === 0) return;

    // Get company ID from props
    const serverCompany = (props as any).company as any | undefined;
    if (!serverCompany || !serverCompany.id) {
      alert('No se pudo identificar la empresa. Por favor, recarga la página.');
      return;
    }

    // Get CSRF token
    const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
    if (!csrfTokenMeta) {
      alert('Token CSRF no encontrado. Por favor, recarga la página.');
      return;
    }
    const csrfToken = (csrfTokenMeta as HTMLMetaElement).content;

    // Find the annex to get its type
    const currentAnnex = program.annexes.find(a => a.id === annexId);
    const isMultipleFiles = currentAnnex?.type === 'IMAGES';

    try {
      // Upload files to backend
      const uploadedFiles: Array<{ name: string; url: string; mime: string }> = [];
      
      for (const file of (isMultipleFiles ? files : files.slice(0, 1))) {
        const formData = new FormData();
        formData.append('company_id', serverCompany.id.toString());
        formData.append('file', file);

        const response = await fetch(`/programa/${program.id}/annex/${annexId}/upload`, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
          },
          body: formData,
        });

        if (!response.ok) {
          const errorData = await response.json().catch(() => ({ message: 'Error al subir archivo' }));
          throw new Error(errorData.message || 'Error al subir archivo');
        }

        const result = await response.json();
        if (result.success && result.submission) {
          uploadedFiles.push(result.submission);
        }
      }

      // Recargar la página para obtener los datos actualizados del servidor
      // Esto asegura que los archivos se muestren correctamente desde la BD
      router.reload({
        only: ['program'],
        onSuccess: () => {
          setUploadOpenFor(null);
        }
      });

    } catch (error) {
      console.error('Error uploading annex files:', error);
      alert(error instanceof Error ? error.message : 'Error al subir los archivos');
    }
  };

  const handleTextSubmit = async (annexId: number) => {
    const text = textContent[annexId] || '';
    
    if (!text.trim()) {
      alert('Por favor ingresa algún contenido de texto.');
      return;
    }

    // Get company ID from props
    const serverCompany = (props as any).company as any | undefined;
    if (!serverCompany || !serverCompany.id) {
      alert('No se pudo identificar la empresa. Por favor, recarga la página.');
      return;
    }

    // Get CSRF token
    const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
    if (!csrfTokenMeta) {
      alert('Token CSRF no encontrado. Por favor, recarga la página.');
      return;
    }
    const csrfToken = (csrfTokenMeta as HTMLMetaElement).content;

    try {
      const response = await fetch(`/programa/${program.id}/annex/${annexId}/upload`, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          company_id: serverCompany.id,
          content_text: text,
        }),
      });

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({ message: 'Error al guardar el texto' }));
        throw new Error(errorData.message || 'Error al guardar el texto');
      }

      // Recargar la página para obtener los datos actualizados del servidor
      router.reload({
        only: ['program'],
        onSuccess: () => {
          setUploadOpenFor(null);
        }
      });

    } catch (error) {
      console.error('Error saving text content:', error);
      alert(error instanceof Error ? error.message : 'Error al guardar el texto');
    }
  };

  const handleAddTableRow = (annexId: number) => {
    const annex = program.annexes.find(a => a.id === annexId);
    if (!annex || !annex.table_columns) return;
    
    const newRow: Record<string, string> = {};
    annex.table_columns.forEach(col => {
      newRow[col] = '';
    });
    
    setTableData(prev => ({
      ...prev,
      [annexId]: [...(prev[annexId] || []), newRow]
    }));
  };

  const handleRemoveTableRow = (annexId: number, rowIndex: number) => {
    setTableData(prev => ({
      ...prev,
      [annexId]: (prev[annexId] || []).filter((_, i) => i !== rowIndex)
    }));
  };

  const handleTableCellChange = (annexId: number, rowIndex: number, columnName: string, value: string) => {
    setTableData(prev => {
      const annexData = [...(prev[annexId] || [])];
      annexData[rowIndex] = {
        ...annexData[rowIndex],
        [columnName]: value
      };
      return {
        ...prev,
        [annexId]: annexData
      };
    });
  };

  const handleTableSubmit = async (annexId: number) => {
    const data = tableData[annexId];
    if (!data || data.length === 0) {
      alert('Por favor agrega al menos una fila a la tabla.');
      return;
    }

    // Get company ID from props
    const serverCompany = (props as any).company as any | undefined;
    if (!serverCompany || !serverCompany.id) {
      alert('No se pudo identificar la empresa. Por favor, recarga la página.');
      return;
    }

    // Get CSRF token
    const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
    if (!csrfTokenMeta) {
      alert('Token CSRF no encontrado. Por favor, recarga la página.');
      return;
    }
    const csrfToken = (csrfTokenMeta as HTMLMetaElement).content;

    try {
      const response = await fetch(`/programa/${program.id}/annex/${annexId}/upload`, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          company_id: serverCompany.id,
          table_data: data,
        }),
      });

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({ message: 'Error al guardar la tabla' }));
        throw new Error(errorData.message || 'Error al guardar la tabla');
      }

      // Recargar la página para obtener los datos actualizados del servidor
      router.reload({
        only: ['program'],
        onSuccess: () => {
          setUploadOpenFor(null);
        }
      });

    } catch (error) {
      console.error('Error saving table data:', error);
      alert(error instanceof Error ? error.message : 'Error al guardar la tabla');
    }
  };

  const renderTableInput = (annex: Annex) => {
    if (!annex.table_columns || annex.table_columns.length === 0) {
      return (
        <div className="text-sm text-muted-foreground">
          Este anexo tipo tabla no tiene columnas configuradas.
        </div>
      );
    }

    const currentData = tableData[annex.id] || [];
    const headerColor = annex.table_header_color || '#153366';

    return (
      <div className="space-y-4">
        <div className="flex items-center justify-between">
          <h4 className="text-sm font-medium">Datos de la tabla</h4>
          <Button 
            type="button" 
            variant="outline" 
            size="sm" 
            onClick={() => handleAddTableRow(annex.id)}
          >
            <PlusCircle className="h-4 w-4 mr-2" />
            Agregar Fila
          </Button>
        </div>

        {currentData.length > 0 ? (
          <div className="border rounded-md overflow-auto max-h-[400px]">
            <table className="w-full text-sm">
              <thead style={{ backgroundColor: headerColor }}>
                <tr>
                  {annex.table_columns.map((col, idx) => (
                    <th key={idx} className="px-3 py-2 text-left text-white font-semibold border-r last:border-r-0">
                      {col}
                    </th>
                  ))}
                  <th className="px-3 py-2 w-16"></th>
                </tr>
              </thead>
              <tbody>
                {currentData.map((row, rowIdx) => (
                  <tr key={rowIdx} className="border-b hover:bg-muted/50">
                    {annex.table_columns!.map((col, colIdx) => (
                      <td key={colIdx} className="px-2 py-1 border-r last:border-r-0">
                        <Input
                          value={row[col] || ''}
                          onChange={(e) => handleTableCellChange(annex.id, rowIdx, col, e.target.value)}
                          className="h-8 text-sm"
                          placeholder={col}
                        />
                      </td>
                    ))}
                    <td className="px-2 py-1">
                      <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="h-8 w-8"
                        onClick={() => handleRemoveTableRow(annex.id, rowIdx)}
                      >
                        <Trash2 className="h-4 w-4" />
                      </Button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        ) : (
          <div className="border rounded-md p-6 text-center text-sm text-muted-foreground">
            No hay filas. Haz clic en "Agregar Fila" para empezar.
          </div>
        )}

        <Button onClick={() => handleTableSubmit(annex.id)} className="w-full">
          Guardar Tabla
        </Button>
      </div>
    );
  };
  
  const clearAnnexFiles = async (annexId: number) => {
    if (!confirm('¿Estás seguro de que deseas eliminar todos los archivos de este anexo?')) {
      return;
    }

    // Get company ID from props
    const serverCompany = (props as any).company as any | undefined;
    if (!serverCompany || !serverCompany.id) {
      alert('No se pudo identificar la empresa.');
      return;
    }

    // Get CSRF token
    const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
    if (!csrfTokenMeta) {
      alert('Token CSRF no encontrado. Por favor, recarga la página.');
      return;
    }
    const csrfToken = (csrfTokenMeta as HTMLMetaElement).content;

    try {
      const response = await fetch(`/programa/${program.id}/annex/${annexId}/clear`, {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          company_id: serverCompany.id,
        }),
      });

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({ message: 'Error al eliminar archivos' }));
        throw new Error(errorData.message || 'Error al eliminar archivos');
      }

      // Recargar la página para obtener los datos actualizados del servidor
      router.reload({
        only: ['program'],
      });

    } catch (error) {
      console.error('Error clearing annex files:', error);
      alert(error instanceof Error ? error.message : 'Error al eliminar los archivos');
    }
  };

  const deleteAnnexFile = async (annexId: number, fileId: number) => {
    if (!confirm('¿Estás seguro de que deseas eliminar este archivo?')) {
      return;
    }

    // Get company ID from props
    const serverCompany = (props as any).company as any | undefined;
    if (!serverCompany || !serverCompany.id) {
      alert('No se pudo identificar la empresa.');
      return;
    }

    // Get CSRF token
    const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
    if (!csrfTokenMeta) {
      alert('Token CSRF no encontrado. Por favor, recarga la página.');
      return;
    }
    const csrfToken = (csrfTokenMeta as HTMLMetaElement).content;

    try {
      const response = await fetch(`/programa/${program.id}/annex/${annexId}/file/${fileId}`, {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          company_id: serverCompany.id,
        }),
      });

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({ message: 'Error al eliminar archivo' }));
        throw new Error(errorData.message || 'Error al eliminar archivo');
      }

      // Recargar la página para obtener los datos actualizados del servidor
      router.reload({
        only: ['program'],
      });

    } catch (error) {
      console.error('Error deleting annex file:', error);
      alert(error instanceof Error ? error.message : 'Error al eliminar el archivo');
    }
  };
  
  const openViewAnnex = (annex: Annex) => setViewOpenFor({ kind: 'ANNEX', id: annex.id });
  const openViewPoe = (poe: Poe) => setViewOpenFor({ kind: 'POE', id: poe.id });
  const currentAnnex = program.annexes.find(a => a.id === viewOpenFor.id);
  const totalAnnex = program.annexes.filter(a => a.type !== 'FORMATO').length;
  const completedAnnex = program.annexes.filter(a => 
    a.type !== 'FORMATO' && (a.content_type === 'text' ? !!a.content_text : a.files.length > 0)
  ).length;

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

      // Solo enviar anexos que son archivos File reales (no los ya subidos desde el servidor)
      let anexoIndex = 0;
      program.annexes.forEach((annex) => {
        if (annex.files.length > 0) {
          // Verificar si el primer archivo es un File object real (no un objeto { name, url, mime })
          const firstFile = annex.files[0];
          // Los archivos ya subidos tienen la propiedad 'url', los File reales no
          if (firstFile && !('url' in firstFile) && firstFile instanceof File) {
            formData.append(`anexos[${anexoIndex}][id]`, annex.id.toString());
            formData.append(`anexos[${anexoIndex}][archivo]`, firstFile as File);
            anexoIndex++;
          }
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

  // Ya no descargamos - refrescamos la página para que se actualice el PDF disponible
  alert('Documento generado exitosamente. El PDF estará disponible en el visor.');
  // Recargar datos de programa y empresa para obtener la nueva URL del PDF (con cache-busting)
  router.reload({ only: ['program', 'company'] });
    } catch (error) {
        console.error("Error al generar PDF:", error);
        alert(error instanceof Error ? error.message : "Ocurrió un error inesperado.");
    } finally {
        setIsGenerating(false);
    }
  };

  const getFileUrl = (f: any) => {
    if (!f) return ''
    if (typeof f === 'object' && 'url' in f) {
      const u = (f as any).url as string
      // Codificar espacios y caracteres especiales para evitar 404 por URL mal formada
      try {
        if (u.startsWith('http://') || u.startsWith('https://') || u.startsWith('/')) {
          return encodeURI(u)
        }
        return u
      } catch {
        return u
      }
    }
    try {
      return URL.createObjectURL(f as Blob)
    } catch {
      return ''
    }
  }

  const getTextPreview = (html: string, maxLength: number = 100): string => {
    // Eliminar etiquetas HTML
    const text = html.replace(/<[^>]*>/g, '');
    // Eliminar espacios múltiples y saltos de línea
    const cleaned = text.replace(/\s+/g, ' ').trim();
    // Truncar si es muy largo
    if (cleaned.length > maxLength) {
      return cleaned.substring(0, maxLength) + '...';
    }
    return cleaned;
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
                        const isCompleted = annex.content_type === 'text' 
                            ? !!annex.content_text 
                            : annex.content_type === 'table'
                            ? (tableData[annex.id] && tableData[annex.id].length > 0)
                            : annex.files.length > 0;
                        const fileLabel = annex.content_type === 'text' 
                            ? (annex.content_text ? 'Texto proporcionado' : 'Sin texto')
                            : annex.content_type === 'table'
                            ? (tableData[annex.id] && tableData[annex.id].length > 0 ? `${tableData[annex.id].length} fila(s)` : 'Sin datos')
                            : (annex.type === 'IMAGES' ? `${annex.files.length} imagen(es)` : annex.files[0]?.name || 'Sin archivo');
                        const textPreview = annex.content_type === 'text' && annex.content_text 
                            ? getTextPreview(annex.content_text, 80) 
                            : null;
                        return (
                            <Card key={annex.id} className="border-muted/60">
                                <CardContent className="p-4">
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="min-w-0 flex-1">
                      <div className="flex items-center gap-2">
                        <Icon className="h-4 w-4 text-muted-foreground" />
                        <p className="font-semibold truncate" title={annex.name}>{annex.name}</p>
                        {annex.content_type === 'text' ? (
                          <Badge variant="secondary" className="text-[11px]">Texto</Badge>
                        ) : annex.content_type === 'table' ? (
                          <Badge variant="default" className="text-[11px]">Tabla</Badge>
                        ) : (
                          <Badge variant="outline" className="text-[11px]">Imagen</Badge>
                        )}
                      </div>
                                            <div className={`mt-1 flex items-center gap-2 text-sm ${isCompleted ? 'text-emerald-600' : 'text-red-600'}`}>
                                                {isCompleted ? <FileCheck2 className="h-4 w-4"/> : <XCircle className="h-4 w-4"/>}
                                                <span className="truncate" title={fileLabel}>{fileLabel}</span>
                                            </div>
                                            {textPreview && (
                                                <div className="mt-2 text-xs text-muted-foreground italic border-l-2 border-muted pl-2">
                                                    {textPreview}
                                                </div>
                                            )}
                                        </div>
                                        <div className="flex items-center gap-2 flex-shrink-0">
                                        <Button 
                                            variant="outline" 
                                            size="sm" 
                                            onClick={() => openViewAnnex(annex)} 
                                            disabled={!isCompleted}
                                        >
                                            <Eye className="h-4 w-4 mr-2"/>Ver
                                        </Button>
                                        <Dialog open={uploadOpenFor === annex.id} onOpenChange={(o) => setUploadOpenFor(o ? annex.id : null)}>
                                            <DialogTrigger asChild>
                                                <Button variant="default" size="sm"><Upload className="h-4 w-4 mr-2"/>Subir</Button>
                                            </DialogTrigger>
                                            <DialogContent>
                                                <DialogHeader>
                                                    <DialogTitle>Subir anexo: {annex.name}</DialogTitle>
                                                </DialogHeader>
                                                {/* Header informativo del anexo */}
                                                <div className="mb-3 border rounded-md p-3 bg-muted/20">
                                                  <div className="grid grid-cols-3 gap-3 items-center">
                                                    <div className="flex items-center">
                                                      {((props as any).company?.logo_left_url) ? (
                                                        <img src={(props as any).company.logo_left_url as string} alt="logo" className="h-8 w-auto object-contain" />
                                                      ) : (
                                                        <div className="text-[11px] text-muted-foreground">Sin logo</div>
                                                      )}
                                                    </div>
                                                    <div className="text-center">
                                                      <div className="text-sm font-semibold truncate" title={annex.name}>{annex.name}</div>
                                                    </div>
                                                    <div className="text-right text-xs leading-5">
                                                      <div><span className="font-medium">Versión:</span> 1</div>
                                                      <div><span className="font-medium">Código:</span> {annex.code ?? '—'}</div>
                                                      <div><span className="font-medium">Fecha de subida:</span> {annex.uploaded_at ?? '—'}</div>
                                                    </div>
                                                  </div>
                                                </div>
                                                {annex.content_type === 'text' ? (
                                                    <div className="space-y-4">
                                                        <RichTextEditor
                                                            content={textContent[annex.id] || ''}
                                                            onChange={(content) => setTextContent(prev => ({ ...prev, [annex.id]: content }))}
                                                            placeholder={`Ingresa el contenido para ${annex.name}...`}
                                                        />
                                                        <Button onClick={() => handleTextSubmit(annex.id)} className="w-full">
                                                            Guardar Texto
                                                        </Button>
                                                    </div>
                                                ) : annex.content_type === 'table' ? (
                                                    <div className="space-y-4">
                                                        {renderTableInput(annex)}
                                                    </div>
                                                ) : (
                                                    <Input type="file" accept={typeAccept[annex.type]} multiple={annex.type==='IMAGES'} onChange={(e) => handleAnnexUpload(annex.id, e)} />
                                                )}
                                            </DialogContent>
                                        </Dialog>
                                        {(annex.files.length > 0 || (annex.content_type === 'text' && annex.content_text)) && (
                                            <Button variant="ghost" size="icon" onClick={() => clearAnnexFiles(annex.id)} title="Quitar contenido">
                                                <Trash2 className="h-4 w-4"/>
                                            </Button>
                                        )}
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        )
                    })}
                </CardContent>
            </Card>
        </div>

        {/* --- VISTA PREVIA DEL PDF GENERADO --- */}
        {((props as any).company?.current_pdf_url) && (
          <Card className="mt-6">
            <CardHeader>
              <CardTitle>Vista Previa del Documento</CardTitle>
              <CardDescription>Visualización del PDF generado para este programa</CardDescription>
            </CardHeader>
            <CardContent>
              <iframe 
                // Usar URL con parámetro de versión para evitar cache
                src={((props as any).company.current_pdf_url_cache as string) || ((props as any).company.current_pdf_url as string)}
                // Cambiar la key para forzar el re-render cuando cambia la versión del PDF
                key={String(((props as any).company.current_pdf_mtime as number) ?? '')}
                className="w-full h-[75vh] border rounded" 
                title="Vista previa del documento PDF"
              />
            </CardContent>
          </Card>
        )}
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
          {/* Header de 3 columnas para anexos */}
          {viewOpenFor.kind === 'ANNEX' && currentAnnex && (
            <div className="mb-4 border rounded-md p-3 bg-muted/30">
              <div className="grid grid-cols-3 gap-3 items-center">
                {/* Columna 1: Logo */}
                <div className="flex items-center">
                  {((props as any).company?.logo_left_url) ? (
                    <img
                      src={(props as any).company.logo_left_url as string}
                      alt="logo"
                      className="h-10 w-auto object-contain"
                    />
                  ) : (
                    <div className="text-xs text-muted-foreground">Sin logo</div>
                  )}
                </div>
                {/* Columna 2: Nombre del anexo */}
                <div className="text-center">
                  <div className="text-sm font-semibold">{currentAnnex.name}</div>
                </div>
                {/* Columna 3: Versión, Código, Fecha */}
                <div className="text-right text-xs leading-5">
                  <div><span className="font-medium">Versión:</span> 1</div>
                  <div><span className="font-medium">Código:</span> {currentAnnex.code ?? '—'}</div>
                  <div><span className="font-medium">Fecha de subida:</span> {currentAnnex.uploaded_at ?? '—'}</div>
                </div>
              </div>
            </div>
          )}
          {viewOpenFor.kind === 'ANNEX' && currentAnnex && currentAnnex.content_type === 'text' && currentAnnex.content_text && (
            <div className="p-6 border rounded-lg bg-white dark:bg-gray-900">
              <div className="mb-4 flex items-center gap-2 text-sm text-muted-foreground border-b pb-2">
                <FileText className="h-4 w-4" />
                <span>Contenido de texto</span>
              </div>
              <div 
                className="prose prose-sm dark:prose-invert max-w-none prose-headings:font-semibold prose-p:leading-relaxed prose-a:text-primary prose-strong:text-foreground prose-ul:list-disc prose-ol:list-decimal" 
                dangerouslySetInnerHTML={{ __html: currentAnnex.content_text }}
              />
            </div>
          )}
          {viewOpenFor.kind === 'ANNEX' && currentAnnex && currentAnnex.content_type === 'text' && !currentAnnex.content_text && (
            <div className="p-6 border rounded-lg bg-muted/30 text-center">
              <p className="text-muted-foreground">No hay contenido de texto para este anexo.</p>
            </div>
          )}
          {viewOpenFor.kind === 'ANNEX' && currentAnnex && currentAnnex.content_type === 'table' && currentAnnex.table_data && currentAnnex.table_data.length > 0 && (
            <div className="p-6 border rounded-lg bg-white dark:bg-gray-900">
              <div className="mb-4 flex items-center gap-2 text-sm text-muted-foreground border-b pb-2">
                <FileDigit className="h-4 w-4" />
                <span>Datos de la tabla ({currentAnnex.table_data.length} filas)</span>
              </div>
              <div className="overflow-auto max-h-[60vh] border rounded-lg">
                <table className="w-full text-sm border-collapse">
                  <thead style={{ backgroundColor: currentAnnex.table_header_color || '#153366' }}>
                    <tr>
                      {currentAnnex.table_columns?.map((col, idx) => (
                        <th key={idx} className="px-4 py-3 text-left text-white font-semibold border-r last:border-r-0 border-white/20">
                          {col}
                        </th>
                      ))}
                    </tr>
                  </thead>
                  <tbody>
                    {currentAnnex.table_data.map((row, rowIdx) => (
                      <tr key={rowIdx} className="border-b hover:bg-muted/50 transition-colors">
                        {currentAnnex.table_columns?.map((col, colIdx) => (
                          <td key={colIdx} className="px-4 py-2 border-r last:border-r-0">
                            {row[col] || '-'}
                          </td>
                        ))}
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}
          {viewOpenFor.kind === 'ANNEX' && currentAnnex && currentAnnex.content_type === 'table' && (!currentAnnex.table_data || currentAnnex.table_data.length === 0) && (
            <div className="p-6 border rounded-lg bg-muted/30 text-center">
              <p className="text-muted-foreground">No hay datos en esta tabla.</p>
            </div>
          )}
          {viewOpenFor.kind === 'ANNEX' && currentAnnex && currentAnnex.type === 'IMAGES' && currentAnnex.files.length > 0 && (
            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
              {currentAnnex.files.map((f: any, i) => (
                <div key={f.id || i} className="relative group border rounded-lg overflow-hidden">
                  <img src={getFileUrl(f)} alt={`img-${i}`} className="w-full h-40 object-cover" />
                  {f.id && (
                    <Button
                      variant="destructive"
                      size="icon"
                      className="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity"
                      onClick={() => deleteAnnexFile(currentAnnex.id, f.id)}
                      title="Eliminar archivo"
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  )}
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