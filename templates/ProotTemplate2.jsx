import React, { useContext, useEffect, useState } from "react";
import {
    Button,
    Input,
    Typography,
} from "@material-tailwind/react";
import Note from "@/widgets/notes/Note";
import { useParams } from 'react-router-dom';
import { TFMLDContext } from "@/context/templates/T_FMLD/TFMLDContext";
import { TemplatesContext } from "@/context/templates/templatesContext";
import HeaderTable from "@/widgets/tables/header/HeaderTable";
import { DownloadFiles } from "@/widgets/buttons/downloadComponentes";

const initialCorrectiveActions = [];

export function ProotTemplate2() {
    const { dataTable, dataTableAC, createTFMLS, createCorrectiveActionsTFMLS, getTFMLS, getCATFMLS, updateTFMLS, date, downloadExcel, downloadPDF } = useContext(TFMLDContext);
    const [rows, setRows] = useState([]);
    const [encargados, setEncargados] = useState([]); // Nuevo estado para los encargados
    const [newActions, setNewActions] = useState(false);
    const [cellColors, setCellColors] = useState({});
    const [correctiveActions, setCorrectiveActions] = useState(initialCorrectiveActions);
    const { id } = useParams();
    const { dataTemplates, getTemplates } = useContext(TemplatesContext);

    useEffect(() => {
        getTemplates();
    }, []);

    useEffect(() => {
        if (id && dataTable == null) {
            getTFMLS(id);
        }
        if (id && dataTableAC == null) {
            getCATFMLS(id);
        }
    }, [id, dataTable, dataTableAC, getTFMLS, getCATFMLS]);

    useEffect(() => {
        if (dataTable) {
            const newRows = Object.keys(dataTable);
            const newCellColors = {};
            const newEncargados = newRows.map(rowKey => dataTable[rowKey].encargado || "");

            newRows.forEach((rowKey, rowIndex) => {
                const row = dataTable[rowKey];
                if (row && row.cells) {
                    row.cells.forEach((cellValue, colIndex) => {
                        const cellKey = `${rowIndex}-${colIndex}`;
                        if (cellValue === 1) newCellColors[cellKey] = 'green';
                        if (cellValue === 2) newCellColors[cellKey] = 'red';
                        if (cellValue === 3) newCellColors[cellKey] = 'yellow';
                    });
                }
            });

            setRows(newRows);
            setEncargados(newEncargados); // Establecer el nuevo estado de encargados
            setCellColors(newCellColors);
        } else {
            setRows([]); // Inicializar con una lista vacía si no hay datos
        }
    }, [dataTable]);

    useEffect(() => {
        if (dataTableAC) {
            const newCorrectiveActions = dataTableAC.map(action => ({
                desviacion: action.specific_deviation,
                fecha: action.compliance_date,
                responsable: action.responsible,
                accion: action.action_implemented,
                fechaCumplimiento: action.compliance_date,
                firma: action.verify
            }));

            setCorrectiveActions(newCorrectiveActions);
        }
    }, [dataTableAC]);

    const formatName = (name) => {
        return name.trim().toLowerCase().replace(/\s+/g, '_');
    };

    const addRow = () => {
        setRows([...rows, ""]);
        setEncargados([...encargados, ""]); // Añadir un nuevo encargado vacío
    };

    const addCorrectiveActionRow = () => {
        setCorrectiveActions([...correctiveActions, {
            desviacion: '',
            fecha: '',
            responsable: '',
            accion: '',
            fechaCumplimiento: '',
            firma: ''
        }]);

        setNewActions(true);
    };

    const handleCellClick = (rowIndex, colIndex) => {
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

    const handleSaveCA = async () => {
        const correctiveActionsData = correctiveActions.map(action => ({
            specific_deviation: action.desviacion,
            responsible: action.responsable,
            action_implemented: action.accion,
            compliance_date: action.fechaCumplimiento,
            verify: action.firma
        }));

        const lastAction = correctiveActionsData[correctiveActionsData.length - 1];
        await createCorrectiveActionsTFMLS(id, lastAction);
        setNewActions(false);
    };

    const handleSaveOrUpdate = async () => {
        const tableData = {};

        // Validar que los campos no estén vacíos
        const invalidRows = rows.filter(row => row.trim() === "");
        if (invalidRows.length > 0) {
            alert("Todos los campos de 'EQUIPOS, ÁREAS Y UTENSILIOS' deben estar llenos.");
            return;
        }

        rows.forEach((row, rowIndex) => {
            const cells = [...Array(31).keys()].map(colIndex => {
                const cellKey = `${rowIndex}-${colIndex}`;
                const color = cellColors[cellKey];

                if (color === 'green') return 1;
                if (color === 'red') return 2;
                if (color === 'yellow') return 3;
                return 0;
            });

            tableData[formatName(row)] = {
                cells,
                encargado: encargados[rowIndex] // Usar el encargado correspondiente
            };
        });

        // Lógica de creación o actualización
        if (dataTable) {
            await updateTFMLS(id, tableData); // Update si dataTable existe
        } else {
            await createTFMLS(tableData); // Create si dataTable no existe
        }
    };


    return (
        <div className="mt-12 fadeIn">
            <Note
                title={"PARAMETROS EVALUATIVOS"}
                content={"ASPECTOS A EVALUAR EN LA LIMPIEZA Y DESINFECCIÓN: Ausencia de polvo, restos del alimento, manchas de tinta, grasa, o algún colorante, ausencia de películas u óxido, un enjuague completo, una organización adecuada. Una correcta preparación tanto del desengrasante como del desinfectante. Si todo esto CUMPLE se registra Cumple, y de lo contrario, si se llega a observar alguna anomalía, se registra la NO CONFORMIDAD (No Cumple) y se presenta su acción correctiva. En la casilla 'ENCARGADO' registrar las iniciales de su nombre."}
            />

            <div className='w-full flex justify-between my-5'>
                <div className="flex flex-row gap-5 ">
                    <Button onClick={addRow} color="green" variant="gradient">
                        AGREGAR FILA
                    </Button>
                    <Button color="blue" variant="gradient" onClick={handleSaveOrUpdate}>
                        GUARDAR DATOS
                    </Button>
                </div>
                <div>
                    <DownloadFiles createPDF={() => downloadPDF(id)} createExcel={() => downloadExcel(id)} />
                </div>
            </div>


            <HeaderTable dataTemplates={dataTemplates} dateString={date ? date.created_at : '01-01-2000'} name='formato de monitoreo de limpieza y desinfeccion' version_id={5} />

            <div className="overflow-x-scroll">
                <table className="w-full border-collapse">
                    <thead>
                        <tr>
                            <th className="border p-2 text-center">EQUIPOS, ÁREAS Y UTENSILIOS</th>
                            <th className="border p-2 text-center">PERIODICIDAD</th>
                            <th className="border p-2 text-center">ENCARGADO</th>
                            {[...Array(31).keys()].map((num, index) => (
                                <th key={index} className="border p-2 text-center">{num + 1}</th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((row, rowIndex) => (
                            <tr key={rowIndex}>
                                <td className="border p-2 text-center">
                                    <Input
                                        size="md"
                                        value={row}
                                        onChange={e => {
                                            const newRows = [...rows];
                                            newRows[rowIndex] = e.target.value;
                                            setRows(newRows);
                                        }}
                                        required
                                    />
                                </td>
                                <td className="border p-2 text-center">Diario</td>
                                <td className="border p-2 text-center">
                                    <Input
                                        size="md"
                                        value={encargados[rowIndex]}
                                        onChange={e => {
                                            const newEncargados = [...encargados];
                                            newEncargados[rowIndex] = e.target.value;
                                            setEncargados(newEncargados);
                                        }}
                                        required
                                    />
                                </td>
                                {[...Array(31).keys()].map(colIndex => (
                                    <td
                                        key={colIndex}
                                        className="border p-2 text-center"
                                        style={{ backgroundColor: cellColors[`${rowIndex}-${colIndex}`] || 'white' }}
                                        onClick={() => handleCellClick(rowIndex, colIndex)}
                                    >
                                        {cellColors[`${rowIndex}-${colIndex}`] === 'green' ? 'Cumple' :
                                            cellColors[`${rowIndex}-${colIndex}`] === 'red' ? 'No Cumple' :
                                                cellColors[`${rowIndex}-${colIndex}`] === 'yellow' ? 'No Aplica' : ''}
                                    </td>
                                ))}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            <div className="overflow-x-scroll">
                <table className="w-full border-collapse mt-5">
                    <thead>
                        <tr>
                            <th className="border p-2 text-center">DESVIACIÓN ESPECÍFICA</th>
                            <th className="border p-2 text-center">FECHA</th>
                            <th className="border p-2 text-center">RESPONSABLE</th>
                            <th className="border p-2 text-center">ACCIÓN CORRECTIVA</th>
                            <th className="border p-2 text-center">FECHA DE CUMPLIMIENTO DE LA ACCIÓN CORRECTIVA</th>
                            <th className="border p-2 text-center">FIRMA</th>
                        </tr>
                    </thead>
                    <tbody>
                        {correctiveActions.map((action, actionIndex) => (
                            <tr key={actionIndex}>
                                <td className="border p-2 text-center">
                                    <Input
                                        size="md"
                                        value={action.desviacion}
                                        onChange={e => {
                                            const newActions = [...correctiveActions];
                                            newActions[actionIndex].desviacion = e.target.value;
                                            setCorrectiveActions(newActions);
                                        }}
                                        required
                                    />
                                </td>
                                <td className="border p-2 text-center">
                                    <Input
                                        size="md"
                                        type="date"
                                        value={action.fecha}
                                        onChange={e => {
                                            const newActions = [...correctiveActions];
                                            newActions[actionIndex].fecha = e.target.value;
                                            setCorrectiveActions(newActions);
                                        }}
                                        required
                                    />
                                </td>
                                <td className="border p-2 text-center">
                                    <Input
                                        size="md"
                                        value={action.responsable}
                                        onChange={e => {
                                            const newActions = [...correctiveActions];
                                            newActions[actionIndex].responsable = e.target.value;
                                            setCorrectiveActions(newActions);
                                        }}
                                        required
                                    />
                                </td>
                                <td className="border p-2 text-center">
                                    <Input
                                        size="md"
                                        value={action.accion}
                                        onChange={e => {
                                            const newActions = [...correctiveActions];
                                            newActions[actionIndex].accion = e.target.value;
                                            setCorrectiveActions(newActions);
                                        }}
                                        required
                                    />
                                </td>
                                <td className="border p-2 text-center">
                                    <Input
                                        size="md"
                                        type="date"
                                        value={action.fechaCumplimiento}
                                        onChange={e => {
                                            const newActions = [...correctiveActions];
                                            newActions[actionIndex].fechaCumplimiento = e.target.value;
                                            setCorrectiveActions(newActions);
                                        }}
                                        required
                                    />
                                </td>
                                <td className="border p-2 text-center">
                                    <Input
                                        size="md"
                                        value={action.firma}
                                        onChange={e => {
                                            const newActions = [...correctiveActions];
                                            newActions[actionIndex].firma = e.target.value;
                                            setCorrectiveActions(newActions);
                                        }}
                                        required
                                    />
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <div className='flex mb-5 w-full justify-center gap-5 mt-5'>
                {newActions ?
                    <Button onClick={handleSaveCA} color="blue" variant="gradient">
                        GUARDAR DATOS
                    </Button>
                    :
                    <Button onClick={addCorrectiveActionRow} color="green" variant="gradient">
                        AGREGAR ACCIÓN CORRECTIVA
                    </Button>
                }
            </div>
        </div>
    );
}

export default ProotTemplate2;
