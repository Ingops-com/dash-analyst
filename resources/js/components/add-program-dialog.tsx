import { useState, useEffect } from 'react';
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

const defaultFormState = {
    nombre: '',
    version: '',
    codigo: '',
    fecha: '',
    tipo: 'ISO 22000',
};

const tipos = ['ISO 22000', 'PSB', 'Invima'];

export function AddProgramDialog({ isOpen, onClose, onSave }) {
    const [formData, setFormData] = useState(defaultFormState);

    const handleChange = (e) => {
        const { id, value } = e.target;
        setFormData((prev) => ({ ...prev, [id]: value }));
    };

    const handleSelectChange = (value) => {
        setFormData((prev) => ({ ...prev, tipo: value }));
    };

    const handleSubmit = () => {
        onSave(formData);
        onClose(); // Close dialog after save
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Agregar Nuevo Programa</DialogTitle>
                    <DialogDescription>
                        Completa los campos para crear un nuevo programa.
                    </DialogDescription>
                </DialogHeader>
                <div className="grid gap-4 py-4">
                    <div className="grid grid-cols-4 items-center gap-4">
                        <Label htmlFor="nombre" className="text-right">Nombre</Label>
                        <Input id="nombre" value={formData.nombre} onChange={handleChange} className="col-span-3" />
                    </div>
                    <div className="grid grid-cols-4 items-center gap-4">
                        <Label htmlFor="version" className="text-right">Versión</Label>
                        <Input id="version" value={formData.version} onChange={handleChange} className="col-span-3" />
                    </div>
                    <div className="grid grid-cols-4 items-center gap-4">
                        <Label htmlFor="codigo" className="text-right">Código</Label>
                        <Input id="codigo" value={formData.codigo} onChange={handleChange} className="col-span-3" />
                    </div>
                     <div className="grid grid-cols-4 items-center gap-4">
                        <Label htmlFor="fecha" className="text-right">Fecha</Label>
                        <Input id="fecha" type="date" value={formData.fecha} onChange={handleChange} className="col-span-3" />
                    </div>
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
                <DialogFooter>
                    <Button type="submit" onClick={handleSubmit}>Guardar Programa</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}