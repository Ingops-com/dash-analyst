import { useState, useEffect } from "react";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";

interface Producto {
    nombre: string;
    cantidad: number;
    lote: string;
    razon: string;
}

interface TemplateActaDesnaturalizacionProps {
    initialData?: any;
    onSave: (data: any) => void;
    readOnly?: boolean;
}

export function TemplateActaDesnaturalizacion({ initialData, onSave, readOnly = false }: TemplateActaDesnaturalizacionProps) {
    const [formData, setFormData] = useState<any>({
        fecha: '',
        responsable: '',
        lugar: '',
        productos: [{ nombre: '', cantidad: 0, lote: '', razon: '' }],
        metodoDesnaturalizacion: '',
        observaciones: '',
        testigos: []
    });
    const [newTestigo, setNewTestigo] = useState('');

    useEffect(() => {
        if (initialData) {
            setFormData(initialData);
        }
    }, [initialData]);

    const handleInputChange = (field: string, value: any) => {
        setFormData({ ...formData, [field]: value });
    };

    const handleProductoChange = (index: number, field: string, value: any) => {
        const newProductos = [...formData.productos];
        newProductos[index][field] = value;
        setFormData({ ...formData, productos: newProductos });
    };

    const handleAddProducto = () => {
        setFormData({
            ...formData,
            productos: [...formData.productos, { nombre: '', cantidad: 0, lote: '', razon: '' }]
        });
    };

    const handleRemoveProducto = (index: number) => {
        if (formData.productos.length > 1) {
            setFormData({
                ...formData,
                productos: formData.productos.filter((_: any, i: number) => i !== index)
            });
        }
    };

    const handleAddTestigo = () => {
        if (newTestigo.trim()) {
            setFormData({
                ...formData,
                testigos: [...formData.testigos, newTestigo]
            });
            setNewTestigo('');
        }
    };

    const handleRemoveTestigo = (index: number) => {
        setFormData({
            ...formData,
            testigos: formData.testigos.filter((_: any, i: number) => i !== index)
        });
    };

    const handleSave = () => {
        onSave(formData);
    };

    return (
        <div className="space-y-6 p-6 bg-white rounded-lg border">
            <h2 className="text-2xl font-bold">Acta de Desnaturalización de Productos</h2>

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
                    <label className="text-sm font-medium">Responsable</label>
                    <Input
                        type="text"
                        value={formData.responsable || ''}
                        onChange={(e) => handleInputChange('responsable', e.target.value)}
                        placeholder="Nombre del responsable"
                        readOnly={readOnly}
                    />
                </div>
                <div className="space-y-2">
                    <label className="text-sm font-medium">Lugar de Desnaturalización</label>
                    <Input
                        type="text"
                        value={formData.lugar || ''}
                        onChange={(e) => handleInputChange('lugar', e.target.value)}
                        placeholder="Ubicación"
                        readOnly={readOnly}
                    />
                </div>
            </div>

            {/* Productos a Desnaturalizar */}
            <div className="space-y-3">
                <h3 className="text-lg font-semibold">Productos a Desnaturalizar</h3>
                
                <div className="overflow-auto border rounded-lg">
                    <table className="w-full">
                        <thead>
                            <tr className="bg-red-100">
                                <th className="border p-3 text-left">Nombre del Producto</th>
                                <th className="border p-3 text-center">Cantidad</th>
                                <th className="border p-3 text-left">Lote</th>
                                <th className="border p-3 text-left">Razón</th>
                                {!readOnly && <th className="border p-3 text-center">Acciones</th>}
                            </tr>
                        </thead>
                        <tbody>
                            {formData.productos?.map((producto: Producto, idx: number) => (
                                <tr key={idx}>
                                    <td className="border p-3">
                                        <Input
                                            type="text"
                                            value={producto.nombre || ''}
                                            onChange={(e) => handleProductoChange(idx, 'nombre', e.target.value)}
                                            placeholder="Nombre del producto"
                                            className="h-8 text-sm"
                                            readOnly={readOnly}
                                        />
                                    </td>
                                    <td className="border p-3">
                                        <Input
                                            type="number"
                                            value={producto.cantidad || ''}
                                            onChange={(e) => handleProductoChange(idx, 'cantidad', parseFloat(e.target.value))}
                                            placeholder="Cantidad"
                                            className="h-8 text-sm text-center"
                                            readOnly={readOnly}
                                        />
                                    </td>
                                    <td className="border p-3">
                                        <Input
                                            type="text"
                                            value={producto.lote || ''}
                                            onChange={(e) => handleProductoChange(idx, 'lote', e.target.value)}
                                            placeholder="Número de lote"
                                            className="h-8 text-sm"
                                            readOnly={readOnly}
                                        />
                                    </td>
                                    <td className="border p-3">
                                        <Input
                                            type="text"
                                            value={producto.razon || ''}
                                            onChange={(e) => handleProductoChange(idx, 'razon', e.target.value)}
                                            placeholder="Razón de desnaturalización"
                                            className="h-8 text-sm"
                                            readOnly={readOnly}
                                        />
                                    </td>
                                    {!readOnly && (
                                        <td className="border p-3 text-center">
                                            <Button
                                                onClick={() => handleRemoveProducto(idx)}
                                                variant="ghost"
                                                size="sm"
                                                disabled={formData.productos.length === 1}
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
                    <Button onClick={handleAddProducto} variant="outline" className="w-full">
                        Agregar Producto
                    </Button>
                )}
            </div>

            {/* Método de Desnaturalización */}
            <div className="space-y-2">
                <label className="text-sm font-medium">Método de Desnaturalización</label>
                <Textarea
                    value={formData.metodoDesnaturalizacion || ''}
                    onChange={(e) => handleInputChange('metodoDesnaturalizacion', e.target.value)}
                    placeholder="Descripción del método utilizado..."
                    className="h-20"
                    readOnly={readOnly}
                />
            </div>

            {/* Testigos */}
            <div className="space-y-3">
                <h3 className="text-lg font-semibold">Testigos</h3>
                
                {!readOnly && (
                    <div className="flex gap-2">
                        <Input
                            type="text"
                            value={newTestigo}
                            onChange={(e) => setNewTestigo(e.target.value)}
                            placeholder="Nombre del testigo..."
                        />
                        <Button onClick={handleAddTestigo} variant="default">
                            Agregar
                        </Button>
                    </div>
                )}

                <div className="space-y-2">
                    {formData.testigos?.map((testigo: string, idx: number) => (
                        <div key={idx} className="flex justify-between items-center p-3 border rounded bg-gray-50">
                            <p className="text-sm">{testigo}</p>
                            {!readOnly && (
                                <Button
                                    onClick={() => handleRemoveTestigo(idx)}
                                    variant="ghost"
                                    size="sm"
                                >
                                    Eliminar
                                </Button>
                            )}
                        </div>
                    ))}
                </div>
            </div>

            {/* Observaciones */}
            <div className="space-y-2">
                <label className="text-sm font-medium">Observaciones</label>
                <Textarea
                    value={formData.observaciones || ''}
                    onChange={(e) => handleInputChange('observaciones', e.target.value)}
                    placeholder="Cualquier observación adicional..."
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

export default TemplateActaDesnaturalizacion;
