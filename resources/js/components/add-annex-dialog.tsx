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
        tipo: string;
        programIds?: number[];
    } | null;
}

const defaultFormState = {
    nombre: '',
    codigo_anexo: '',
    placeholder: '',
    tipo: 'ISO 22000',
    programIds: [] as number[],
};

const tipos = ['ISO 22000', 'PSB', 'Invima'];

export function AddAnnexDialog({ isOpen, onClose, programs, annex = null }: AddAnnexDialogProps) {
    const [formData, setFormData] = useState(defaultFormState);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    useEffect(() => {
        if (isOpen && annex) {
            // Edit mode - populate form with annex data
            setFormData({
                nombre: annex.nombre || '',
                codigo_anexo: annex.codigo_anexo || '',
                placeholder: annex.placeholder || '',
                tipo: annex.tipo || 'ISO 22000',
                programIds: annex.programIds || [],
            });
        } else if (!isOpen) {
            // Reset form when dialog closes
            setFormData(defaultFormState);
            setErrors({});
        }
    }, [isOpen, annex]);

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

    const handleSubmit = () => {
        setIsSubmitting(true);
        setErrors({});

            const url = annex ? `/anexos/${annex.id}` : '/anexos';
            const method = annex ? 'put' : 'post';

            router[method](url, formData, {
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