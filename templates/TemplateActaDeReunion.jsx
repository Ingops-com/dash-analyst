import React, { useContext, useState, useEffect } from "react";
import { Button, Input, Textarea } from "@material-tailwind/react";
import HeaderTable from "@/widgets/tables/header/HeaderTable";
import { TActaReunionContext } from "@/context/templates/T-acta-de-reunion/TemplateActaDeReunionContext";
import { useParams, useNavigate } from "react-router-dom";
import { DownloadFiles } from "@/widgets/buttons/downloadComponentes";

export default function TemplateActaDeReunion() {
  const { id } = useParams();
  const navigate = useNavigate();
  const {
    editActaReunion,
    createActaReunion,
    getActaReunion,
    dataTemplate,
    downloadPDF,
    downloadExcel
  } = useContext(TActaReunionContext);

  const [temas, setTemas] = useState("");
  const [personas, setPersonas] = useState([]);
  const [fecha, setFecha] = useState("");
  const [horaInicio, setHoraInicio] = useState("");
  const [horaFinalizacion, setHoraFinalizacion] = useState("");
  const [encargado, setEncargado] = useState("");
  const [isEditing, setIsEditing] = useState(false);

  useEffect(() => {
    getActaReunion(id);
  }, [id]);

  useEffect(() => {
    if (dataTemplate && Object.keys(dataTemplate).length > 0) {
      setTemas(dataTemplate.temas || "");
      setPersonas(dataTemplate.personas || []);
      setFecha(dataTemplate.fecha || "");
      setHoraInicio(dataTemplate.horaInicio || "");
      setHoraFinalizacion(dataTemplate.horaFinalizacion || "");
      setEncargado(dataTemplate.encargado || "");
      setIsEditing(true);
    } else {
      setTemas("");
      setPersonas([{ nombre: "", cedula: "", firma: "" }]);
      setFecha("");
      setHoraInicio("");
      setHoraFinalizacion("");
      setEncargado("");
      setIsEditing(false);
    }
  }, [dataTemplate]);

  const addPersona = () => {
    setPersonas([...personas, { nombre: "", cedula: "", firma: "" }]);
  };

  const deletePersona = (index) => {
    const newPersonas = personas.filter((_, i) => i !== index);
    setPersonas(newPersonas);
  };

  const handleInputChange = (e, index, field) => {
    const newPersonas = [...personas];
    newPersonas[index][field] = e.target.value;
    setPersonas(newPersonas);
  };

  const handleSave = () => {
    const actaData = {
      temas,
      personas,
      fecha,
      horaInicio,
      horaFinalizacion,
      encargado,
    };

    if (isEditing) {
      editActaReunion(id, actaData).then(() => {
        navigate(0);
      });
    } else {
      createActaReunion(actaData).then(() => {
        navigate("/dashboard/mis-planillas");
      });
    }
  };

  return (
    <div className="mt-12">
      {/* HeaderTable */}
      <HeaderTable
        dataTemplates={[{ version: "Version prueba", id: 7 }]}
        dateString={"00-00-1999"}
        name="ACTA DE REUNIÓN"
        version_id={7}
      />

      {/* Campos fuera de la tabla */}
      <div className="mb-5">
        <div className="mb-4">
          <label>Fecha</label>
          <Input
            value={fecha}
            onChange={(e) => setFecha(e.target.value)}
            type="date"
          />
        </div>
        <div className="mb-4">
          <label>Hora de inicio</label>
          <Input
            value={horaInicio}
            onChange={(e) => setHoraInicio(e.target.value)}
            type="time"

          />
        </div>
        <div className="mb-4">
          <label> Hora de Finalización</label>
          <Input
            value={horaFinalizacion}
            onChange={(e) => setHoraFinalizacion(e.target.value)}
            type="time"
          />
        </div>
        <div className="mb-4">
          <label>Encargado</label>
          <Input
            value={encargado}
            onChange={(e) => setEncargado(e.target.value)}
          />
        </div>
      </div>

      {/* Temas a tratar */}
      <div className="mb-5">
        <label>Temas a Tratar</label>
        <Textarea
          value={temas}
          onChange={(e) => setTemas(e.target.value)}
        />
      </div>

      {/* Tabla de personas */}
      <div className="overflow-x-scroll">
        <table className="w-full border-collapse">
          <thead>
            <tr>
              <th className="border p-2 text-center">NOMBRE</th>
              <th className="border p-2 text-center">CÉDULA</th>
              <th className="border p-2 text-center">FIRMA</th>
              <th className="border p-2 text-center">ACCIÓN</th>
            </tr>
          </thead>
          <tbody>
            {personas.map((persona, index) => (
              <tr key={index}>
                <td className="border p-2">
                  <Input
                    value={persona.nombre}
                    onChange={(e) => handleInputChange(e, index, "nombre")}
                    placeholder="Nombre"
                  />
                </td>
                <td className="border p-2">
                  <Input
                    value={persona.cedula}
                    onChange={(e) => handleInputChange(e, index, "cedula")}
                    placeholder="Cédula"
                  />
                </td>
                <td className="border p-2">
                  <Input
                    value={persona.firma}
                    onChange={(e) => handleInputChange(e, index, "firma")}
                    placeholder="Firma"
                  />
                </td>
                <td className="border p-2 text-center">
                  <Button color="red" onClick={() => deletePersona(index)}>
                    Eliminar
                  </Button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {/* Botones para agregar persona y guardar datos */}
      <div className="flex justify-center mt-5 gap-5">
        <Button color="green" variant="gradient" onClick={addPersona}>
          Agregar Persona
        </Button>
        <Button color="blue" variant="gradient" onClick={handleSave}>
          Guardar Datos
        </Button>
        <DownloadFiles createPDF={() => downloadPDF(id)} createExcel={() => downloadExcel(id)} />
      </div>
    </div>
  );
}
