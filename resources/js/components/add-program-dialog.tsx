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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

const defaultFormState = {
    nombre: '',
    version: '1.0',
    codigo: '',
    fecha: new Date().toISOString().split('T')[0],
    tipo: 'ISO 22000',
    template_path: '',
    description: '',
};

const tipos = ['ISO 22000', 'PSB', 'Invima'];

type AddProgramDialogProps = {
    isOpen: boolean;
    onClose: () => void;
    onSave?: (data: any) => void;
    program?: {
        id: number;
        nombre: string;
        version: string;
        codigo: string;
        fecha: string;
        tipo: string;
        template_path?: string;
        description?: string;
    } | null;
};

export function AddProgramDialog({ isOpen, onClose, onSave, program = null }: AddProgramDialogProps) {
    const [formData, setFormData] = useState(defaultFormState);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    useEffect(() => {
        if (isOpen && program) {
            // Edit mode - populate form with program data
            setFormData({
                nombre: program.nombre || '',
                version: program.version || '1.0',
                codigo: program.codigo || '',
                fecha: program.fecha || new Date().toISOString().split('T')[0],
                tipo: program.tipo || 'ISO 22000',
                template_path: program.template_path || '',
                description: program.description || '',
            });
        } else if (!isOpen) {
            // Reset form when dialog closes
            setFormData(defaultFormState);
            setErrors({});
        }
    }, [isOpen, program]);

    const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
        const { id, value } = e.target;
        setFormData((prev) => ({ ...prev, [id]: value }));
        // Clear error when user types
        if (errors[id]) {
            setErrors((prev) => ({ ...prev, [id]: '' }));
        }
    };

    const handleSelectChange = (value: string) => {
        setFormData((prev) => ({ ...prev, tipo: value }));
    };

    const handleSubmit = () => {
        setIsSubmitting(true);
        setErrors({});

        const url = program ? `/programas/${program.id}` : '/programas';
        const method = program ? 'put' : 'post';

        router[method](url, formData, {
            preserveScroll: true,
            onSuccess: () => {
                onClose();
                setFormData(defaultFormState);
            },
            onError: (errors) => {
                setErrors(errors);
            },
            onFinish: () => {
                setIsSubmitting(false);
            }
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-2xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>{program ? 'Editar Programa' : 'Agregar Nuevo Programa'}</DialogTitle>
                    <DialogDescription>
                        {program 
                            ? 'Actualiza los campos del programa. La plantilla debe estar ubicada en storage/plantillas/'
                            : 'Completa los campos para crear un nuevo programa. La plantilla debe estar ubicada en storage/plantillas/'
                        }
                    </DialogDescription>
                </DialogHeader>
                <div className="grid gap-4 py-4">
                    <div className="grid grid-cols-4 items-center gap-4">
                        <Label htmlFor="nombre" className="text-right">Nombre</Label>
                        <div className="col-span-3">
                            <Input id="nombre" value={formData.nombre} onChange={handleChange} />
                            {errors.nombre && <p className="text-sm text-red-500 mt-1">{errors.nombre}</p>}
                        </div>
                    </div>
                    <div className="grid grid-cols-4 items-center gap-4">
                        <Label htmlFor="version" className="text-right">Versión</Label>
                        <div className="col-span-3">
                            <Input id="version" value={formData.version} onChange={handleChange} />
                            {errors.version && <p className="text-sm text-red-500 mt-1">{errors.version}</p>}
                        </div>
                    </div>
                    <div className="grid grid-cols-4 items-center gap-4">
                        <Label htmlFor="codigo" className="text-right">Código</Label>
                        <div className="col-span-3">
                            <Input id="codigo" value={formData.codigo} onChange={handleChange} placeholder="PSB-002" />
                            {errors.codigo && <p className="text-sm text-red-500 mt-1">{errors.codigo}</p>}
                        </div>
                    </div>
                     <div className="grid grid-cols-4 items-center gap-4">
                        <Label htmlFor="fecha" className="text-right">Fecha</Label>
                        <div className="col-span-3">
                            <Input id="fecha" type="date" value={formData.fecha} onChange={handleChange} />
                            {errors.fecha && <p className="text-sm text-red-500 mt-1">{errors.fecha}</p>}
                        </div>
                    </div>
                    <div className="grid grid-cols-4 items-center gap-4">
                        <Label htmlFor="tipo" className="text-right">Tipo</Label>
                        <div className="col-span-3">
                            <Select onValueChange={handleSelectChange} value={formData.tipo}>
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    {tipos.map(tipo => <SelectItem key={tipo} value={tipo}>{tipo}</SelectItem>)}
                                </SelectContent>
                            </Select>
                            {errors.tipo && <p className="text-sm text-red-500 mt-1">{errors.tipo}</p>}
                        </div>
                    </div>
                    
                    <div className="col-span-4">
                        <div className="border-t pt-4 mt-2">
                            <h4 className="text-sm font-semibold mb-3">Configuración de Plantilla</h4>
                            
                            <div className="grid grid-cols-4 items-center gap-4 mb-4">
                                <Label htmlFor="template_path" className="text-right">
                                    Ruta Plantilla
                                </Label>
                                <div className="col-span-3">
                                    <Input 
                                        id="template_path" 
                                        value={formData.template_path} 
                                        onChange={handleChange}
                                        placeholder="nombreCarpeta/Plantilla.docx"
                                        className="font-mono text-sm"
                                    />
                                    <p className="text-xs text-muted-foreground mt-1">
                                        Ejemplo: planDeSaneamientoBasico/Plantilla.docx
                                    </p>
                                    {errors.template_path && <p className="text-sm text-red-500 mt-1">{errors.template_path}</p>}
                                </div>
                            </div>
                            
                            <div className="grid grid-cols-4 items-start gap-4">
                                <Label htmlFor="description" className="text-right pt-2">
                                    Descripción
                                </Label>
                                <div className="col-span-3">
                                    <Textarea 
                                        id="description" 
                                        value={formData.description} 
                                        onChange={handleChange}
                                        placeholder="Describe el propósito de este documento y qué anexos requiere..."
                                        rows={3}
                                        className="resize-none"
                                    />
                                    <p className="text-xs text-muted-foreground mt-1">
                                        Esta descripción ayudará a identificar el documento
                                    </p>
                                    {errors.description && <p className="text-sm text-red-500 mt-1">{errors.description}</p>}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" onClick={onClose} disabled={isSubmitting}>Cancelar</Button>
                    <Button type="submit" onClick={handleSubmit} disabled={isSubmitting}>
                        {isSubmitting ? 'Guardando...' : (program ? 'Actualizar Programa' : 'Guardar Programa')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}