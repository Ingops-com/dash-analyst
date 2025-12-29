import { useState, useEffect } from "react";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";

interface FloatCellProps {
    rowIndex: number;
    colIndex: number;
    floatValues: Record<number, Record<number, number>>;
    setFloatValues: (values: Record<number, Record<number, number>>) => void;
}

const FloatCell = ({ rowIndex, colIndex, floatValues, setFloatValues }: FloatCellProps) => {
    const handleFloatChange = (value: string) => {
        const newValues = { ...floatValues };
        if (!newValues[rowIndex]) {
            newValues[rowIndex] = {};
        }
        newValues[rowIndex][colIndex] = parseFloat(value) || 0;
        setFloatValues(newValues);
    };

    return (
        <td className="border p-2 text-center">
            <Input
                type="number"
                step="0.01"
                className="h-8 text-sm"
                value={floatValues[rowIndex]?.[colIndex] || ''}
                onChange={e => handleFloatChange(e.target.value)}
            />
        </td>
    );
};

interface ComplyCellProps {
    rowIndex: string | number;
    colIndex: number;
    cellColors: Record<string, string>;
    setCellColors: (colors: Record<string, string>) => void;
}

const ComplyCell = ({ rowIndex, colIndex, cellColors, setCellColors }: ComplyCellProps) => {
    const handleCellClick = () => {
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

    const bgColorClass = {
        'white': 'bg-white',
        'green': 'bg-green-200',
        'red': 'bg-red-200',
        'yellow': 'bg-yellow-200'
    }[cellColors[`${rowIndex}-${colIndex}`] || 'white'] || 'bg-white';

    return (
        <td
            className={`border p-2 text-center cursor-pointer ${bgColorClass}`}
            onClick={handleCellClick}
        >
            {cellColors[`${rowIndex}-${colIndex}`] === 'green' ? 'Cumple' :
                cellColors[`${rowIndex}-${colIndex}`] === 'red' ? 'No cumple' :
                    cellColors[`${rowIndex}-${colIndex}`] === 'yellow' ? 'No aplica' : ''}
        </td>
    );
};

interface Row {
    name: string;
    supervisor: string;
}

interface PlanillaSalubridadProps {
    initialData?: any;
    onSave: (data: any) => void;
    readOnly?: boolean;
}

export function PlanillaSalubridad({ initialData, onSave, readOnly = false }: PlanillaSalubridadProps) {
    const [rows, setRows] = useState<Row[]>([]);
    const [cellColors, setCellColors] = useState<Record<string, string>>({});
    const [floatValues, setFloatValues] = useState<Record<number, Record<number, number>>>({});

    const fixedRows = [
        { name: "pest_free_areas", displayName: "ÁREAS LIBRES DE PLAGAS" },
        { name: "dead_pests", displayName: "PLAGAS MUERTAS" },
        { name: "rodent_excrement", displayName: "EXCREMENTO DE ROEDORES" },
        { name: "evidence_of_spider_webs", displayName: "EVIDENCIA DE TELARAÑAS" },
        { name: "packaging_alterations", displayName: "ALTERACIONES DE LOS EMPAQUES" },
        { name: "roofing_eticity", displayName: "HERMETICIDAD DE TECHOS" },
        { name: "door_eticity", displayName: "HERMETICIDAD DE PUERTAS" },
        { name: "emergency_rute", displayName: "RUTA DE EVACUACIÓN" },
        { name: "evacuation_solid_residues", displayName: "EVACUACIÓN DE RESIDUOS SÓLIDOS" },
    ];

    const floatRows = [
        { name: "evacuation_volumes", displayName: "VOLUMEN DE EVACUACIÓN" },
        { name: "color", displayName: "COLOR" },
        { name: "odor", displayName: "OLOR" },
        { name: "flavor", displayName: "SABOR" },
        { name: "chlorine", displayName: "CLORO" },
        { name: "ph", displayName: "PH" },
        { name: "sampling_point", displayName: "PUNTO DE MUESTREO" },
    ];

    const [fixedSupervisors, setFixedSupervisors] = useState<string[]>(Array(fixedRows.length).fill(""));
    const [floatSupervisors, setFloatSupervisors] = useState<string[]>(Array(floatRows.length).fill(""));

    const TABLE_HEAD = [
        "Nombre", "Supervisor",
        ...[...Array(31).keys()].map(num => (num + 1).toString()),
    ];

    useEffect(() => {
        if (initialData) {
            loadData(initialData);
        }
    }, [initialData]);

    const formatName = (name: string) => {
        return name.trim().toLowerCase().replace(/\s+/g, '_');
    };

    const loadData = (data: any) => {
        if (!data) return;

        const { data: operatorsData, fixedData, floatData } = data;

        const newCellColors: Record<string, string> = {};
        const newFloatValues: Record<number, Record<number, number>> = {};

        // Cargar datos de operadores dinámicos
        if (operatorsData && operatorsData.length > 0) {
            const operators = operatorsData[0].operators;
            const newRows = Object.keys(operators).map(key => ({
                name: key,
                supervisor: operators[key].supervisor
            }));
            setRows(newRows);

            Object.keys(operators).forEach((operador, rowIndex) => {
                const valores = operators[operador].valores;
                Object.keys(valores).forEach(colKey => {
                    const value = valores[colKey];
                    const colIndex = parseInt(colKey.replace('col', ''));
                    const color = value === 1 ? 'green' : value === 2 ? 'red' : value === 3 ? 'yellow' : 'white';
                    newCellColors[`${rowIndex}-${colIndex}`] = color;
                });
            });
        }

        // Cargar datos de filas fijas
        if (fixedData && fixedData.length > 0) {
            const newFixedSupervisors = fixedRows.map(row => 
                fixedData[0][formatName(row.name)]?.supervisor || ""
            );
            setFixedSupervisors(newFixedSupervisors);

            fixedRows.forEach((fixedRow, rowIndex) => {
                const fixedRowData = fixedData[0][formatName(fixedRow.name)];
                if (fixedRowData && fixedRowData.valores) {
                    Object.keys(fixedRowData.valores).forEach(colKey => {
                        const value = fixedRowData.valores[colKey];
                        if (value !== 0) {
                            const colIndex = parseInt(colKey.replace('col', ''));
                            const color = value === 1 ? 'green' : value === 2 ? 'red' : value === 3 ? 'yellow' : 'white';
                            newCellColors[`fixed-${rowIndex}-${colIndex}`] = color;
                        }
                    });
                }
            });
        }

        // Cargar datos de filas flotantes
        if (floatData && floatData.length > 0) {
            const newFloatSupervisors = floatRows.map(row =>
                floatData[0][formatName(row.name)]?.supervisor || ""
            );
            setFloatSupervisors(newFloatSupervisors);

            floatRows.forEach((floatRow, rowIndex) => {
                const fieldName = formatName(floatRow.name);
                const floatRowData = floatData[0][fieldName];
                if (floatRowData && floatRowData.valores) {
                    Object.keys(floatRowData.valores).forEach(colKey => {
                        const value = floatRowData.valores[colKey];
                        const colIndex = parseInt(colKey.replace('col', ''));
                        if (["chlorine", "ph", "evacuation_volumes", "sampling_point"].includes(floatRow.name.toLowerCase())) {
                            if (!newFloatValues[rowIndex]) {
                                newFloatValues[rowIndex] = {};
                            }
                            newFloatValues[rowIndex][colIndex] = value;
                        } else {
                            const color = value === 1 ? 'green' : value === 2 ? 'red' : value === 3 ? 'yellow' : 'white';
                            newCellColors[`float-${rowIndex}-${colIndex}`] = color;
                        }
                    });
                }
            });
        }

        setCellColors(newCellColors);
        setFloatValues(newFloatValues);
    };

    const addRow = () => {
        setRows([...rows, { name: '', supervisor: '' }]);
    };

    const getColorValue = (key: string) => {
        const color = cellColors[key];
        return color === 'green' ? 1 : color === 'red' ? 2 : color === 'yellow' ? 3 : 0;
    };

    const collectData = () => {
        const generateRowData = (rowsList: any[], supervisorsList: string[], type?: string) =>
            rowsList.reduce((obj: any, row: any, rowIndex: number) => {
                const keyPrefix = type ? `${type}-${rowIndex}` : rowIndex;
                const rowData = {
                    supervisor: supervisorsList[rowIndex] || "",
                    valores: [...Array(31).keys()].reduce((valObj: any, colIndex: number) => {
                        const cellKey = `${keyPrefix}-${colIndex}`;
                        valObj[`col${colIndex}`] = getColorValue(cellKey);
                        return valObj;
                    }, {}),
                };
                obj[formatName(row.name || row.displayName)] = rowData;
                return obj;
            }, {});

        const floatData = floatRows.reduce((obj: any, floatRow, rowIndex) => {
            const rowData = {
                supervisor: floatSupervisors[rowIndex] || "",
                valores: [...Array(31).keys()].reduce((valObj: any, colIndex: number) => {
                    if (["chlorine", "ph", "evacuation_volumes", "sampling_point"].includes(floatRow.name.toLowerCase())) {
                        valObj[`col${colIndex}`] = floatValues[rowIndex]?.[colIndex] || 0;
                    } else {
                        const cellKey = `float-${rowIndex}-${colIndex}`;
                        valObj[`col${colIndex}`] = getColorValue(cellKey);
                    }
                    return valObj;
                }, {}),
            };
            obj[formatName(floatRow.name)] = rowData;
            return obj;
        }, {});

        return {
            data: rows.length > 0 ? [{ operators: generateRowData(rows, rows.map(r => r.supervisor)) }] : [],
            fixedData: [generateRowData(fixedRows, fixedSupervisors, "fixed")],
            floatData: [floatData],
        };
    };

    const handleSave = () => {
        const data = collectData();
        onSave(data);
    };

    return (
        <div className="space-y-4">
            {!readOnly && (
                <div className='flex gap-2'>
                    <Button onClick={addRow} variant="default" size="sm">
                        Agregar Fila
                    </Button>
                    <Button onClick={handleSave} variant="default" size="sm">
                        Guardar Planilla
                    </Button>
                </div>
            )}

            <div className="overflow-x-auto border rounded-md">
                <table className="w-full border-collapse text-sm">
                    <thead>
                        <tr>
                            {TABLE_HEAD.map((header, index) => (
                                <th key={index} className="border p-2 text-center bg-muted font-medium">{header}</th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((row, rowIndex) => (
                            <tr key={rowIndex}>
                                <td className="border p-2 text-center">
                                    <Input
                                        className="h-8 text-sm"
                                        value={row.name}
                                        onChange={e => {
                                            const newRows = [...rows];
                                            newRows[rowIndex].name = e.target.value;
                                            setRows(newRows);
                                        }}
                                        disabled={readOnly}
                                    />
                                </td>
                                <td className="border p-2 text-center">
                                    <Input
                                        className="h-8 text-sm"
                                        value={row.supervisor}
                                        onChange={e => {
                                            const newRows = [...rows];
                                            newRows[rowIndex].supervisor = e.target.value;
                                            setRows(newRows);
                                        }}
                                        disabled={readOnly}
                                    />
                                </td>
                                {[...Array(31).keys()].map(colIndex => (
                                    <ComplyCell
                                        key={colIndex}
                                        rowIndex={rowIndex}
                                        colIndex={colIndex}
                                        cellColors={cellColors}
                                        setCellColors={readOnly ? () => {} : setCellColors}
                                    />
                                ))}
                            </tr>
                        ))}
                        {fixedRows.map((fixedRow, rowIndex) => (
                            <tr key={rowIndex + rows.length}>
                                <td className="border p-2 text-center text-xs font-medium">{fixedRow.displayName}</td>
                                <td className="border p-2 text-center">
                                    <Input
                                        className="h-8 text-sm"
                                        value={fixedSupervisors[rowIndex]}
                                        onChange={e => {
                                            const newSupervisors = [...fixedSupervisors];
                                            newSupervisors[rowIndex] = e.target.value;
                                            setFixedSupervisors(newSupervisors);
                                        }}
                                        disabled={readOnly}
                                    />
                                </td>
                                {[...Array(31).keys()].map(colIndex => (
                                    <ComplyCell
                                        key={colIndex}
                                        rowIndex={`fixed-${rowIndex}`}
                                        colIndex={colIndex}
                                        cellColors={cellColors}
                                        setCellColors={readOnly ? () => {} : setCellColors}
                                    />
                                ))}
                            </tr>
                        ))}
                        {floatRows.map((floatRow, rowIndex) => (
                            <tr key={rowIndex + rows.length + fixedRows.length}>
                                <td className="border p-2 text-center text-xs font-medium">{floatRow.displayName}</td>
                                <td className="border p-2 text-center">
                                    <Input
                                        className="h-8 text-sm"
                                        value={floatSupervisors[rowIndex]}
                                        onChange={e => {
                                            const newSupervisors = [...floatSupervisors];
                                            newSupervisors[rowIndex] = e.target.value;
                                            setFloatSupervisors(newSupervisors);
                                        }}
                                        disabled={readOnly}
                                    />
                                </td>
                                {["chlorine", "ph", "evacuation_volumes", "sampling_point"].includes(floatRow.name)
                                    ? [...Array(31).keys()].map(colIndex => (
                                        <FloatCell
                                            key={colIndex}
                                            rowIndex={rowIndex}
                                            colIndex={colIndex}
                                            floatValues={floatValues}
                                            setFloatValues={readOnly ? () => {} : setFloatValues}
                                        />
                                    ))
                                    : [...Array(31).keys()].map(colIndex => (
                                        <ComplyCell
                                            key={colIndex}
                                            rowIndex={`float-${rowIndex}`}
                                            colIndex={colIndex}
                                            cellColors={cellColors}
                                            setCellColors={readOnly ? () => {} : setCellColors}
                                        />
                                    ))}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
