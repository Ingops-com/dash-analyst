import { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Separator } from './ui/separator';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Plus, X } from 'lucide-react';

interface Program {
    id: number;
    nombre: string;
    codigo: string;
}

interface AddAnnexDialogProps {
    isOpen: boolean;
    onClose: () => void;
    programs: Program[];
    annex?: {
        id: number;
        nombre: string;
        codigo_anexo: string;
        placeholder?: string;
        content_type?: string;
        planilla_view?: string;
        tipo: string;
        programIds?: number[];
        table_columns?: string[];
        table_header_color?: string;
    } | null;
}

interface AnnexFormData {
    nombre: string;
    codigo_anexo: string;
    placeholder: string;
    content_type: string;
    planilla_view?: string;
    tipo: string;
    programIds: number[];
    table_columns: string[];
    table_header_color: string;
}

const defaultFormState: AnnexFormData = {
    nombre: '',
    codigo_anexo: '',
    placeholder: '',
    content_type: 'image',
    planilla_view: '',
    tipo: 'ISO 22000',
    programIds: [],
    table_columns: [],
    table_header_color: '#153366',
};

const tipos = ['ISO 22000', 'PSB', 'Invima'];

export function AddAnnexDialog({ isOpen, onClose, programs, annex = null }: AddAnnexDialogProps) {
    const [formData, setFormData] = useState({ ...defaultFormState, planilla_view: '' });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    useEffect(() => {
        if (isOpen && annex) {
            setFormData({
                nombre: annex.nombre || '',
                codigo_anexo: annex.codigo_anexo || '',
                placeholder: annex.placeholder || '',
                content_type: annex.content_type || 'image',
                tipo: annex.tipo || 'ISO 22000',
                programIds: annex.programIds || [],
                table_columns: annex.table_columns || [],
                table_header_color: annex.table_header_color || '#153366',
                planilla_view: annex.planilla_view || '',
            });
        } else if (!isOpen) {
            setFormData({ ...defaultFormState, planilla_view: '' });
            setErrors({});
        }
    }, [isOpen, annex]);
    // Lista de vistas de planilla disponibles (puedes agregar más componentes aquí)
    const planillaViews = [
        { name: 'PlanillaSalubridad', label: 'Salubridad' },
        { name: 'ProotTemplate', label: 'Monitoreo - Saneamiento Básico' },
        // Agrega aquí más componentes de planilla
    ];

    const handlePlanillaViewChange = (view: string) => {
        setFormData(prev => ({ ...prev, planilla_view: view }));
    };

    const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const { id, value } = e.target;
        setFormData((prev) => ({ ...prev, [id]: value }));
        // Clear error for this field
        if (errors[id]) {
            setErrors((prev) => {
                const newErrors = { ...prev };
                delete newErrors[id];
                return newErrors;
            });
        }
    };

    const handleSelectChange = (value: string) => {
        setFormData((prev) => ({ ...prev, tipo: value }));
        if (errors.tipo) {
            setErrors((prev) => {
                const newErrors = { ...prev };
                delete newErrors.tipo;
                return newErrors;
            });
        }
    };

    const handleContentTypeChange = (value: string) => {
        setFormData((prev) => ({ ...prev, content_type: value }));
        if (errors.content_type) {
            setErrors((prev) => {
                const newErrors = { ...prev };
                delete newErrors.content_type;
                return newErrors;
            });
        }
    };

    const handleProgramToggle = (programId: number) => {
        setFormData(prev => {
            const newProgramIds = prev.programIds.includes(programId)
                ? prev.programIds.filter(id => id !== programId)
                : [...prev.programIds, programId];
            return { ...prev, programIds: newProgramIds };
        });
        if (errors.programIds) {
            setErrors((prev) => {
                const newErrors = { ...prev };
                delete newErrors.programIds;
                return newErrors;
            });
        }
    };

    const handleAddColumn = () => {
        setFormData(prev => ({
            ...prev,
            table_columns: [...prev.table_columns, '']
        }));
    };

    const handleRemoveColumn = (index: number) => {
        setFormData(prev => ({
            ...prev,
            table_columns: prev.table_columns.filter((_, i) => i !== index)
        }));
    };

    const handleColumnChange = (index: number, value: string) => {
        setFormData(prev => ({
            ...prev,
            table_columns: prev.table_columns.map((col, i) => i === index ? value : col)
        }));
    };

    const handleColorChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        setFormData(prev => ({ ...prev, table_header_color: e.target.value }));
    };

    const handleSubmit = () => {
        setIsSubmitting(true);
        setErrors({});

            const url = annex ? `/anexos/${annex.id}` : '/anexos';
            const method = annex ? 'put' : 'post';

            router[method](url, formData as any, {
            onSuccess: () => {
                setFormData(defaultFormState);
                setIsSubmitting(false);
                onClose();
            },
            onError: (errors) => {
                setErrors(errors);
                setIsSubmitting(false);
            },
        });
    };

    const handleDialogClose = (open: boolean) => {
        if (!open && !isSubmitting) {
            setFormData(defaultFormState);
            setErrors({});
            onClose();
        }
    };

    return (
        <Dialog open={isOpen} onOpenChange={handleDialogClose}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                        <DialogTitle>{annex ? 'Editar Anexo' : 'Agregar Nuevo Anexo'}</DialogTitle>
                    <DialogDescription>
                            {annex ? 'Actualiza los datos del anexo y sus vínculos.' : 'Define el anexo y vincúlalo a uno o más programas.'}
                    </DialogDescription>
                </DialogHeader>

                {/* Annex Details */}
                <div className="grid gap-4 py-2">
                    <div className="grid grid-cols-4 items-center gap-4">
                        <Label htmlFor="nombre" className="text-right">Nombre</Label>
                        <div className="col-span-3">
                            <Input 
                                id="nombre" 
                                value={formData.nombre} 
                                onChange={handleChange} 
                                className={errors.nombre ? 'border-red-500' : ''}
                            />
                            {errors.nombre && <p className="text-sm text-red-500 mt-1">{errors.nombre}</p>}
                        </div>
                    </div>
                    <div className="grid grid-cols-4 items-center gap-4">
                        <Label htmlFor="codigo_anexo" className="text-right">Código</Label>
                        <div className="col-span-3">
                            <Input 
                                id="codigo_anexo" 
                                value={formData.codigo_anexo} 
                                onChange={handleChange} 
                                className={errors.codigo_anexo ? 'border-red-500' : ''}
                            />
                            {errors.codigo_anexo && <p className="text-sm text-red-500 mt-1">{errors.codigo_anexo}</p>}
                        </div>
                    </div>
                    <div className="grid grid-cols-4 items-center gap-4">
                        <Label htmlFor="placeholder" className="text-right">Placeholder</Label>
                        <div className="col-span-3">
                            <Input 
                                id="placeholder" 
                                placeholder="Ej: Anexo 6"
                                value={formData.placeholder} 
                                onChange={handleChange} 
                                className={errors.placeholder ? 'border-red-500' : ''}
                            />
                            <p className="text-xs text-muted-foreground mt-1">Nombre de la variable en la plantilla DOCX (sin ${`{}`}).</p>
                            {errors.placeholder && <p className="text-sm text-red-500 mt-1">{errors.placeholder}</p>}
                        </div>
                    </div>
                    <div className="grid grid-cols-4 items-center gap-4">
                        <Label htmlFor="content_type" className="text-right">Tipo de Contenido</Label>
                        <div className="col-span-3">
                            <Select onValueChange={handleContentTypeChange} value={formData.content_type}>
                                <SelectTrigger className={errors.content_type ? 'border-red-500' : ''}><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="image">Imagen</SelectItem>
                                    <SelectItem value="text">Texto</SelectItem>
                                    <SelectItem value="table">Tabla</SelectItem>
                                    <SelectItem value="planilla">Planilla</SelectItem>
                                </SelectContent>
                            </Select>
                            <p className="text-xs text-muted-foreground mt-1">Define si el anexo contendrá una imagen, texto, tabla o planilla.</p>
                            {errors.content_type && <p className="text-sm text-red-500 mt-1">{errors.content_type}</p>}
                        </div>
                    </div>
                    {/* Selector de vista de planilla si el tipo es planilla */}
                    {formData.content_type === 'planilla' && (
                        <div className="grid grid-cols-4 items-center gap-4">
                            <Label className="text-right">Vista de Planilla</Label>
                            <div className="col-span-3 flex flex-col gap-2">
                                {planillaViews.map(view => (
                                    <div key={view.name} className="flex items-center gap-2">
                                        <Checkbox
                                            id={`planilla-view-${view.name}`}
                                            checked={formData.planilla_view === view.name}
                                            onCheckedChange={() => handlePlanillaViewChange(view.name)}
                                        />
                                        <Label htmlFor={`planilla-view-${view.name}`}>{view.label}</Label>
                                    </div>
                                ))}
                                <p className="text-xs text-muted-foreground mt-1">Selecciona la vista que corresponde a la planilla que debe llenar el usuario.</p>
                            </div>
                        </div>
                    )}
                    {/* --- FIELD CHANGED HERE --- */}
                    <div className="grid grid-cols-4 items-center gap-4">
                        <Label htmlFor="tipo" className="text-right">Tipo</Label>
                        <div className="col-span-3">
                            <Select onValueChange={handleSelectChange} defaultValue={formData.tipo}>
                                <SelectTrigger className={errors.tipo ? 'border-red-500' : ''}><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    {tipos.map(tipo => <SelectItem key={tipo} value={tipo}>{tipo}</SelectItem>)}
                                </SelectContent>
                            </Select>
                            {errors.tipo && <p className="text-sm text-red-500 mt-1">{errors.tipo}</p>}
                        </div>
                    </div>

                    {/* Table Configuration - Only show when content_type is 'table' */}
                    {formData.content_type === 'table' && (
                        <>
                            <Separator className="my-2" />
                            <div className="space-y-4">
                                <div className="flex items-center justify-between">
                                    <Label>Columnas de la Tabla</Label>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={handleAddColumn}
                                    >
                                        <Plus className="h-4 w-4 mr-1" />
                                        Agregar Columna
                                    </Button>
                                </div>

                                {formData.table_columns.length > 0 ? (
                                    <div className="space-y-2">
                                        {formData.table_columns.map((column, index) => (
                                            <div key={index} className="flex gap-2">
                                                <Input
                                                    value={column}
                                                    onChange={(e) => handleColumnChange(index, e.target.value)}
                                                    placeholder={`Nombre de columna ${index + 1}`}
                                                    className="flex-1"
                                                />
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() => handleRemoveColumn(index)}
                                                >
                                                    <X className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <p className="text-sm text-muted-foreground">No hay columnas. Haz clic en "Agregar Columna" para empezar.</p>
                                )}

                                <div className="grid grid-cols-4 items-center gap-4 mt-4">
                                    <Label htmlFor="table_header_color" className="text-right">Color de Cabecera</Label>
                                    <div className="col-span-3 flex gap-2 items-center">
                                        <Input
                                            type="color"
                                            id="table_header_color"
                                            value={formData.table_header_color}
                                            onChange={handleColorChange}
                                            className="w-20 h-10 cursor-pointer"
                                        />
                                        <Input
                                            type="text"
                                            value={formData.table_header_color}
                                            onChange={handleColorChange}
                                            placeholder="#153366"
                                            className="flex-1"
                                            maxLength={7}
                                        />
                                    </div>
                                </div>
                            </div>
                        </>
                    )}
                </div>

                <Separator />

                {/* Program Linking */}
                <div className="space-y-2 py-2">
                    <Label>Vincular a Programas</Label>
                    <div className={`max-h-48 overflow-y-auto space-y-2 rounded-md border p-4 ${errors.programIds ? 'border-red-500' : ''}`}>
                        {programs.map(program => (
                            <div key={program.id} className="flex items-center space-x-2">
                                <Checkbox
                                    id={`prog-${program.id}`}
                                    checked={formData.programIds.includes(program.id)}
                                    onCheckedChange={() => handleProgramToggle(program.id)}
                                />
                                <Label htmlFor={`prog-${program.id}`} className="font-normal cursor-pointer">
                                    {program.nombre} <span className="text-xs text-muted-foreground">({program.codigo})</span>
                                </Label>
                            </div>
                        ))}
                    </div>
                    {errors.programIds && <p className="text-sm text-red-500 mt-1">{errors.programIds}</p>}
                </div>

                <DialogFooter>
                    <Button type="submit" onClick={handleSubmit} disabled={isSubmitting}>
                            {isSubmitting ? 'Guardando...' : (annex ? 'Actualizar Anexo' : 'Guardar Anexo')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}