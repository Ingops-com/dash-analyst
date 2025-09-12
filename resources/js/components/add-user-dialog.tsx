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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

export function AddUserDialog({ isOpen, onClose, onAddUser }) {
    const [formData, setFormData] = useState({
        nombre: '',
        username: '',
        password: '',
        correo: '',
        rol: 'Analista',
    });

    const handleChange = (e) => {
        const { id, value } = e.target;
        setFormData((prev) => ({ ...prev, [id]: value }));
    };

    const handleSelectChange = (value) => {
        setFormData((prev) => ({ ...prev, rol: value }));
    };

    const handleSubmit = () => {
        onAddUser(formData);
        onClose();
        // Reset form after submission
        setFormData({
            nombre: '',
            username: '',
            password: '',
            correo: '',
            rol: 'Analista',
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Agregar Nuevo Usuario</DialogTitle>
                    <DialogDescription>
                        Completa los campos para agregar un nuevo usuario al sistema.
                    </DialogDescription>
                </DialogHeader>
                <div className="grid gap-4 py-4">
                    <div className="grid grid-cols-4 items-center gap-4">
                        <Label htmlFor="nombre" className="text-right">
                            Nombre
                        </Label>
                        <Input id="nombre" value={formData.nombre} onChange={handleChange} className="col-span-3" />
                    </div>
                    <div className="grid grid-cols-4 items-center gap-4">
                        <Label htmlFor="username" className="text-right">
                            Usuario
                        </Label>
                        <Input id="username" value={formData.username} onChange={handleChange} className="col-span-3" />
                    </div>
                    <div className="grid grid-cols-4 items-center gap-4">
                        <Label htmlFor="password" name="password" className="text-right">
                            Contraseña
                        </Label>
                        <Input id="password" type="password" value={formData.password} onChange={handleChange} className="col-span-3" />
                    </div>
                    <div className="grid grid-cols-4 items-center gap-4">
                        <Label htmlFor="correo" className="text-right">
                            Correo
                        </Label>
                        <Input id="correo" type="email" value={formData.correo} onChange={handleChange} className="col-span-3" />
                    </div>
                    <div className="grid grid-cols-4 items-center gap-4">
                        <Label htmlFor="rol" className="text-right">
                            Rol
                        </Label>
                        <Select onValueChange={handleSelectChange} defaultValue={formData.rol}>
                            <SelectTrigger className="col-span-3">
                                <SelectValue placeholder="Selecciona un rol" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="Administrador">Administrador</SelectItem>
                                <SelectItem value="Analista">Analista</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
                <DialogFooter>
                    <Button type="submit" onClick={handleSubmit}>Guardar Usuario</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}