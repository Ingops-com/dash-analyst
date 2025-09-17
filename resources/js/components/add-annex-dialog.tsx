import { useState } from 'react';
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


const defaultFormState = {
    nombre: '',
    codigo_anexo: '',
    tipo: 'ISO 22000', // Changed from 'categoria'
    programIds: [],
};

const tipos = ['ISO 22000', 'PSB', 'Invima'];

export function AddAnnexDialog({ isOpen, onClose, onSave, programs }) {
    const [formData, setFormData] = useState(defaultFormState);

    const handleChange = (e) => {
        const { id, value } = e.target;
        setFormData((prev) => ({ ...prev, [id]: value }));
    };

    const handleSelectChange = (value) => {
        setFormData((prev) => ({ ...prev, tipo: value }));
    };

    const handleProgramToggle = (programId) => {
        setFormData(prev => {
            const newProgramIds = prev.programIds.includes(programId)
                ? prev.programIds.filter(id => id !== programId)
                : [...prev.programIds, programId];
            return { ...prev, programIds: newProgramIds };
        });
    };

    const handleSubmit = () => {
        onSave(formData);
        onClose();
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Agregar Nuevo Anexo</DialogTitle>
                    <DialogDescription>
                        Define el anexo y vincúlalo a uno o más programas.
                    </DialogDescription>
                </DialogHeader>

                {/* Annex Details */}
                <div className="grid gap-4 py-2">
                    <div className="grid grid-cols-4 items-center gap-4">
                        <Label htmlFor="nombre" className="text-right">Nombre</Label>
                        <Input id="nombre" value={formData.nombre} onChange={handleChange} className="col-span-3" />
                    </div>
                    <div className="grid grid-cols-4 items-center gap-4">
                        <Label htmlFor="codigo_anexo" className="text-right">Código</Label>
                        <Input id="codigo_anexo" value={formData.codigo_anexo} onChange={handleChange} className="col-span-3" />
                    </div>
                    {/* --- FIELD CHANGED HERE --- */}
                    <div className="grid grid-cols-4 items-center gap-4">
                        <Label htmlFor="tipo" className="text-right">Tipo</Label>
                         <Select onValueChange={handleSelectChange} defaultValue={formData.tipo}>
                            <SelectTrigger className="col-span-3"><SelectValue /></SelectTrigger>
                            <SelectContent>
                                {tipos.map(tipo => <SelectItem key={tipo} value={tipo}>{tipo}</SelectItem>)}
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <Separator />

                {/* Program Linking */}
                <div className="space-y-2 py-2">
                    <Label>Vincular a Programas</Label>
                    <div className="max-h-48 overflow-y-auto space-y-2 rounded-md border p-4">
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
                </div>

                <DialogFooter>
                    <Button type="submit" onClick={handleSubmit}>Guardar Anexo</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}