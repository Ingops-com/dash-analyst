import React, { useState, useEffect, useContext } from "react";
import { Input, Button, Typography, Radio } from "@material-tailwind/react";
import HeaderTable from "@/widgets/tables/header/HeaderTable";
import { TDesnaturalizacionProductosContext } from "@/context/templates/T-formato-desnaturalizacion-productos/TemplateActaDesnaturalizacionContext";
import { useParams, useNavigate } from "react-router-dom";
import { DownloadFiles } from "@/widgets/buttons/downloadComponentes";

const TemplateActaDesnaturalizacion = () => {
    const { id } = useParams();
    const navigate = useNavigate();
    const {
        dataTemplate,
        editDesnaturalizacionProductos,
        createDesnaturalizacionProductos,
        getDesnaturalizacionProductos,
        downloadPDF,
        downloadExcel
    } = useContext(TDesnaturalizacionProductosContext);
    const [rows, setRows] = useState([]);
    const [correctiveRows, setCorrectiveRows] = useState([]);
    const [conceptoCalidad, setConceptoCalidad] = useState('');
    const [autorizaDesnaturalizacion, setAutorizaDesnaturalizacion] = useState('');
    const [empresaRecolectora, setEmpresaRecolectora] = useState('');
    const [isEditing, setIsEditing] = useState(false);

    // Al cargar el componente, obtenemos los datos
    useEffect(() => {
        getDesnaturalizacionProductos(id);
    }, [id]);

    // Cuando dataTemplate cambia, actualizamos el estado
    useEffect(() => {
        if (dataTemplate && Object.keys(dataTemplate).length > 0) {
            // Si hay datos en dataTemplate, los rellenamos en el estado y activamos modo edición
            setRows(dataTemplate.rows || []);
            setCorrectiveRows(dataTemplate.correctiveRows || []);
            setConceptoCalidad(dataTemplate.conceptoCalidad || '');
            setAutorizaDesnaturalizacion(dataTemplate.autorizaDesnaturalizacion || '');
            setEmpresaRecolectora(dataTemplate.empresaRecolectora || '');
            setIsEditing(true);
        } else {
            // Si no hay datos, nos aseguramos de que esté todo vacío
            setRows([]);
            setCorrectiveRows([]);
            setConceptoCalidad('');
            setAutorizaDesnaturalizacion('');
            setEmpresaRecolectora('');
            setIsEditing(false);
        }
    }, [dataTemplate]);

    const addRow = () => {
        setRows([...rows, {
            fechaIncidente: '',
            producto: '',
            lote: '',
            fechaVencimiento: '',
            cantidad: '',
            proveedor: '',
            motivo: ''
        }]);
    };

    const addCorrectiveRow = () => {
        setCorrectiveRows([...correctiveRows, {
            fecha: '',
            desviacion: '',
            responsable: '',
            accion: ''
        }]);
    };

    const deleteRow = (rowIndex) => {
        const newRows = rows.filter((_, index) => index !== rowIndex);
        setRows(newRows);
    };

    const deleteCorrectiveRow = (rowIndex) => {
        const newCorrectiveRows = correctiveRows.filter((_, index) => index !== rowIndex);
        setCorrectiveRows(newCorrectiveRows);
    };

    const saveData = () => {
        const data = {
            rows,
            correctiveRows,
            conceptoCalidad,
            autorizaDesnaturalizacion,
            empresaRecolectora,
        };

        if (isEditing) {
            editDesnaturalizacionProductos(id, data).then(() => {
                console.log("Datos editados:", data);
                navigate(0); // Recargamos la página para reflejar los cambios
            });
        } else {
            createDesnaturalizacionProductos(data).then(() => {
                console.log("Datos creados:", data);
                navigate("/dashboard/mis-planillas"); // Redirigir a la ruta específica después de crear
            });
        }
    };

    const TABLE_HEADERS = [
        "FECHA INCIDENTE", "PRODUCTO-MATERIA PRIMA-REFERENCIA", "LOTE", "FECHA DE VENCIMIENTO",
        "CANTIDAD", "PROVEEDOR", "MOTIVO", "ACCIÓN"
    ];

    const CORRECTIVE_HEADERS = [
        "FECHA", "DESVIACIÓN ESPECÍFICA", "RESPONSABLE", "ACCIÓN CORRECTIVA", "ACCIÓN"
    ];

    return (
        <div className="p-5">
            <HeaderTable dataTemplates={[{ version: 'Version prueba', id: 7 }]} dateString={'00-00-1999'} name='ACTA DE DESNATURALIZACIÓN DE PRODUCTOS Y/O MATERIA PRIMA' version_id={7} />


            {/* Tabla de datos */}
            <div className="overflow-x-scroll">
                <table className="w-full border-collapse mb-5">
                    <thead>
                        <tr>
                            {TABLE_HEADERS.map((header, index) => (
                                <th key={index} className="border p-2 text-center">{header}</th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((row, rowIndex) => (
                            <tr key={rowIndex}>
                                <td className="border p-2 text-center">
                                    <Input type="date" size="md" value={row.fechaIncidente} onChange={(e) => {
                                        const newRows = [...rows];
                                        newRows[rowIndex].fechaIncidente = e.target.value;
                                        setRows(newRows);
                                    }} />
                                </td>
                                <td className="border p-2 text-center">
                                    <Input size="md" value={row.producto} onChange={(e) => {
                                        const newRows = [...rows];
                                        newRows[rowIndex].producto = e.target.value;
                                        setRows(newRows);
                                    }} />
                                </td>
                                <td className="border p-2 text-center">
                                    <Input size="md" value={row.lote} onChange={(e) => {
                                        const newRows = [...rows];
                                        newRows[rowIndex].lote = e.target.value;
                                        setRows(newRows);
                                    }} />
                                </td>
                                <td className="border p-2 text-center">
                                    <Input type="date" size="md" value={row.fechaVencimiento} onChange={(e) => {
                                        const newRows = [...rows];
                                        newRows[rowIndex].fechaVencimiento = e.target.value;
                                        setRows(newRows);
                                    }} />
                                </td>
                                <td className="border p-2 text-center">
                                    <Input size="md" value={row.cantidad} onChange={(e) => {
                                        const newRows = [...rows];
                                        newRows[rowIndex].cantidad = e.target.value;
                                        setRows(newRows);
                                    }} />
                                </td>
                                <td className="border p-2 text-center">
                                    <Input size="md" value={row.proveedor} onChange={(e) => {
                                        const newRows = [...rows];
                                        newRows[rowIndex].proveedor = e.target.value;
                                        setRows(newRows);
                                    }} />
                                </td>
                                <td className="border p-2 text-center">
                                    <Input size="md" value={row.motivo} onChange={(e) => {
                                        const newRows = [...rows];
                                        newRows[rowIndex].motivo = e.target.value;
                                        setRows(newRows);
                                    }} />
                                </td>
                                <td className="border p-2 text-center">
                                    <Button color="red" variant="gradient" onClick={() => deleteRow(rowIndex)}>
                                        ELIMINAR
                                    </Button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <div className="flex justify-center">
                <Button onClick={addRow} color="green" variant="gradient" className="mb-5">
                    AGREGAR FILA
                </Button>
            </div>

            <div className="mb-5">
                <Typography variant="h6" className="mb-2">CONCEPTO DPT CALIDAD</Typography>
                <Input
                    size="md"
                    value={conceptoCalidad}
                    onChange={(e) => setConceptoCalidad(e.target.value)}
                    placeholder="Escriba el concepto del departamento de calidad"
                    className="mb-4"
                />

                <Typography variant="h6" className="mb-2">AUTORIZA DESNATURALIZACIÓN</Typography>
                <Input
                    size="md"
                    value={autorizaDesnaturalizacion}
                    onChange={(e) => setAutorizaDesnaturalizacion(e.target.value)}
                    placeholder="Nombre de quien autoriza la desnaturalización"
                    className="mb-4"
                />

                <Typography variant="h6" className="mb-2">SE DEBE DISPONER A EMPRESA RECOLECTORA</Typography>
                <div className="flex items-center gap-5 mb-4">
                    <Radio
                        id="si"
                        name="empresaRecolectora"
                        label="Sí"
                        onChange={() => setEmpresaRecolectora("Sí")}
                        checked={empresaRecolectora === "Sí"}
                    />
                    <Radio
                        id="no"
                        name="empresaRecolectora"
                        label="No"
                        onChange={() => setEmpresaRecolectora("No")}
                        checked={empresaRecolectora === "No"}
                    />
                </div>
            </div>


            <Typography variant="h6" className="mb-4">Acciones Correctivas</Typography>

            <table className="w-full border-collapse mb-5">
                <thead>
                    <tr>
                        {CORRECTIVE_HEADERS.map((header, index) => (
                            <th key={index} className="border p-2 text-center">{header}</th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {correctiveRows.map((row, rowIndex) => (
                        <tr key={rowIndex}>
                            <td className="border p-2 text-center">
                                <Input type="date" size="md" value={row.fecha} onChange={(e) => {
                                    const newRows = [...correctiveRows];
                                    newRows[rowIndex].fecha = e.target.value;
                                    setCorrectiveRows(newRows);
                                }} />
                            </td>
                            <td className="border p-2 text-center">
                                <Input size="md" value={row.desviacion} onChange={(e) => {
                                    const newRows = [...correctiveRows];
                                    newRows[rowIndex].desviacion = e.target.value;
                                    setCorrectiveRows(newRows);
                                }} />
                            </td>
                            <td className="border p-2 text-center">
                                <Input size="md" value={row.responsable} onChange={(e) => {
                                    const newRows = [...correctiveRows];
                                    newRows[rowIndex].responsable = e.target.value;
                                    setCorrectiveRows(newRows);
                                }} />
                            </td>
                            <td className="border p-2 text-center">
                                <Input size="md" value={row.accion} onChange={(e) => {
                                    const newRows = [...correctiveRows];
                                    newRows[rowIndex].accion = e.target.value;
                                    setCorrectiveRows(newRows);
                                }} />
                            </td>
                            <td className="border p-2 text-center">
                                <Button color="red" variant="gradient" onClick={() => deleteCorrectiveRow(rowIndex)}>
                                    ELIMINAR
                                </Button>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>

            <div className="flex justify-center gap-5">
                <Button onClick={addCorrectiveRow} color="green" variant="gradient">
                    AGREGAR ACCIÓN CORRECTIVA
                </Button>
                <Button onClick={saveData} color="blue" variant="gradient">
                    GUARDAR DATOS
                </Button>
                <DownloadFiles createPDF={() => downloadPDF(id)} createExcel={() => downloadExcel(id)} />
            </div>
        </div>
    );
};

export default TemplateActaDesnaturalizacion;
