# Arquitectura de Documentos - Sistema Escalable para 30+ Plantillas

## 📊 Estructura de Base de Datos

### Tabla: `programs`
```sql
- id: INT (Primary Key, Auto Increment)
- nombre: VARCHAR(255) - Nombre del documento/programa
- version: VARCHAR(255) - Versión del documento
- codigo: VARCHAR(255) UNIQUE - Código único (ej: PSB-001, ISO-22000-001)
- fecha: DATE - Fecha del programa
- tipo: ENUM('ISO 22000', 'PSB', 'Invima') - Tipo/categoría
- template_path: VARCHAR(500) - Ruta relativa en storage/plantillas/
- description: TEXT - Descripción del propósito del documento
- created_at, updated_at: TIMESTAMP
```

### Tabla: `annexes`
```sql
- id: INT (Primary Key, Auto Increment)
- nombre: VARCHAR(255) - Nombre del anexo
- codigo_anexo: VARCHAR(255) - Código del anexo
- tipo: ENUM - Tipo/categoría del anexo
- status: ENUM - Estado del anexo
- created_at, updated_at: TIMESTAMP
```

### Tabla: `program_annexes` (Pivot - Many to Many)
```sql
- program_id: INT (FK → programs.id)
- annex_id: INT (FK → annexes.id)
```

### Tabla: `company_annex_submissions`
```sql
- id: INT (Primary Key, Auto Increment)
- company_id: INT (FK → companies.id)
- program_id: INT (FK → programs.id)
- annex_id: INT (FK → annexes.id)
- file_path: VARCHAR - Ruta del archivo subido
- file_name: VARCHAR - Nombre original del archivo
- mime_type: VARCHAR - Tipo MIME
- file_size: BIGINT - Tamaño en bytes
- status: ENUM('Pendiente', 'Aprobado', 'Rechazado')
- submitted_by: INT (FK → users.id)
- created_at, updated_at: TIMESTAMP
```

## 🏗️ Arquitectura para Escalabilidad

### Principios de Diseño

1. **Separación de Plantillas y Programas**
   - Cada programa apunta a su propia plantilla Word via `template_path`
   - Las plantillas se almacenan en `storage/plantillas/{carpeta}/{archivo}.docx`
   - Ejemplo: `storage/plantillas/planDeSaneamientoBasico/Plantilla.docx`

2. **Anexos Reutilizables**
   - Un anexo puede pertenecer a múltiples programas
   - Relación many-to-many a través de `program_annexes`
   - Ejemplo: "Certificado de Fumigación" puede estar en PSB y BPM

3. **Archivos por Empresa**
   - Los archivos subidos se guardan en `storage/app/public/anexos/company_{id}/program_{id}/`
   - Un registro en `company_annex_submissions` por cada archivo
   - Una empresa puede tener múltiples versiones del mismo anexo

4. **Generación Dinámica de Documentos**
   - El controlador lee `program.template_path` para saber qué plantilla usar
   - Los placeholders en la plantilla se llenan con datos de:
     - Company (nombre, dirección, NIT, etc.)
     - Program (nombre, versión, código, fecha)
     - Annexes (imágenes de los archivos subidos)

## 📁 Estructura de Archivos

```
storage/
├── plantillas/                          # Plantillas Word master
│   ├── planDeSaneamientoBasico/
│   │   └── Plantilla.docx              # Template PSB
│   ├── iso22000/
│   │   └── Plantilla.docx              # Template ISO 22000
│   ├── buenasPracticasManufactura/
│   │   └── Plantilla.docx              # Template BPM
│   ├── controlPlagas/
│   │   └── Plantilla.docx              # Template Control de Plagas
│   ├── capacitacionPersonal/
│   │   └── Plantilla.docx              # Template Capacitación
│   └── ...                              # +25 plantillas más
│
└── app/
    └── public/
        ├── anexos/                      # Archivos subidos por empresas
        │   ├── company_1/
        │   │   ├── program_1/
        │   │   │   ├── {uuid}_certificado.pdf
        │   │   │   └── {uuid}_foto.jpg
        │   │   └── program_2/
        │   │       └── {uuid}_manual.pdf
        │   └── company_2/
        │       └── ...
        └── logos/                       # Logos de empresas
            ├── company_1_logo.png
            └── company_2_logo.jpg
```

## 🔄 Flujo de Trabajo

### 1. Crear Nuevo Programa/Documento

```php
POST /programas
{
  "nombre": "Control de Temperaturas",
  "version": "1.0",
  "codigo": "CT-001",
  "tipo": "ISO 22000",
  "template_path": "controlTemperaturas/Plantilla.docx",
  "description": "Registro diario de temperaturas de equipos de refrigeración"
}
```

### 2. Vincular Anexos al Programa

```php
POST /anexos
{
  "nombre": "Registro de Temperaturas",
  "codigo_anexo": "ANX-RT-01",
  "tipo": "ISO 22000",
  "programIds": [4, 5, 6]  // Se puede vincular a múltiples programas
}
```

