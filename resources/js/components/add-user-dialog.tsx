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
import { Separator } from './ui/separator';
import { Avatar, AvatarFallback, AvatarImage } from './ui/avatar';


// Companies will be provided from server via props

const defaultFormState = {
    nombre: '',
    username: '',
    password: '',
    correo: '',
    rol: 'analista',
    empresasAsociadas: [],
};

export function AddUserDialog({ isOpen, onClose, onSaveUser, userToEdit, companies = [] }) {
    const [formData, setFormData] = useState(defaultFormState);
    const [companySearch, setCompanySearch] = useState('');

    useEffect(() => {
        if (userToEdit) {
            setFormData({
                nombre: userToEdit.nombre || '',
                username: userToEdit.username || '',
                correo: userToEdit.correo || '',
                rol: userToEdit.rol || 'Analista',
                empresasAsociadas: userToEdit.empresasAsociadas || [],
                password: '', // Password should be empty for editing
            });
        } else {
            setFormData(defaultFormState);
        }
    }, [userToEdit, isOpen]);

    const handleChange = (e) => {
        const { id, value } = e.target;
        setFormData((prev) => ({ ...prev, [id]: value }));
    };

    const handleSelectChange = (value) => {
        setFormData((prev) => ({ ...prev, rol: value }));
    };

    const handleSubmit = () => {
        onSaveUser(formData);
    };

    const toggleEmpresa = (empresaId) => {
        const newEmpresas = formData.empresasAsociadas.includes(empresaId)
            ? formData.empresasAsociadas.filter(id => id !== empresaId)
            : [...formData.empresasAsociadas, empresaId];
        setFormData(prev => ({ ...prev, empresasAsociadas: newEmpresas }));
    };

    const filteredCompanies = companies.filter((c: any) =>
        c.nombre.toLowerCase().includes(companySearch.toLowerCase()) ||
        (c.nit ?? '').toLowerCase().includes(companySearch.toLowerCase())
    );

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{userToEdit ? 'Editar Usuario' : 'Agregar Nuevo Usuario'}</DialogTitle>
                    <DialogDescription>
                        {userToEdit ? 'Modifica los datos del usuario.' : 'Completa los campos para agregar un nuevo usuario.'}
                    </DialogDescription>
                </DialogHeader>
                <div className="grid gap-4 py-4">
                    {/* Base Form Fields */}
                    <div className="grid grid-cols-4 items-center gap-4">
                        <Label htmlFor="nombre" className="text-right">Nombre</Label>
                        <Input id="nombre" value={formData.nombre} onChange={handleChange} className="col-span-3" />
                    </div>
                    <div className="grid grid-cols-4 items-center gap-4">
                        <Label htmlFor="username" className="text-right">Usuario</Label>
                        <Input id="username" value={formData.username} onChange={handleChange} className="col-span-3" />
                    </div>
                    <div className="grid grid-cols-4 items-center gap-4">
                        <Label htmlFor="password" className="text-right">Contraseña</Label>
                        <Input id="password" type="password" value={formData.password} onChange={handleChange} className="col-span-3" placeholder={userToEdit ? 'Dejar en blanco para no cambiar' : ''} />
                    </div>
                    <div className="grid grid-cols-4 items-center gap-4">
                        <Label htmlFor="correo" className="text-right">Correo</Label>
                        <Input id="correo" type="email" value={formData.correo} onChange={handleChange} className="col-span-3" />
                    </div>
                    <div className="grid grid-cols-4 items-center gap-4">
                        <Label htmlFor="rol" className="text-right">Rol</Label>
                        <Select onValueChange={handleSelectChange} value={formData.rol}>
                            <SelectTrigger className="col-span-3"><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="Usuario">Usuario</SelectItem>
                                <SelectItem value="Analista">Analista</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                {/* Conditional Section for "Analista" role */}
                {['admin'].includes(String(formData.rol).toLowerCase()) && (
                    <>
                        <Separator />
                        <div className="space-y-4 pt-4">
                            <div>
                                <h4 className="font-medium">Asignar Empresas</h4>
                                <p className="text-sm text-muted-foreground">Habilita las empresas que este analista puede gestionar.</p>
                            </div>
                            <Input
                                placeholder="Buscar empresa por nombre o NIT..."
                                value={companySearch}
                                onChange={e => setCompanySearch(e.target.value)}
                            />
                            <div className="max-h-48 overflow-y-auto space-y-2 pr-2">
                                {filteredCompanies.map(empresa => {
                                    const isEnabled = formData.empresasAsociadas.includes(empresa.id);
                                    return (
                                        <div key={empresa.id} className="flex items-center justify-between p-2 border rounded-md">
                                            <div className="flex items-center gap-3">
                                                <Avatar>
                                                    <AvatarImage src={empresa.logo} alt={empresa.nombre} />
                                                    <AvatarFallback>{empresa.nombre.substring(0, 2)}</AvatarFallback>
                                                </Avatar>
                                                <div>
                                                    <p className="font-semibold">{empresa.nombre}</p>
                                                    <p className="text-xs text-muted-foreground">{empresa.nit}</p>
                                                </div>
                                            </div>
                                            <Button
                                                variant={isEnabled ? 'secondary' : 'outline'}
                                                size="sm"
                                                onClick={() => toggleEmpresa(empresa.id)}
                                            >
                                                {isEnabled ? 'Deshabilitar' : 'Habilitar'}
                                            </Button>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    </>
                )}

                <DialogFooter>
                    <Button type="submit" onClick={handleSubmit}>Guardar Cambios</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
