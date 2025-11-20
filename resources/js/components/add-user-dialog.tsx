import { useState, useEffect, ChangeEvent } from 'react';
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
import { Switch } from '@/components/ui/switch';

type CompanyOption = { id: number; nombre: string; nit?: string; logo?: string };
type PermissionState = {
    can_view_annexes: boolean;
    can_upload_annexes: boolean;
    can_delete_annexes: boolean;
    can_generate_documents: boolean;
};
type FormState = {
    nombre: string;
    username: string;
    password: string;
    correo: string;
    rol: string;
    empresasAsociadas: number[];
};
type AddUserDialogProps = {
    isOpen: boolean;
    onClose: () => void;
    onSaveUser: (data: any) => void;
    userToEdit?: any;
    companies?: CompanyOption[];
};

const defaultFormState: FormState = {
    nombre: '',
    username: '',
    password: '',
    correo: '',
    rol: 'analista',
    empresasAsociadas: [],
};

const buildDefaultPermissions = (): PermissionState => ({
    can_view_annexes: true,
    can_upload_annexes: false,
    can_delete_annexes: false,
    can_generate_documents: false,
});

export function AddUserDialog({ isOpen, onClose, onSaveUser, userToEdit, companies = [] }: AddUserDialogProps) {
    const [formData, setFormData] = useState<FormState>(defaultFormState);
    const [companySearch, setCompanySearch] = useState('');
    const [companyPermissions, setCompanyPermissions] = useState<Record<number, PermissionState>>({});

    useEffect(() => {
        if (userToEdit) {
            setFormData({
                nombre: userToEdit.nombre || '',
                username: userToEdit.username || '',
                correo: userToEdit.correo || '',
                rol: userToEdit.rol || 'analista',
                empresasAsociadas: (userToEdit.empresasAsociadas || []).map((id: number) => Number(id)),
                password: '',
            });
            const incoming = (userToEdit.permisos || {}) as Record<string, PermissionState>;
            const normalized: Record<number, PermissionState> = {};
            Object.entries(incoming).forEach(([companyId, perms]) => {
                normalized[Number(companyId)] = {
                    ...buildDefaultPermissions(),
                    ...(perms ?? {}),
                };
            });
            setCompanyPermissions(normalized);
        } else {
            setFormData(defaultFormState);
            setCompanyPermissions({});
        }
    }, [userToEdit, isOpen]);

    useEffect(() => {
        setCompanyPermissions((prev) => {
            const next = { ...prev };
            const assigned = new Set(formData.empresasAsociadas.map((id) => Number(id)));
            assigned.forEach((companyId) => {
                if (!next[companyId]) {
                    next[companyId] = buildDefaultPermissions();
                }
            });
            Object.keys(next).forEach((companyId) => {
                if (!assigned.has(Number(companyId))) {
                    delete next[Number(companyId)];
                }
            });
            return next;
        });
    }, [formData.empresasAsociadas]);

    const handleChange = (e: ChangeEvent<HTMLInputElement>) => {
        const { id, value } = e.target;
        setFormData((prev) => ({
            ...prev,
            [id as keyof FormState]: value,
        }) as FormState);
    };

    const handleSelectChange = (value: string) => {
        setFormData((prev) => ({ ...prev, rol: value }));
    };

    const handleSubmit = () => {
        onSaveUser({ ...formData, permisos: companyPermissions });
    };

    const toggleEmpresa = (empresaId: number) => {
        const isUserRole = String(formData.rol).toLowerCase() === 'usuario';
        let newEmpresas: number[] = [];
        if (isUserRole) {
            newEmpresas = formData.empresasAsociadas.includes(empresaId) ? [] : [empresaId];
        } else {
            newEmpresas = formData.empresasAsociadas.includes(empresaId)
                ? formData.empresasAsociadas.filter((id) => id !== empresaId)
                : [...formData.empresasAsociadas, empresaId];
        }
        setFormData((prev) => ({ ...prev, empresasAsociadas: newEmpresas }));
    };

    const filteredCompanies = (companies || []).filter((c) =>
        c.nombre.toLowerCase().includes(companySearch.toLowerCase()) ||
        (c.nit ?? '').toLowerCase().includes(companySearch.toLowerCase())
    );

    const handlePermissionChange = (companyId: number, key: keyof PermissionState, value: boolean) => {
        setCompanyPermissions((prev) => {
            const current = { ...buildDefaultPermissions(), ...(prev[companyId] || {}) };
            const updated: PermissionState = { ...current };

            if (key === 'can_view_annexes') {
                updated.can_view_annexes = value;
                if (!value) {
                    updated.can_upload_annexes = false;
                    updated.can_delete_annexes = false;
                    updated.can_generate_documents = false;
                }
            } else if (key === 'can_upload_annexes') {
                updated.can_upload_annexes = value && updated.can_view_annexes;
                if (!updated.can_upload_annexes) {
                    updated.can_delete_annexes = false;
                }
            } else if (key === 'can_delete_annexes') {
                updated.can_delete_annexes = value && updated.can_upload_annexes && updated.can_view_annexes;
            } else if (key === 'can_generate_documents') {
                updated.can_generate_documents = value && updated.can_view_annexes;
            }

            return { ...prev, [companyId]: updated };
        });
    };

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
                                <SelectItem value="usuario">Usuario</SelectItem>
                                <SelectItem value="analista">Analista</SelectItem>
                                <SelectItem value="admin">Admin</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                {String(formData.rol).toLowerCase() !== 'super-admin' && (
                    <>
                        <Separator />
                        <div className="space-y-4 pt-4">
                            <div>
                                <h4 className="font-medium">Asignar Empresas</h4>
                                <p className="text-sm text-muted-foreground">Habilita las empresas que este usuario puede gestionar.</p>
                            </div>
                            <Input
                                placeholder="Buscar empresa por nombre o NIT..."
                                value={companySearch}
                                onChange={(e) => setCompanySearch(e.target.value)}
                            />
                            <div className="max-h-48 overflow-y-auto space-y-2 pr-2">
                                {filteredCompanies.map((empresa) => {
                                    const isEnabled = formData.empresasAsociadas.includes(empresa.id);
                                    return (
                                        <div key={empresa.id} className="flex items-center justify-between p-2 border rounded-md">
                                            <div className="flex items-center gap-3">
                                                <Avatar>
                                                    <AvatarImage src={empresa.logo} alt={empresa.nombre} />
                                                    <AvatarFallback>{empresa.nombre.substring(0, 2)}</AvatarFallback>
                                                </Avatar>
                                                <div>
                                                    <p className="font-semibold truncate max-w-[200px]" title={empresa.nombre}>{empresa.nombre}</p>
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

                {formData.empresasAsociadas.length > 0 && (
                    <>
                        <Separator />
                        <div className="space-y-4 pt-4">
                            <div>
                                <h4 className="font-medium">Permisos por Empresa</h4>
                                <p className="text-sm text-muted-foreground">Define qué puede hacer este usuario en cada empresa asignada.</p>
                            </div>
                            <div className="space-y-3">
                                {formData.empresasAsociadas.map((companyId) => {
                                    const empresa = companies.find((c) => c.id === companyId);
                                    const perms = companyPermissions[companyId] || buildDefaultPermissions();
                                    return (
                                        <div key={companyId} className="border rounded-md p-3 space-y-2">
                                            <div className="flex items-center justify-between">
                                                <div>
                                                    <p className="font-semibold">{empresa?.nombre ?? `Empresa ${companyId}`}</p>
                                                    <p className="text-xs text-muted-foreground">{empresa?.nit}</p>
                                                </div>
                                            </div>
                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-2">
                                                <label className="flex items-center justify-between border rounded-md px-3 py-2 text-sm">
                                                    <span>Ver anexos</span>
                                                    <Switch checked={perms.can_view_annexes} onCheckedChange={(checked) => handlePermissionChange(companyId, 'can_view_annexes', checked)} />
                                                </label>
                                                <label className="flex items-center justify-between border rounded-md px-3 py-2 text-sm">
                                                    <span>Subir anexos</span>
                                                    <Switch checked={perms.can_upload_annexes} disabled={!perms.can_view_annexes} onCheckedChange={(checked) => handlePermissionChange(companyId, 'can_upload_annexes', checked)} />
                                                </label>
                                                <label className="flex items-center justify-between border rounded-md px-3 py-2 text-sm">
                                                    <span>Eliminar anexos</span>
                                                    <Switch checked={perms.can_delete_annexes} disabled={!perms.can_upload_annexes} onCheckedChange={(checked) => handlePermissionChange(companyId, 'can_delete_annexes', checked)} />
                                                </label>
                                                <label className="flex items-center justify-between border rounded-md px-3 py-2 text-sm">
                                                    <span>Generar documento</span>
                                                    <Switch checked={perms.can_generate_documents} disabled={!perms.can_view_annexes} onCheckedChange={(checked) => handlePermissionChange(companyId, 'can_generate_documents', checked)} />
                                                </label>
                                            </div>
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
