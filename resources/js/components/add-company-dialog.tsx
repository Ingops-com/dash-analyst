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
import { Textarea } from '@/components/ui/textarea';
import { Separator } from './ui/separator';

const defaultFormState = {
    nombre: '',
    nit_empresa: '',
    correo: '',
    direccion: '',
    telefono: '',
    representante_legal: '',
    encargado_sgc: '',
    version: '',
    fecha_inicio: '',
    fecha_verificacion: '',
    actividades: '',
    logoIzquierdo: null,
    logoDerecho: null,
    logoPieDePagina: null,
};

// A small component to make the logo upload section cleaner
const LogoUploader = ({ id, label, onChange }) => (
    <div className="flex flex-col items-center gap-2 p-4 border border-dashed rounded-lg">
        <Label htmlFor={id} className="text-sm font-medium">{label}</Label>
        <Input id={id} type="file" onChange={onChange} className="text-xs file:mr-2 file:py-1 file:px-2 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-primary-foreground file:text-primary hover:file:bg-primary/20" accept="image/png, image/jpeg, image/jpg" />
    </div>
);

export function AddCompanyDialog({ isOpen, onClose, onSaveCompany, companyToEdit }) {
    const [formData, setFormData] = useState(defaultFormState);

    useEffect(() => {
        if (companyToEdit) {
            setFormData({
                nombre: companyToEdit.nombre || '',
                nit_empresa: companyToEdit.nit_empresa || '',
                correo: companyToEdit.correo || '',
                direccion: companyToEdit.direccion || '',
                telefono: companyToEdit.telefono || '',
                representante_legal: companyToEdit.representante_legal || '',
                encargado_sgc: companyToEdit.encargado_sgc || '',
                version: companyToEdit.version || '',
                fecha_inicio: companyToEdit.fecha_inicio || '',
                fecha_verificacion: companyToEdit.fecha_verificacion || '',
                actividades: companyToEdit.actividades || '',
                ...defaultFormState // Reset logos
            });
        } else {
            setFormData(defaultFormState);
        }
    }, [companyToEdit, isOpen]);

    const handleChange = (e) => {
        const { id, value } = e.target;
        setFormData((prev) => ({ ...prev, [id]: value }));
    };

    const handleFileChange = (e) => {
        const { id, files } = e.target;
        setFormData((prev) => ({ ...prev, [id]: files[0] }));
    }

    const handleSubmit = () => {
        onSaveCompany(formData);
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-4xl">
                <DialogHeader>
                    <DialogTitle>{companyToEdit ? 'Editar Empresa' : 'Agregar Nueva Empresa'}</DialogTitle>
                    <DialogDescription>
                        Completa la información de la empresa.
                    </DialogDescription>
                </DialogHeader>

                {/* Logo Upload Section */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4 pt-4">
                    <LogoUploader id="logoIzquierdo" label="Logo Izquierdo" onChange={handleFileChange} />
                    <LogoUploader id="logoDerecho" label="Logo Derecho" onChange={handleFileChange} />
                    <LogoUploader id="logoPieDePagina" label="Logo Pie de Página" onChange={handleFileChange} />
                </div>

                <Separator className="my-4" />

                {/* Form Fields Section */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-4">
                    {/* Column 1 */}
                    <div className="space-y-4">
                        <div>
                            <Label htmlFor="nombre">Razón Social</Label>
                            <Input id="nombre" value={formData.nombre} onChange={handleChange} />
                        </div>
                        <div>
                            <Label htmlFor="nit_empresa">NIT</Label>
                            <Input id="nit_empresa" value={formData.nit_empresa} onChange={handleChange} />
                        </div>
                        <div>
                            <Label htmlFor="correo">Correo</Label>
                            <Input id="correo" type="email" value={formData.correo} onChange={handleChange} />
                        </div>
                    </div>

                    {/* Column 2 */}
                    <div className="space-y-4">
                        <div>
                            <Label htmlFor="direccion">Dirección</Label>
                            <Input id="direccion" value={formData.direccion} onChange={handleChange} />
                        </div>
                        <div>
                            <Label htmlFor="telefono">Teléfono</Label>
                            <Input id="telefono" value={formData.telefono} onChange={handleChange} />
                        </div>
                        <div>
                            <Label htmlFor="representante_legal">Representante Legal</Label>
                            <Input id="representante_legal" value={formData.representante_legal} onChange={handleChange} />
                        </div>
                    </div>

                    {/* Column 3 - CORRECTION APPLIED HERE */}
                    <div className="space-y-4">
                        <div>
                            <Label htmlFor="encargado_sgc">Encargado SGC</Label>
                            <Input id="encargado_sgc" value={formData.encargado_sgc} onChange={handleChange} />
                        </div>
                        <div>
                            <Label htmlFor="version">Versión</Label>
                            <Input id="version" value={formData.version} onChange={handleChange} />
                        </div>
                         <div>
                            <Label htmlFor="fecha_inicio">Fecha Inicio</Label>
                            <Input id="fecha_inicio" type="date" value={formData.fecha_inicio} onChange={handleChange} />
                        </div>
                        <div>
                            <Label htmlFor="fecha_verificacion">Fecha Verif.</Label>
                            <Input id="fecha_verificacion" type="date" value={formData.fecha_verificacion} onChange={handleChange} />
                        </div>
                    </div>

                    {/* Full-width Activities */}
                    <div className="md:col-span-3">
                         <Label htmlFor="actividades">Actividades</Label>
                         <Textarea id="actividades" value={formData.actividades} onChange={handleChange} rows={3}/>
                    </div>
                </div>

                <DialogFooter className="pt-4">
                    <Button type="submit" onClick={handleSubmit}>Guardar Empresa</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}