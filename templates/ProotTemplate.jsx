import { useContext, useEffect, useState } from "react";
import { Input, Button, Typography } from "@material-tailwind/react";
import Note from "@/widgets/notes/Note";
import { TMSBContext } from "@/context/templates/T_MSB/TMSBContext";
import { useParams } from "react-router-dom";
import { TemplatesContext } from "@/context/templates/templatesContext";
import HeaderTable from "@/widgets/tables/header/HeaderTable";
import { DownloadFiles } from "@/widgets/buttons/downloadComponentes";

const FloatCell = ({ rowIndex, colIndex, floatValues, setFloatValues }) => {
    const handleFloatChange = (value) => {
        const newValues = [...floatValues];
        if (!newValues[rowIndex]) {
            newValues[rowIndex] = {};
        }
        newValues[rowIndex][colIndex] = value;
        setFloatValues(newValues);
    };

    return (
        <td className="border p-2 text-center">
            <Input
                type="number"
                step="0.01"
                size="md"
                value={floatValues[rowIndex]?.[colIndex] || ''}
                onChange={e => handleFloatChange(e.target.value)}
            />
        </td>
    );
};

const ComplyCell = ({ rowIndex, colIndex, cellColors, setCellColors }) => {
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

    return (
        <td
            className="border p-2 text-center"
            style={{ backgroundColor: cellColors[`${rowIndex}-${colIndex}`] || 'white' }}
            onClick={handleCellClick}
        >
            {cellColors[`${rowIndex}-${colIndex}`] === 'green' ? 'Cumple' :
                cellColors[`${rowIndex}-${colIndex}`] === 'red' ? 'No cumple' :
                    cellColors[`${rowIndex}-${colIndex}`] === 'yellow' ? 'No aplica' : ''}
        </td>
    );
};