### 3. Empresa Sube Archivos para Anexos

```php
POST /programa/{programId}/annex/{annexId}/upload
{
  "company_id": 1,
  "file": <binary>
}
```

### 4. Generar Documento Final

```php
POST /programa/{programId}/generate-pdf
{
  "company_id": 1
}
```

**El sistema:**
1. Lee `program.template_path` → Carga la plantilla correcta
2. Busca los anexos vinculados al programa
3. Para cada anexo, busca los archivos subidos por la empresa
4. Reemplaza placeholders en la plantilla:
   - `${COMPANY_NAME}` → Nombre de la empresa
   - `${PROGRAM_NAME}` → Nombre del programa
   - `${ANNEX_1}` → Primera imagen del anexo 1
   - `${ANNEX_2}` → Primera imagen del anexo 2
   - etc.
5. Genera el documento final con header/footer personalizado

## 🎯 Ventajas de Esta Arquitectura

### Escalabilidad
✅ Fácil agregar nuevas plantillas (solo crear archivo + registro en DB)  
✅ No requiere cambios en el código para nuevos documentos  
✅ Un anexo puede reutilizarse en múltiples documentos  

### Mantenibilidad
✅ Plantillas separadas por carpeta (fácil de organizar)  
✅ Código genérico de generación (no hardcoded por tipo)  
✅ Logs detallados para debugging  

### Flexibilidad
✅ Diferentes empresas pueden tener diferentes anexos para el mismo programa  
✅ Versionado de documentos (campo version)  
✅ Estados de anexos (Pendiente, Aprobado, Rechazado)  

## 📝 Ejemplo: Agregar Documento #31

```sql
-- 1. Crear el programa
INSERT INTO programs (nombre, version, codigo, tipo, template_path, description) 
VALUES (
  'Manual de Limpieza y Desinfección',
  '1.0',
  'MLD-001',
  'PSB',
  'manualLimpiezaDesinfeccion/Plantilla.docx',
  'Procedimientos detallados de limpieza y desinfección de áreas'
);

-- 2. Crear anexos necesarios
INSERT INTO annexes (nombre, codigo_anexo, tipo) VALUES
('Hoja de Seguridad de Químicos', 'ANX-HSQ-01', 'PSB'),
('Cronograma de Limpieza', 'ANX-CL-02', 'PSB'),
('Registro de Limpieza Diaria', 'ANX-RLD-03', 'PSB');

-- 3. Vincular anexos al programa
INSERT INTO program_annexes (program_id, annex_id) VALUES
(31, <id_anexo_1>),
(31, <id_anexo_2>),
(31, <id_anexo_3>);

-- 4. Colocar la plantilla en:
--    storage/plantillas/manualLimpiezaDesinfeccion/Plantilla.docx
```

## 🚀 Próximos Pasos Recomendados

1. **Actualizar Frontend**
   - Agregar campo `template_path` en AddProgramDialog
   - Mostrar descripción del programa en la tarjeta
   - Selector de archivos para subir nueva plantilla

2. **Panel de Administración de Plantillas**
   - Listar todas las plantillas disponibles
   - Upload de nuevas plantillas
   - Preview de placeholders en cada plantilla

3. **Validación de Placeholders**
   - Script que lee una plantilla y lista sus placeholders
   - Verificar que todos los anexos tienen placeholders correspondientes

4. **Documentación de Placeholders**
   - Guía para crear nuevas plantillas
   - Lista de placeholders estándar disponibles
   - Ejemplos de templates bien formateados

## 🔍 Comandos Útiles

```bash
# Ver programas y sus plantillas
php -r "require 'vendor/autoload.php'; ..."

# Actualizar plantilla de un programa
UPDATE programs SET template_path = 'nuevaPlantilla/Template.docx' WHERE id = X;

# Ver qué anexos tiene un programa
SELECT a.* FROM annexes a 
JOIN program_annexes pa ON a.id = pa.annex_id 
WHERE pa.program_id = X;

# Ver archivos subidos de una empresa para un programa
SELECT * FROM company_annex_submissions 
WHERE company_id = X AND program_id = Y;
```

## ⚠️ Consideraciones Importantes

1. **Límite de Imágenes por Anexo en PhpWord**
   - PhpWord solo permite 1 imagen por placeholder
   - Si un anexo tiene múltiples archivos, se usa el primero
   - Para múltiples imágenes, usar `cloneBlock` en lugar de `setImageValue`

2. **Formato de Plantillas**
   - Evitar imágenes WMF/EMF (usar PNG/JPG)
   - Placeholders deben ser `${NOMBRE_VARIABLE}`
   - Header/footer deben ser compatibles con PhpWord

3. **Permisos de Archivos**
   - Directorio `storage/plantillas/` debe tener permisos de lectura
   - Directorio `storage/app/public/` debe tener permisos de escritura
   - Symlink `public/storage` debe existir

---

**Fecha de Creación:** 2025-11-03  
**Última Actualización:** 2025-11-03  
**Versión:** 1.0
