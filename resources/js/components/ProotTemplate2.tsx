import { useState, useEffect } from "react";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";

interface ProotTemplate2Props {
    initialData?: any;
    onSave: (data: any) => void;
    readOnly?: boolean;
}

export function ProotTemplate2({ initialData, onSave, readOnly = false }: ProotTemplate2Props) {
    const [rows, setRows] = useState<any[]>([]);
    const [cellColors, setCellColors] = useState<Record<string, string>>({});
    const [correctiveActions, setCorrectiveActions] = useState<any[]>([]);
    const [newAction, setNewAction] = useState('');

    useEffect(() => {
        if (initialData) {
            setRows(initialData.rows || []);
            setCellColors(initialData.cellColors || {});
            setCorrectiveActions(initialData.correctiveActions || []);
        }
    }, [initialData]);

    const handleSave = () => {
        onSave({
            rows,
            cellColors,
            correctiveActions
        });
    };

    const handleCellClick = (rowIndex: number, colIndex: number) => {
        if (readOnly) return;
        
        const cellKey = `${rowIndex}-${colIndex}`;
        const currentColor = cellColors[cellKey] || 'white';

        let newColor;
        if (currentColor === 'white') {
            newColor = 'green';
        } else if (currentColor === 'green') {
            newColor = 'red';
        } else if (currentColor === 'red') {
            newColor = 'yellow';
        } else {
            newColor = 'white';
        }

        setCellColors({ ...cellColors, [cellKey]: newColor });
    };

    const getBgColorClass = (color: string) => {
        const colorMap: Record<string, string> = {
            'white': 'bg-white',
            'green': 'bg-green-200',
            'red': 'bg-red-200',
            'yellow': 'bg-yellow-200'
        };
        return colorMap[color] || 'bg-white';
    };

    const handleAddAction = () => {
        if (newAction.trim()) {
            setCorrectiveActions([...correctiveActions, { description: newAction, status: 'pending' }]);
            setNewAction('');
        }
    };

    const handleRemoveAction = (index: number) => {
        setCorrectiveActions(correctiveActions.filter((_, i) => i !== index));
    };

    return (
        <div className="space-y-6 p-6 bg-white rounded-lg border">
            <div className="space-y-4">
                <h2 className="text-2xl font-bold">Formato de Monitoreo y Control</h2>
                
                {/* Tabla de monitoreo */}
                <div className="overflow-auto border rounded-lg">
                    <table className="w-full border-collapse">
                        <thead>
                            <tr className="bg-blue-100">
                                <th className="border p-2">Ítem</th>
                                <th className="border p-2">Estado</th>
                                <th className="border p-2">Observaciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            {rows.map((row, idx) => (
                                <tr key={idx} className={getBgColorClass(cellColors[`${idx}-0`])}>
                                    <td className="border p-2">{row.name || `Ítem ${idx + 1}`}</td>
                                    <td 
                                        className={`border p-2 text-center cursor-pointer ${getBgColorClass(cellColors[`${idx}-0`])}`}
                                        onClick={() => handleCellClick(idx, 0)}
                                    >
                                        {cellColors[`${idx}-0`] === 'green' ? 'Cumple' :
                                         cellColors[`${idx}-0`] === 'red' ? 'No cumple' :
                                         cellColors[`${idx}-0`] === 'yellow' ? 'No aplica' : ''}
                                    </td>
                                    <td className="border p-2">
                                        <Input
                                            type="text"
                                            placeholder="Observaciones..."
                                            defaultValue={row.observations || ''}
                                            className="h-8 text-sm"
                                            readOnly={readOnly}
                                        />
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {/* Acciones Correctivas */}
                <div className="space-y-3 mt-6">
                    <h3 className="text-lg font-semibold">Acciones Correctivas</h3>
                    
                    {!readOnly && (
                        <div className="flex gap-2">
                            <Textarea
                                placeholder="Descripción de la acción correctiva..."
                                value={newAction}
                                onChange={(e) => setNewAction(e.target.value)}
                                className="h-20"
                            />
                            <Button onClick={handleAddAction} variant="default">
                                Agregar
                            </Button>
                        </div>
                    )}

                    <div className="space-y-2">
                        {correctiveActions.map((action, idx) => (
                            <div key={idx} className="flex justify-between items-start p-3 border rounded bg-gray-50">
                                <div className="flex-1">
                                    <p className="text-sm">{action.description}</p>
                                </div>
                                {!readOnly && (
                                    <Button
                                        onClick={() => handleRemoveAction(idx)}
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
                    <Button onClick={handleSave} className="w-full mt-6">
                        Guardar Formato
                    </Button>
                )}
            </div>
        </div>
    );
}

export default ProotTemplate2;