export function ProotTemplate() {
    const { id } = useParams();
    const { createTMSB, getTMSB, dataTMSB, editTMSB, createCorrectiveActionsTMSB, getACTMSB, correctiveActions, setCorrectiveActions, downloadExcel, downloadPDF } = useContext(TMSBContext);
    const [rows, setRows] = useState([]);
    const [cellColors, setCellColors] = useState({});
    const [floatValues, setFloatValues] = useState([...Array(3)].map(() => ({})));
    const { getTemplates, dataTemplates } = useContext(TemplatesContext);

    useEffect(() => {
        if (id) {
            getTMSB(id);
            if (correctiveActions.length === 0) {
                getACTMSB(id);
            }
        }
        getTemplates();
    }, [id]);

    useEffect(() => {
        getTemplates();
    }, []);

    useEffect(() => {
        if (dataTMSB) {
            loadData(dataTMSB);
        }
    }, [dataTMSB]);

    const addRow = () => {
        setRows([...rows, { name: '', supervisor: '' }]);
        setFloatValues([...floatValues, {}]);
    };

    const formatName = (name) => {
        return name.trim().toLowerCase().replace(/\s+/g, '_');
    };

    const getColorValue = (key, cellColors, floatValues, floatRowNames) => {
        if (floatRowNames.includes(key.toLowerCase())) {
            return parseFloat(floatValues?.[key]) || 0;
        }
        const color = cellColors[key];
        return color === 'green' ? 1 : color === 'red' ? 2 : color === 'yellow' ? 3 : 0;
    };

    const collectData = () => {
        const generateRowData = (rows, cellColors, supervisors, type) =>
            rows.reduce((obj, row, rowIndex) => {
                const keyPrefix = type ? `${type}-${rowIndex}` : rowIndex;
                const rowData = {
                    supervisor: supervisors[rowIndex],
                    valores: [...Array(31).keys()].reduce((valObj, colIndex) => {
                        const cellKey = `${keyPrefix}-${colIndex}`;
                        valObj[`col${colIndex}`] = getColorValue(cellKey, cellColors, floatValues, ["chlorine", "ph", "evacuation_volumes", "sampling_point"]);
                        return valObj;
                    }, {}),
                };
                obj[formatName(row.name)] = rowData;
                return obj;
            }, {});

        const floatData = floatRows.reduce((obj, floatRow, rowIndex) => {
            const rowData = {
                supervisor: floatSupervisors[rowIndex],
                valores: [...Array(31).keys()].reduce((valObj, colIndex) => {
                    // Recolectar valores numéricos correctamente
                    if (["chlorine", "ph", "evacuation_volumes", "sampling_point"].includes(floatRow.name.toLowerCase())) {
                        valObj[`col${colIndex}`] = parseFloat(floatValues[rowIndex]?.[colIndex]) || 0;
                    } else {
                        const cellKey = `float-${rowIndex}-${colIndex}`;
                        const cellColor = cellColors[cellKey];
                        valObj[`col${colIndex}`] = cellColor === 'green' ? 1 :
                            cellColor === 'red' ? 2 :
                                cellColor === 'yellow' ? 3 : 0;
                    }
                    return valObj;
                }, {}),
            };
            obj[formatName(floatRow.name)] = rowData;
            return obj;
        }, {});

        return {
            id,
            data: generateRowData(rows, cellColors, rows.map(r => r.supervisor)),
            fixedData: generateRowData(fixedRows, cellColors, fixedSupervisors, "fixed"),
            floatData: floatData,
        };
    };

    const loadData = (dataTMSB) => {
        const { data_last, fixedData, floatData } = dataTMSB;

        const newCellColors = {};
        const newFloatValues = [...Array(floatRows.length)].map(() => ({}));

        const newRows = data_last && data_last.length > 0
            ? Object.keys(data_last[0].operators).map(key => ({
                name: key,
                supervisor: data_last[0].operators[key].supervisor
            }))
            : [{ name: "nada para cargar", supervisor: "nada para cargar" }];

        if (data_last && data_last.length > 0) {
            const operadores = data_last[0].operators;
            Object.keys(operadores).forEach((operador, rowIndex) => {
                const valores = operadores[operador].valores;
                Object.keys(valores).forEach(colKey => {
                    const value = valores[colKey];
                    const colIndex = parseInt(colKey.replace('col', ''));
                    const color = value === 1 ? 'green' : value === 2 ? 'red' : value === 3 ? 'yellow' : 'white';
                    newCellColors[`${rowIndex}-${colIndex}`] = color;
                });
            });
        }

        const newFixedSupervisors = fixedData && fixedData.length > 0
            ? fixedRows.map(row => fixedData[0][formatName(row.name)]?.supervisor || "nada para cargar")
            : Array(fixedRows.length).fill("nada para cargar");

        if (fixedData && fixedData.length > 0) {
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

        const newFloatSupervisors = floatData && floatData.length > 0
            ? floatRows.map(row => floatData[0][formatName(row.name)]?.supervisor || "nada para cargar")
            : Array(floatRows.length).fill("nada para cargar");

        if (floatData && floatData.length > 0) {
            floatRows.forEach((floatRow, rowIndex) => {
                const fieldName = formatName(floatRow.name);
                const floatRowData = floatData[0][fieldName];
                if (floatRowData && floatRowData.valores) {
                    Object.keys(floatRowData.valores).forEach(colKey => {
                        const value = floatRowData.valores[colKey];
                        const colIndex = parseInt(colKey.replace('col', ''));
                        if (["chlorine", "ph", "evacuation_volumes", "sampling_point"].includes(floatRow.name.toLowerCase())) {
                            newFloatValues[rowIndex][colIndex] = value;
                        } else {
                            const color = value === 1 ? 'green' : value === 2 ? 'red' : value === 3 ? 'yellow' : 'white';
                            newCellColors[`float-${rowIndex}-${colIndex}`] = color;
                        }
                    });
                }
            });
        }

        setRows(newRows);
        setFixedSupervisors(newFixedSupervisors);
        setFloatSupervisors(newFloatSupervisors);
        setCellColors(newCellColors);
        setFloatValues(newFloatValues);
    };

    const isEmptyData = (data) => {
        return !data || Object.keys(data).every(key => {
            const value = data[key];
            return Array.isArray(value) ? value.length === 0 : !value;
        });
    };

    const TABLE_HEAD = [
        "Nombre", "Supervisor",
        ...[...Array(31).keys()].map(num => (num + 1).toString()),
    ];

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
        { name: "evacuation_volumes", displayName: "VOLUMEN DE EVACUACIÓN", values: [] },
        { name: "color", displayName: "COLOR", values: [] },
        { name: "odor", displayName: "OLOR", values: [] },
        { name: "flavor", displayName: "SABOR", values: [] },
        { name: "chlorine", displayName: "CLORO", values: [] },
        { name: "ph", displayName: "PH", values: [] },
        { name: "sampling_point", displayName: "PUNTO DE MUESTREO", values: [] },

    ];

    const [fixedSupervisors, setFixedSupervisors] = useState(Array(fixedRows.length).fill(""));
    const [floatSupervisors, setFloatSupervisors] = useState(Array(floatRows.length).fill(""));

    const handleSaveOrUpdate = async () => {
        const jsonData = collectData();
        if (isEmptyData(dataTMSB)) {
            await createTMSB(jsonData); // Crear
        } else {
            await editTMSB(id, jsonData); // Actualizar
        }
    };

    // Acciones correctivas

    const [newActions, setNewActions] = useState(false);

    const handleSaveCA = async () => {
        const correctiveActionsData = correctiveActions.map(action => ({
            specific_deviation: action.desviacion,
            responsible: action.responsable,
            action_implemented: action.accion,
            compliance_date: action.fechaCumplimiento,
            verify: action.firma
        }));

        const lastAction = correctiveActionsData[correctiveActionsData.length - 1];
        await createCorrectiveActionsTMSB(id, lastAction);
        setNewActions(false);
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

    const handleCorrectiveChange = (index, field, value) => {
        const newActions = [...correctiveActions];
        newActions[index][field] = value;
        setCorrectiveActions(newActions);
    };

    return (
        <div className="mt-12 fadeIn">
            <Note
                title={"ASPECTOS A EVALUAR"}
                aspecto={"MANIPULADOR DE ALIMENTOS:"}
                content={` Para que el personal cumpla a cabalidad, debe mantener las uñas cortas y limpias, estar sin maquillaje ni accesorios (aretes, collares, anillos, piercing, etc.), no tener olores fuertes, utilizar uniforme o ropa clara, limpia y en buen estado. El lavado de manos debe realizarse cada vez que se vaya al baño, antes o después de comer, cada vez que salga del sitio de trabajo o toque su cuerpo, y antes de entrar a las áreas de trabajo. De igual manera, el uso de tapabocas y cofia es obligatorio. No se puede comer, masticar, fumar o beber en áreas no destinadas para este fin. Debe utilizar equipo de protección personal (EPP) como cofia, tapabocas y guantes, y mantener una buena higiene personal. Si alguno de estos aspectos no se cumple a cabalidad en un día, se registra como NO CONFORMIDAD. Es obligatorio portar el carnet que lo identifique dentro de las áreas; en caso contrario, se restringirá el acceso de la persona.`}
            />

            <Note
                title={"ASPECTOS A EVALUAR"}
                aspecto={"INSTALACIONES LOCATIVAS Y PLAGAS: "}
                content={`Para que las instalaciones cumplan, es necesario que las áreas estén limpias y libres de plagas; se debe evidenciar en todo momento limpieza y aseo de las instalaciones, áreas libres de empaques, residuos y escombros, y las ventanas tengan malla milimétrica en buen estado para prevenir el ingreso de plagas. En caso contrario, se registra la no conformidad y se toma acción correctiva. Se debe revisar la rotación de productos para que no se evidencien productos vencidos. Se debe registrar la fecha de vencimiento y rotación de los productos (lote y rotación). Para productos sin fecha de vencimiento, se debe establecer un control para determinar si el producto sigue apto para consumo humano. El estado de las instalaciones debe ser adecuado para la manipulación de alimentos. Las paredes, pisos, techos y equipos deben estar en buen estado y limpios. Si se evidencia alguna anomalía, se registra la no conformidad y se toma acción correctiva.`}
            />

            <Note
                title={"ASPECTOS A EVALUAR"}
                aspecto={"EVACUACIÓN DE RESIDUOS SOLIDOS: "}
                content={"Se debe verificar que los residuos se retiren del establecimiento en la fecha de recolección indicada y registrar el volumen evacuado en numeros de bolsa, así como verificar si se clasifica o no el residuo (BLANCO: residuos aprovechable), (NEGRO: residuos NO aprovechable), (VERDE: residuos orgánicos), (AZUL: residuos peligrosos)"}
            />
            <Note
                title={"ASPECTOS A EVALUAR"}
                aspecto={"AGUA POTABLE:"}
                content={"Para que la calidad del agua cumpla se realizara medicion de cloro libre y pH (cloro: 0,3 - 2 y pH: 6,5 - 9) por medio de equipo de verificación,se verifica sabor olor y color adecuados, si alguno de estos parametros no cumple se registra la no conformidad y se toma acción correctiva."}
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

            <HeaderTable dataTemplates={dataTemplates} dateString={dataTMSB ? dataTMSB.data_last[0] : false} name='Monitoreo Saneamiento Basico' version_id={4} />

            <div className="overflow-x-scroll">
                <table className="w-full border-collapse">
                    <thead>
                        <tr>
                            {TABLE_HEAD.map((header, index) => (
                                <th key={index} className="border p-2 text-center">{header}</th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((row, rowIndex) => (
                            <tr key={rowIndex}>
                                <td className="border p-2 text-center">
                                    <Input
                                        size="md"
                                        value={row.name}
                                        onChange={e => {
                                            const newRows = [...rows];
                                            newRows[rowIndex].name = e.target.value;
                                            setRows(newRows);
                                        }}
                                    />
                                </td>
                                <td className="border p-2 text-center">
                                    <Input
                                        size="md"
                                        value={row.supervisor}
                                        onChange={e => {
                                            const newRows = [...rows];
                                            newRows[rowIndex].supervisor = e.target.value;
                                            setRows(newRows);
                                        }}
                                    />
                                </td>
                                {[...Array(31).keys()].map(colIndex => (
                                    <ComplyCell
                                        key={colIndex}
                                        rowIndex={rowIndex}
                                        colIndex={colIndex}
                                        cellColors={cellColors}
                                        setCellColors={setCellColors}
                                    />
                                ))}
                            </tr>
                        ))}
                        {fixedRows.map((fixedRow, rowIndex) => (
                            <tr key={rowIndex + rows.length}>
                                <td className="border p-2 text-center">{fixedRow.displayName}</td>
                                <td className="border p-2 text-center">
                                    <Input
                                        size="md"
                                        value={fixedSupervisors[rowIndex]}
                                        onChange={e => {
                                            const newSupervisors = [...fixedSupervisors];
                                            newSupervisors[rowIndex] = e.target.value;
                                            setFixedSupervisors(newSupervisors);
                                        }}
                                    />
                                </td>
                                {[...Array(31).keys()].map(colIndex => (
                                    <ComplyCell
                                        key={colIndex}
                                        rowIndex={`fixed-${rowIndex}`}
                                        colIndex={colIndex}
                                        cellColors={cellColors}
                                        setCellColors={setCellColors}
                                    />
                                ))}
                            </tr>
                        ))}
                        {floatRows.map((floatRow, rowIndex) => (
                            <tr key={rowIndex + rows.length + fixedRows.length}>
                                <td className="border p-2 text-center">{floatRow.displayName}</td>
                                <td className="border p-2 text-center">
                                    <Input
                                        size="md"
                                        value={floatSupervisors[rowIndex]}
                                        onChange={e => {
                                            const newSupervisors = [...floatSupervisors];
                                            newSupervisors[rowIndex] = e.target.value;
                                            setFloatSupervisors(newSupervisors);
                                        }}
                                    />
                                </td>
                                {["chlorine", "ph", "evacuation_volumes", "sampling_point"].includes(floatRow.name)
                                    ? [...Array(31).keys()].map(colIndex => (
                                        <FloatCell
                                            key={colIndex}
                                            rowIndex={rowIndex}
                                            colIndex={colIndex}
                                            floatValues={floatValues}
                                            setFloatValues={setFloatValues}
                                        />
                                    ))
                                    : [...Array(31).keys()].map(colIndex => (
                                        <ComplyCell
                                            key={colIndex}
                                            rowIndex={`float-${rowIndex}`}
                                            colIndex={colIndex}
                                            cellColors={cellColors}
                                            setCellColors={setCellColors}
                                        />
                                    ))}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>


            {/* Acciones correctivas */}

            <div className='mt-10'>
                <Typography variant="h5" className="mb-4">Acciones Correctivas</Typography>
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
                            {correctiveActions.map((action, index) => (
                                <tr key={index}>
                                    <td className="border p-2 text-center">
                                        <Input
                                            size="md"
                                            value={action.desviacion}
                                            onChange={e => handleCorrectiveChange(index, 'desviacion', e.target.value)}
                                            required
                                        />
                                    </td>
                                    <td className="border p-2 text-center">
                                        <Input
                                            size="md"
                                            type="date"
                                            value={action.fecha}
                                            onChange={e => handleCorrectiveChange(index, 'fecha', e.target.value)}
                                            required
                                        />
                                    </td>
                                    <td className="border p-2 text-center">
                                        <Input
                                            size="md"
                                            value={action.responsable}
                                            onChange={e => handleCorrectiveChange(index, 'responsable', e.target.value)}
                                            required
                                        />
                                    </td>
                                    <td className="border p-2 text-center">
                                        <Input
                                            size="md"
                                            value={action.accion}
                                            onChange={e => handleCorrectiveChange(index, 'accion', e.target.value)}
                                            required
                                        />
                                    </td>
                                    <td className="border p-2 text-center">
                                        <Input
                                            size="md"
                                            type="date"
                                            value={action.fechaCumplimiento}
                                            onChange={e => handleCorrectiveChange(index, 'fechaCumplimiento', e.target.value)}
                                            required
                                        />
                                    </td>
                                    <td className="border p-2 text-center">
                                        <Input
                                            size="md"
                                            value={action.firma}
                                            onChange={e => handleCorrectiveChange(index, 'firma', e.target.value)}
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

        </div>
    );
};

export default ProotTemplate;
