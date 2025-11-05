# Guía Rápida: Agregar Nuevos Documentos

## 📝 Paso 1: Preparar la Plantilla Word

1. Crea tu plantilla `.docx` con los placeholders necesarios:
   ```
   ${COMPANY_NAME}
   ${COMPANY_ADDRESS}
   ${COMPANY_NIT}
   ${PROGRAM_NAME}
   ${PROGRAM_VERSION}
   ${ANNEX_1}  ← Primera imagen del anexo 1
   ${ANNEX_2}  ← Primera imagen del anexo 2
   etc.
   ```

2. Guarda la plantilla en una carpeta descriptiva:
   ```
   storage/plantillas/nombreDelDocumento/Plantilla.docx
   ```

## 🎯 Paso 2: Crear el Programa desde la Interfaz

1. Ve a `/programas`
2. Click en **"Agregar Programa"**
3. Completa los campos:

   - **Nombre**: Nombre completo del documento
   - **Versión**: 1.0 (o la versión actual)
   - **Código**: Código único (ej: `MLD-001`)
   - **Fecha**: Fecha de creación
   - **Tipo**: ISO 22000, PSB, o Invima
   - **Ruta Plantilla**: `nombreDelDocumento/Plantilla.docx`
   - **Descripción**: Propósito del documento y anexos requeridos

4. Click en **"Guardar Programa"**

## 📎 Paso 3: Crear y Vincular Anexos

1. Click en **"Agregar Anexo"**
2. Completa:
   - **Nombre**: Nombre del anexo
   - **Código**: Código único (ej: `ANX-HSQ-01`)
   - **Tipo**: Categoría del anexo
   - **Vincular a Programas**: Selecciona los programas que usan este anexo

3. Click en **"Guardar Anexo"**

## ✅ Verificar Configuración

Ejecuta el script de verificación:

```bash
php tools/check_templates.php
```

Esto mostrará:
- ✓ Plantillas disponibles
- ✓ Programas con plantilla configurada
- ⚠ Programas sin plantilla
- ✗ Plantillas configuradas pero que no existen

## 🚀 Uso por Empresa

1. La empresa ingresa al sistema
2. Selecciona el programa/documento
3. Sube archivos para cada anexo requerido
4. Click en **"Generar Documento"**
5. El sistema:
   - Carga la plantilla configurada
   - Llena los datos de la empresa
   - Inserta las imágenes de los anexos
   - Genera el documento final con header/footer

## 📊 Ejemplo Completo: Manual de Limpieza

### 1. Crear estructura de carpetas:
```
storage/plantillas/
└── manualLimpieza/
    └── Plantilla.docx
```

### 2. Crear el programa desde la interfaz:
```
Nombre: Manual de Limpieza y Desinfección
Versión: 1.0
Código: MLD-001
Tipo: PSB
Ruta Plantilla: manualLimpieza/Plantilla.docx
Descripción: Procedimientos de limpieza y desinfección
```

### 3. Crear anexos:
```
Anexo 1:
  Nombre: Hoja de Seguridad de Químicos
  Código: ANX-HSQ-01
  Vincular a: [Manual de Limpieza]

Anexo 2:
  Nombre: Cronograma de Limpieza
  Código: ANX-CL-02
  Vincular a: [Manual de Limpieza]
```

### 4. Verificar:
```bash
php tools/check_templates.php
```

Deberías ver:
```
ID 4: Manual de Limpieza y Desinfección (MLD-001)
  ✓ Plantilla: manualLimpieza/Plantilla.docx (existe)
```

## 🔧 Comandos Útiles

### Ver todos los programas:
```sql
SELECT id, nombre, codigo, template_path FROM programs;
```

### Actualizar plantilla de un programa existente:
```sql
UPDATE programs 
SET template_path = 'carpeta/Plantilla.docx',
    description = 'Descripción del documento'
WHERE id = X;
```

### Ver anexos de un programa:
```sql
SELECT a.* FROM annexes a 
JOIN program_annexes pa ON a.id = pa.annex_id 
WHERE pa.program_id = X;
```

## ⚠️ Problemas Comunes

### Problema: "La plantilla no se encontró"
**Solución**: Verifica que el archivo existe en la ruta exacta:
```bash
php tools/check_templates.php
```

### Problema: "Este programa no tiene una plantilla configurada"
**Solución**: 
1. Edita el programa (cuando implementemos EDIT)
2. O actualiza directamente:
```sql
UPDATE programs SET template_path = 'ruta/correcta.docx' WHERE id = X;
```

### Problema: Las imágenes no aparecen en el documento
**Solución**: 
- Verifica que los anexos tienen archivos subidos
- Verifica que los placeholders coinciden: `${ANNEX_1}`, `${ANNEX_2}`, etc.
- El sistema usa el primer archivo de cada anexo

## 📈 Escalabilidad

El sistema está diseñado para manejar **30+ documentos** fácilmente:

- ✅ Cada programa tiene su propia plantilla
- ✅ Los anexos se reutilizan entre programas
- ✅ No requiere cambios en el código para nuevos documentos
- ✅ Fácil mantenimiento y organización

Para agregar documento #31, simplemente repite los pasos 1-3 de esta guía.
