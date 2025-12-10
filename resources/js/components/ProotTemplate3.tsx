import { useState, useEffect } from "react";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";

interface ProotTemplate3Props {
    initialData?: any;
    onSave: (data: any) => void;
    readOnly?: boolean;
}

export function ProotTemplate3({ initialData, onSave, readOnly = false }: ProotTemplate3Props) {
    const [formData, setFormData] = useState<any>({
        evaluator: '',
        date: '',
        location: '',
        findings: [],
        recommendations: []
    });
    const [newFinding, setNewFinding] = useState('');
    const [newRecommendation, setNewRecommendation] = useState('');

    useEffect(() => {
        if (initialData) {
            setFormData(initialData);
        }
    }, [initialData]);

    const handleInputChange = (field: string, value: any) => {
        setFormData({ ...formData, [field]: value });
    };

    const handleAddFinding = () => {
        if (newFinding.trim()) {
            setFormData({
                ...formData,
                findings: [...formData.findings, newFinding]
            });
            setNewFinding('');
        }
    };

    const handleRemoveFinding = (index: number) => {
        setFormData({
            ...formData,
            findings: formData.findings.filter((_: any, i: number) => i !== index)
        });
    };

    const handleAddRecommendation = () => {
        if (newRecommendation.trim()) {
            setFormData({
                ...formData,
                recommendations: [...formData.recommendations, newRecommendation]
            });
            setNewRecommendation('');
        }
    };

    const handleRemoveRecommendation = (index: number) => {
        setFormData({
            ...formData,
            recommendations: formData.recommendations.filter((_: any, i: number) => i !== index)
        });
    };

    const handleSave = () => {
        onSave(formData);
    };

    return (
        <div className="space-y-6 p-6 bg-white rounded-lg border">
            <h2 className="text-2xl font-bold">Formato de Inspección y Reporte</h2>

            {/* Información General */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div className="space-y-2">
                    <label className="text-sm font-medium">Evaluador/Inspector</label>
                    <Input
                        type="text"
                        value={formData.evaluator || ''}
                        onChange={(e) => handleInputChange('evaluator', e.target.value)}
                        placeholder="Nombre del evaluador"
                        readOnly={readOnly}
                    />
                </div>
                <div className="space-y-2">
                    <label className="text-sm font-medium">Fecha</label>
                    <Input
                        type="date"
                        value={formData.date || ''}
                        onChange={(e) => handleInputChange('date', e.target.value)}
                        readOnly={readOnly}
                    />
                </div>
                <div className="space-y-2">
                    <label className="text-sm font-medium">Ubicación/Área</label>
                    <Input
                        type="text"
                        value={formData.location || ''}
                        onChange={(e) => handleInputChange('location', e.target.value)}
                        placeholder="Ubicación de la inspección"
                        readOnly={readOnly}
                    />
                </div>
            </div>

            {/* Hallazgos */}
            <div className="space-y-3">
                <h3 className="text-lg font-semibold">Hallazgos</h3>
                
                {!readOnly && (
                    <div className="flex gap-2">
                        <Textarea
                            placeholder="Descripción del hallazgo..."
                            value={newFinding}
                            onChange={(e) => setNewFinding(e.target.value)}
                            className="h-20"
                        />
                        <Button onClick={handleAddFinding} variant="default">
                            Agregar
                        </Button>
                    </div>
                )}

                <div className="space-y-2">
                    {formData.findings?.map((finding: string, idx: number) => (
                        <div key={idx} className="flex justify-between items-start p-3 border rounded bg-blue-50">
                            <p className="text-sm flex-1">{finding}</p>
                            {!readOnly && (
                                <Button
                                    onClick={() => handleRemoveFinding(idx)}
                                    variant="ghost"
                                    size="sm"
                                    className="ml-2"
                                >
                                    Eliminar
                                </Button>
                            )}
                        </div>
                    ))}
                </div>
            </div>

            {/* Recomendaciones */}
            <div className="space-y-3">
                <h3 className="text-lg font-semibold">Recomendaciones</h3>
                
                {!readOnly && (
                    <div className="flex gap-2">
                        <Textarea
                            placeholder="Descripción de la recomendación..."
                            value={newRecommendation}
                            onChange={(e) => setNewRecommendation(e.target.value)}
                            className="h-20"
                        />
                        <Button onClick={handleAddRecommendation} variant="default">
                            Agregar
                        </Button>
                    </div>
                )}

                <div className="space-y-2">
                    {formData.recommendations?.map((rec: string, idx: number) => (
                        <div key={idx} className="flex justify-between items-start p-3 border rounded bg-green-50">
                            <p className="text-sm flex-1">{rec}</p>
                            {!readOnly && (
                                <Button
                                    onClick={() => handleRemoveRecommendation(idx)}
                                    variant="ghost"
                                    size="sm"
                                    className="ml-2"
                                >
                                    Eliminar
                                </Button>
                            )}
                        </div>
                    ))}
                </div>
            </div>

            {/* Botón de guardar */}
            {!readOnly && (
                <Button onClick={handleSave} className="w-full">
                    Guardar Formato
                </Button>
            )}
        </div>
    );
}

export default ProotTemplate3;
