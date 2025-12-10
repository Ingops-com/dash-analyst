import { useState, useEffect } from "react";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";

interface Persona {
    nombre: string;
    cedula: string;
    firma?: string;
}

interface TemplateActaDeReunionProps {
    initialData?: any;
    onSave: (data: any) => void;
    readOnly?: boolean;
}

export function TemplateActaDeReunion({ initialData, onSave, readOnly = false }: TemplateActaDeReunionProps) {
    const [formData, setFormData] = useState<any>({
        fecha: '',
        horaInicio: '',
        horaFinalizacion: '',
        lugar: '',
        temas: '',
        personas: [{ nombre: '', cedula: '', firma: '' }],
        encargado: '',
        observaciones: ''
    });

    useEffect(() => {
        if (initialData) {
            setFormData(initialData);
        }
    }, [initialData]);

    const handleInputChange = (field: string, value: any) => {
        setFormData({ ...formData, [field]: value });
    };

    const handlePersonaChange = (index: number, field: string, value: string) => {
        const newPersonas = [...formData.personas];
        newPersonas[index][field] = value;
        setFormData({ ...formData, personas: newPersonas });
    };

    const handleAddPersona = () => {
        setFormData({
            ...formData,
            personas: [...formData.personas, { nombre: '', cedula: '', firma: '' }]
        });
    };

    const handleRemovePersona = (index: number) => {
        if (formData.personas.length > 1) {
            setFormData({
                ...formData,
                personas: formData.personas.filter((_: any, i: number) => i !== index)
            });
        }
    };

    const handleSave = () => {
        onSave(formData);
    };

    return (
        <div className="space-y-6 p-6 bg-white rounded-lg border">
            <h2 className="text-2xl font-bold">Acta de Reunión</h2>

            {/* Información General */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div className="space-y-2">
                    <label className="text-sm font-medium">Fecha</label>
                    <Input
                        type="date"
                        value={formData.fecha || ''}
                        onChange={(e) => handleInputChange('fecha', e.target.value)}
                        readOnly={readOnly}
                    />
                </div>
                <div className="space-y-2">
                    <label className="text-sm font-medium">Hora de Inicio</label>
                    <Input
                        type="time"
                        value={formData.horaInicio || ''}
                        onChange={(e) => handleInputChange('horaInicio', e.target.value)}
                        readOnly={readOnly}
                    />
                </div>
                <div className="space-y-2">
                    <label className="text-sm font-medium">Hora de Finalización</label>
                    <Input
                        type="time"
                        value={formData.horaFinalizacion || ''}
                        onChange={(e) => handleInputChange('horaFinalizacion', e.target.value)}
                        readOnly={readOnly}
                    />
                </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="space-y-2">
                    <label className="text-sm font-medium">Lugar de la Reunión</label>
                    <Input
                        type="text"
                        value={formData.lugar || ''}
                        onChange={(e) => handleInputChange('lugar', e.target.value)}
                        placeholder="Ubicación de la reunión"
                        readOnly={readOnly}
                    />
                </div>
                <div className="space-y-2">
                    <label className="text-sm font-medium">Encargado/Moderador</label>
                    <Input
                        type="text"
                        value={formData.encargado || ''}
                        onChange={(e) => handleInputChange('encargado', e.target.value)}
                        placeholder="Nombre del encargado"
                        readOnly={readOnly}
                    />
                </div>
            </div>

            {/* Temas */}
            <div className="space-y-2">
                <label className="text-sm font-medium">Temas a Tratar</label>
                <Textarea
                    value={formData.temas || ''}
                    onChange={(e) => handleInputChange('temas', e.target.value)}
                    placeholder="Descripción de los temas tratados..."
                    className="h-24"
                    readOnly={readOnly}
                />
            </div>

            {/* Asistentes */}
            <div className="space-y-3">
                <h3 className="text-lg font-semibold">Asistentes</h3>
                
                <div className="overflow-auto border rounded-lg">
                    <table className="w-full">
                        <thead>
                            <tr className="bg-blue-100">
                                <th className="border p-3 text-left">Nombre</th>
                                <th className="border p-3 text-left">Cédula</th>
                                <th className="border p-3 text-left">Firma</th>
                                {!readOnly && <th className="border p-3 text-center">Acciones</th>}
                            </tr>
                        </thead>
                        <tbody>
                            {formData.personas?.map((persona: Persona, idx: number) => (
                                <tr key={idx}>
                                    <td className="border p-3">
                                        <Input
                                            type="text"
                                            value={persona.nombre || ''}
                                            onChange={(e) => handlePersonaChange(idx, 'nombre', e.target.value)}
                                            placeholder="Nombre completo"
                                            className="h-8 text-sm"
                                            readOnly={readOnly}
                                        />
                                    </td>
                                    <td className="border p-3">
                                        <Input
                                            type="text"
                                            value={persona.cedula || ''}
                                            onChange={(e) => handlePersonaChange(idx, 'cedula', e.target.value)}
                                            placeholder="Número de cédula"
                                            className="h-8 text-sm"
                                            readOnly={readOnly}
                                        />
                                    </td>
                                    <td className="border p-3">
                                        <Input
                                            type="text"
                                            value={persona.firma || ''}
                                            onChange={(e) => handlePersonaChange(idx, 'firma', e.target.value)}
                                            placeholder="Firma"
                                            className="h-8 text-sm"
                                            readOnly={readOnly}
                                        />
                                    </td>
                                    {!readOnly && (
                                        <td className="border p-3 text-center">
                                            <Button
                                                onClick={() => handleRemovePersona(idx)}
                                                variant="ghost"
                                                size="sm"
                                                disabled={formData.personas.length === 1}
                                            >
                                                Eliminar
                                            </Button>
                                        </td>
                                    )}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {!readOnly && (
                    <Button onClick={handleAddPersona} variant="outline" className="w-full">
                        Agregar Asistente
                    </Button>
                )}
            </div>

            {/* Observaciones */}
            <div className="space-y-2">
                <label className="text-sm font-medium">Observaciones Adicionales</label>
                <Textarea
                    value={formData.observaciones || ''}
                    onChange={(e) => handleInputChange('observaciones', e.target.value)}
                    placeholder="Cualquier comentario adicional..."
                    className="h-20"
                    readOnly={readOnly}
                />
            </div>

            {/* Botón de guardar */}
            {!readOnly && (
                <Button onClick={handleSave} className="w-full">
                    Guardar Acta
                </Button>
            )}
        </div>
    );
}

export default TemplateActaDeReunion;
