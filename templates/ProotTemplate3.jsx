import { useState, useContext, useEffect } from "react";
import { Input, Button } from "@material-tailwind/react";
import Note from "@/widgets/notes/Note";
import { TemplatesContext } from "@/context/templates/templatesContext";
import { TFIRMPIContext } from "@/context/templates/T_FIRMPI/TfirmpiContext";
import { useParams } from 'react-router-dom';
import HeaderTable from "@/widgets/tables/header/HeaderTable";
import { DownloadFiles } from "@/widgets/buttons/downloadComponentes";

export function ProotTemplate3() {
    const { createTFirmpi, getTFirmpi, dataTable, editTFirmpi, dataTableAc, downloadExcel, downloadPDF, getCorrectiveAction, createCorrectiveAction, date } = useContext(TFIRMPIContext);
    const [rows, setRows] = useState([]);
    const [correctiveRows, setCorrectiveRows] = useState([]);
    const [cellColors, setCellColors] = useState({});
    const { dataTemplates, getTemplates } = useContext(TemplatesContext);
    const { id } = useParams();
    const [newActions, setNewActions] = useState(false);
    const [todayDateFormat, setTodayDateFormat] = useState(new Date().toISOString().split('T')[0]);

    useEffect(() => {
        getTemplates();
    }, []);

    useEffect(() => {
        if (id && dataTable == null) {
            getTFirmpi(id);
        }
    }, [dataTable]);

    useEffect(() => {
        if (id && dataTableAc == null) {
            getCorrectiveAction(id);
        }
    }, [dataTableAc]);

    useEffect(() => {
        if (dataTable) {
            const newRows = dataTable.map((item, index) => ({
                fechaIngreso: item.fechaIngreso,
                materiaPrima: item.materiaPrima,
                lote: item.lote,
                fechaVencimiento: item.fechaVencimiento,
                cantidad: item.cantidad,
                unidades: item.unidades,
                proveedor: item.proveedor,
                temperatura: item.temperatura,
                placa: item.placa,
                motivo: item.motivo,
                entrega: item.entrega,
                recibe: item.recibe,
                recibido: item.recibido === 1 ? 'Sí' : item.recibido === 2 ? 'No' : '',
            }));

            const newCellColors = {};
            dataTable.forEach((item, rowIndex) => {
                newCellColors[`${rowIndex}-0`] = item.etiqueta === 1 ? 'green' : item.etiqueta === 2 ? 'red' : item.etiqueta === 3 ? 'yellow' : 'white';
                newCellColors[`${rowIndex}-1`] = item.impurezas === 1 ? 'green' : item.impurezas === 2 ? 'red' : item.impurezas === 3 ? 'yellow' : 'white';
                newCellColors[`${rowIndex}-2`] = item.color === 1 ? 'green' : item.color === 2 ? 'red' : item.color === 3 ? 'yellow' : 'white';
                newCellColors[`${rowIndex}-3`] = item.olor === 1 ? 'green' : item.olor === 2 ? 'red' : item.olor === 3 ? 'yellow' : 'white';
                newCellColors[`${rowIndex}-4`] = item.textura === 1 ? 'green' : item.textura === 2 ? 'red' : item.textura === 3 ? 'yellow' : 'white';
                newCellColors[`${rowIndex}-5`] = item.estadoEmpaque === 1 ? 'green' : item.estadoEmpaque === 2 ? 'red' : item.estadoEmpaque === 3 ? 'yellow' : 'white';
                newCellColors[`receive-${rowIndex}`] = item.recibido === 1 ? 'green' : item.recibido === 2 ? 'red' : 'white';
            });

            setRows(newRows);
            setCellColors(newCellColors);
        }
    }, [dataTable]);

    useEffect(() => {
        if (dataTableAc) {
            const newCorrectiveRows = dataTableAc.map((item, index) => ({
                desviacion: item.desviacion,
                fecha: item.fecha,
                responsable: item.responsable,
                accion: item.accion,
                fechaCumplimiento: item.fechaCumplimiento,
                firma: item.firma,
            }));
            setCorrectiveRows(newCorrectiveRows);
        }
    }, [dataTableAc]);

    const addRow = () => {
        setRows([...rows, {
            fechaIngreso: '',
            materiaPrima: '',
            lote: '',
            fechaVencimiento: '',
            cantidad: '',
            unidades: '',
            proveedor: '',
            placa: '',
            motivo: '',
            recibe: '',
            entrega: ''
        }]);
    };

    const addCorrectiveRow = () => {
        setCorrectiveRows([...correctiveRows, {
            desviacion: '',
            fecha: '',
            responsable: '',
            accion: '',
            fechaCumplimiento: '',
            firma: ''
        }]);
        setNewActions(true);
    };

    const handleCellClick = (rowIndex, colIndex, field) => {
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

    const handleReceiveClick = (rowIndex) => {
        const cellKey = `receive-${rowIndex}`;
        const currentColor = cellColors[cellKey] || 'white';

        let newColor, newText;
        if (currentColor === 'white') {
            newColor = 'green';
            newText = 'Sí';
        } else if (currentColor === 'green') {
            newColor = 'red';
            newText = 'No';
        } else {
            newColor = 'white';
            newText = '';
        }

        setCellColors({ ...cellColors, [cellKey]: newColor });
        const newRows = [...rows];
        newRows[rowIndex].recibido = newText;
        setRows(newRows);
    };

    function getData() {
        return rows.map((row, rowIndex) => {
            const rowData = {
                fechaIngreso: row.fechaIngreso,
                materiaPrima: row.materiaPrima,
                lote: row.lote,
                fechaVencimiento: row.fechaVencimiento,
                cantidad: row.cantidad,
                unidades: row.unidades,
                proveedor: row.proveedor,
                placa: row.placa,
                motivo: row.motivo,
                recibe: row.recibe,
                entrega: row.entrega,
                temperatura: row.temperatura,
                etiqueta: cellColors[`${rowIndex}-0`] === 'green' ? 1 : cellColors[`${rowIndex}-0`] === 'red' ? 2 : cellColors[`${rowIndex}-0`] === 'yellow' ? 3 : 0,
                impurezas: cellColors[`${rowIndex}-1`] === 'green' ? 1 : cellColors[`${rowIndex}-1`] === 'red' ? 2 : cellColors[`${rowIndex}-1`] === 'yellow' ? 3 : 0,
                color: cellColors[`${rowIndex}-2`] === 'green' ? 1 : cellColors[`${rowIndex}-2`] === 'red' ? 2 : cellColors[`${rowIndex}-2`] === 'yellow' ? 3 : 0,
                olor: cellColors[`${rowIndex}-3`] === 'green' ? 1 : cellColors[`${rowIndex}-3`] === 'red' ? 2 : cellColors[`${rowIndex}-3`] === 'yellow' ? 3 : 0,
                textura: cellColors[`${rowIndex}-4`] === 'green' ? 1 : cellColors[`${rowIndex}-4`] === 'red' ? 2 : cellColors[`${rowIndex}-4`] === 'yellow' ? 3 : 0,
                estadoEmpaque: cellColors[`${rowIndex}-5`] === 'green' ? 1 : cellColors[`${rowIndex}-5`] === 'red' ? 2 : cellColors[`${rowIndex}-5`] === 'yellow' ? 3 : 0,
                recibido: cellColors[`receive-${rowIndex}`] === 'green' ? 1 : cellColors[`receive-${rowIndex}`] === 'red' ? 2 : 0
            };
            return rowData;
        });
    }

    const handleSaveCA = async () => {
        const correctiveData = correctiveRows.map(row => ({
            desviacion: row.desviacion,
            fecha: row.fecha,
            responsable: row.responsable,
            accion: row.accion,
            fechaCumplimiento: row.fechaCumplimiento,
            firma: row.firma
        }));

        await createCorrectiveAction(id, correctiveData)
        setNewActions(false)
    };

    const TABLE_HEAD = [
        "FECHA DE INGRESO", "MATERIA PRIMA", "LOTE", "FECHA DE VENCIMIENTO", "CANTIDAD DE MATERIA PRIMA",
        "UNIDADES CANASTILLAS KILOS ETC", "PROVEEDOR", "PLACA VEHICULO", , "TEMPERATURA (SI APLICA)", "ETIQUETA", "IMPUREZAS", "COLOR",
        "OLOR", "TEXTURA", "ESTADO DEL EMPAQUE", "RECIBIDO", "INDIQUE EL MOTIVO SI MARCO NO EN LA CASILLA ANTERIOR", "RECIBE", "ENTREGA"
    ];

    const CORRECTIVE_TABLE_HEAD = [
        "DESVIACIÓN ESPECÍFICA", "FECHA", "RESPONSABLE", "ACCIÓN CORRECTIVA", "FECHA DE CUMPLIMIENTO DE LA ACCIÓN CORRECTIVA", "FIRMA"
    ];

    const handleSave = async () => {
        const data = getData();

        if (dataTable) {
            // Lógica de UPDATE
            await editTFirmpi(id, data);
        } else {
            // Lógica de CREATE
            await createTFirmpi(data);
        }
    };

    return (
        <div className="mt-12 fadeIn">
            <Note
                title={""}
                content={"Cada matería prima e insumo debe ser sometida a una inspección organoléptica verificando color, olor, sabor, textura y apariencia característica de cada alimento. \n Cada materia prima e insumo debe tener un rotulo o etiqueta esta debe decir el nombre del alimento de manera clara, debe decir que contiene o ingredientes, nombre dirección y teléfono del fabricante. \n Debe tener impreso de forma legible un lote y una fecha de vencimiento. \n Su empaque no debe presentar ningún tipo de desgaste, suciedad, o parecer que este ya fue utilizado. \n El trasportador de las materias primas debe presentar el concepto sanitario del vehículo vigente ó sea que no sea expedido con vigencia mayor a un año \n Debe presentarse con un uniforme adecuado para poder a las instalaciones. \n NOTA: CUANDO SE TRATA DE UN PRODUCTO QUE REQUIERE REFRIGERACIÓN O CONGELACIÓN, SE DEBE VERIFICAR LA TEMPERATURA REFRIGERACIÓN DEBE ENCONTRARSE DENTRO DE 0 Y 4°C CONGELACIÓN 0 Y -18°C"}
            />

            <div className='w-full flex justify-between my-5'>
                <div className="flex flex-row gap-5 ">
                    <Button onClick={addRow} color="green" variant="gradient">
                        AGREGAR FILA
                    </Button>
                    <Button color="blue" variant="gradient" onClick={handleSave}>
                        GUARDAR DATOS
                    </Button>
                </div>
                <div>
                    <DownloadFiles createPDF={() => downloadPDF(id)} createExcel={() => downloadExcel(id)} />
                </div>
            </div>

            <HeaderTable dataTemplates={dataTemplates} dateString={date ? date : todayDateFormat} name='formato de inspeccion en recepcion de materias primas e insumos' version_id={6} />

            <div className="w-full overflow-x-auto">
                <table className="w-full border-collapse min-w-max">
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
                                        type="date"
                                        size="md"
                                        value={row.fechaIngreso}
                                        onChange={e => {
                                            const newRows = [...rows];
                                            newRows[rowIndex].fechaIngreso = e.target.value;
                                            setRows(newRows);
                                        }}
                                    />
                                </td>
                                <td className="border p-2 text-center">
                                    <Input
                                        size="md"
                                        value={row.materiaPrima}
                                        onChange={e => {
                                            const newRows = [...rows];
                                            newRows[rowIndex].materiaPrima = e.target.value;
                                            setRows(newRows);
                                        }}
                                    />
                                </td>
                                <td className="border p-2 text-center">
                                    <Input
                                        size="md"
                                        value={row.lote}
                                        onChange={e => {
                                            const newRows = [...rows];
                                            newRows[rowIndex].lote = e.target.value;
                                            setRows(newRows);
                                        }}
                                    />
                                </td>
                                <td className="border p-2 text-center">
                                    <Input
                                        type="date"
                                        size="md"
                                        value={row.fechaVencimiento}
                                        onChange={e => {
                                            const newRows = [...rows];
                                            newRows[rowIndex].fechaVencimiento = e.target.value;
                                            setRows(newRows);
                                        }}
                                    />
                                </td>
                                <td className="border p-2 text-center">
                                    <Input
                                        size="md"
                                        value={row.cantidad}
                                        onChange={e => {
                                            const newRows = [...rows];
                                            newRows[rowIndex].cantidad = e.target.value;
                                            setRows(newRows);
                                        }}
                                    />
                                </td>
                                <td className="border p-2 text-center">
                                    <Input
                                        size="md"
                                        value={row.unidades}
                                        onChange={e => {
                                            const newRows = [...rows];
                                            newRows[rowIndex].unidades = e.target.value;
                                            setRows(newRows);
                                        }}
                                    />
                                </td>
                                <td className="border p-2 text-center">
                                    <Input
                                        size="md"
                                        value={row.proveedor}
                                        onChange={e => {
                                            const newRows = [...rows];
                                            newRows[rowIndex].proveedor = e.target.value;
                                            setRows(newRows);
                                        }}
                                    />
                                </td>
                                <td className="border p-2 text-center">
                                    <Input
                                        size="md"
                                        value={row.placa}
                                        onChange={e => {
                                            const newRows = [...rows];
                                            newRows[rowIndex].placa = e.target.value;
                                            setRows(newRows);
                                        }}
                                    />
                                </td>
                                <td className="border p-2 text-center">
                                    <Input
                                        size="md"
                                        value={row.temperatura}
                                        onChange={e => {
                                            const newRows = [...rows];
                                            newRows[rowIndex].temperatura = e.target.value;
                                            setRows(newRows);
                                        }}
                                    />
                                </td>
                                {['etiqueta', 'impurezas', 'color', 'olor', 'textura', 'estadoEmpaque',].map((field, colIndex) => (
                                    <td
                                        key={colIndex}
                                        className="border p-2 text-center"
                                        style={{ backgroundColor: cellColors[`${rowIndex}-${colIndex}`] || 'white' }}
                                        onClick={() => handleCellClick(rowIndex, colIndex, field)}
                                    >
                                        {cellColors[`${rowIndex}-${colIndex}`] === 'green' ? 'Cumple' : cellColors[`${rowIndex}-${colIndex}`] === 'red' ? 'No Cumple' : cellColors[`${rowIndex}-${colIndex}`] === 'yellow' ? 'No Aplica' : ''}
                                    </td>
                                ))}
                                <td
                                    className="border p-2 text-center"
                                    style={{ backgroundColor: cellColors[`receive-${rowIndex}`] || 'white' }}
                                    onClick={() => handleReceiveClick(rowIndex)}
                                >
                                    {rows[rowIndex].recibido}
                                </td>
                                <td className="border p-2 text-center">
                                    <Input
                                        size="md"
                                        value={row.motivo}
                                        onChange={e => {
                                            const newRows = [...rows];
                                            newRows[rowIndex].motivo = e.target.value;
                                            setRows(newRows);
                                        }}
                                    />
                                </td>
                                <td className="border p-2 text-center">
                                    <Input
                                        size="md"
                                        value={row.recibe}
                                        onChange={e => {
                                            const newRows = [...rows];
                                            newRows[rowIndex].recibe = e.target.value;
                                            setRows(newRows);
                                        }}
                                    />
                                </td>
                                <td className="border p-2 text-center">
                                    <Input
                                        size="md"
                                        value={row.entrega}
                                        onChange={e => {
                                            const newRows = [...rows];
                                            newRows[rowIndex].entrega = e.target.value;
                                            setRows(newRows);
                                        }}
                                    />
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {dataTable ?
                <div className="mt-12 fadeIn">
                    <div className="flex mb-5 w-full justify-center gap-5 mt-5">
                        {newActions ? (
                            <Button onClick={handleSaveCA} color="blue" variant="gradient">
                                GUARDAR DATOS
                            </Button>
                        ) : (
                            <Button onClick={addCorrectiveRow} color="green" variant="gradient">
                                AGREGAR ACCIÓN CORRECTIVA
                            </Button>
                        )}
                    </div>
                    <div className="overflow-x-scroll">
                        <table className="w-full border-collapse">
                            <thead>
                                <tr>
                                    {CORRECTIVE_TABLE_HEAD.map((header, index) => (
                                        <th key={index} className="border p-2 text-center">
                                            {header}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {correctiveRows.map((row, rowIndex) => (
                                    <tr key={rowIndex}>
                                        <td className="border p-2 text-center">
                                            <Input
                                                size="md"
                                                value={row.desviacion}
                                                onChange={(e) => {
                                                    const newRows = [...correctiveRows];
                                                    newRows[rowIndex].desviacion = e.target.value;
                                                    setCorrectiveRows(newRows);
                                                }}
                                            />
                                        </td>
                                        <td className="border p-2 text-center">
                                            <Input
                                                type="date"
                                                size="md"
                                                value={row.fecha}
                                                onChange={(e) => {
                                                    const newRows = [...correctiveRows];
                                                    newRows[rowIndex].fecha = e.target.value;
                                                    setCorrectiveRows(newRows);
                                                }}
                                            />
                                        </td>
                                        <td className="border p-2 text-center">
                                            <Input
                                                size="md"
                                                value={row.responsable}
                                                onChange={(e) => {
                                                    const newRows = [...correctiveRows];
                                                    newRows[rowIndex].responsable = e.target.value;
                                                    setCorrectiveRows(newRows);
                                                }}
                                            />
                                        </td>
                                        <td className="border p-2 text-center">
                                            <Input
                                                size="md"
                                                value={row.accion}
                                                onChange={(e) => {
                                                    const newRows = [...correctiveRows];
                                                    newRows[rowIndex].accion = e.target.value;
                                                    setCorrectiveRows(newRows);
                                                }}
                                            />
                                        </td>
                                        <td className="border p-2 text-center">
                                            <Input
                                                type="date"
                                                size="md"
                                                value={row.fechaCumplimiento}
                                                onChange={(e) => {
                                                    const newRows = [...correctiveRows];
                                                    newRows[rowIndex].fechaCumplimiento = e.target.value;
                                                    setCorrectiveRows(newRows);
                                                }}
                                            />
                                        </td>
                                        <td className="border p-2 text-center">
                                            <Input
                                                size="md"
                                                value={row.firma}
                                                onChange={(e) => {
                                                    const newRows = [...correctiveRows];
                                                    newRows[rowIndex].firma = e.target.value;
                                                    setCorrectiveRows(newRows);
                                                }}
                                            />
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
                :
                <> </>
            }
        </div>

    );
}

export default ProotTemplate3;
