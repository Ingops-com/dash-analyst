# Exportación a PDF de Alta Fidelidad con LibreOffice

## Descripción

El sistema ahora utiliza **LibreOffice** como motor principal para convertir documentos DOCX a PDF, garantizando:

- ✅ **Alta fidelidad**: Preserva todos los estilos, formatos, tablas y diseños
- ✅ **Compatibilidad**: Soporta elementos complejos de Word
- ✅ **Imágenes**: Maneja correctamente imágenes incrustadas y formatos especiales
- ✅ **Multiplataforma**: Funciona en Windows, Linux y macOS
- ✅ **Gratuito**: LibreOffice es software libre

## Orden de Prioridad de Conversión

El sistema intenta convertir el documento en este orden:

1. **LibreOffice** (recomendado) - Máxima fidelidad
2. **Microsoft Word COM** (solo Windows con Office instalado)
3. **Endpoint HTTP** (si está configurado)
4. **DomPDF** (fallback de baja fidelidad)

## Instalación de LibreOffice

### Windows

#### Opción 1: Script Automático (Recomendado)

```powershell
# Ejecutar como administrador
.\install-libreoffice.ps1
```

#### Opción 2: Instalación con winget

```powershell
winget install --id TheDocumentFoundation.LibreOffice --silent
```

#### Opción 3: Instalación Manual

1. Descargar desde: https://www.libreoffice.org/download/download/
2. Ejecutar el instalador como administrador
3. Seguir el asistente de instalación

### Linux (Ubuntu/Debian)

```bash
sudo apt update
sudo apt install libreoffice
```

### Linux (CentOS/RHEL)

```bash
sudo yum install libreoffice
```

### macOS

```bash
brew install --cask libreoffice
```

O descargar desde: https://www.libreoffice.org/download/download/

## Verificación de la Instalación

### Desde Terminal

```bash
# Windows
"C:\Program Files\LibreOffice\program\soffice.exe" --version

# Linux/macOS
libreoffice --version
```

### Desde PHP/Laravel

```bash
php artisan tinker
```

```php
// Verificar si está disponible
App\Services\LibreOfficePdfConverter::isAvailable()
// Debería retornar: true

// Obtener versión instalada
App\Services\LibreOfficePdfConverter::getVersion()
// Ejemplo: "7.6.4.1"
```

## Uso en el Sistema

La conversión con LibreOffice se activa automáticamente cuando:

1. LibreOffice está instalado en el sistema
2. El usuario genera un documento PDF desde el programa
3. El sistema detecta la presencia de LibreOffice

No se requiere configuración adicional. El sistema selecciona automáticamente el mejor motor disponible.

## Monitoreo y Logs

Puedes verificar qué motor se utilizó en los logs de Laravel:

```bash
# Ver logs en tiempo real
tail -f storage/logs/laravel.log

# Ver últimas 50 líneas
php artisan tail

# Buscar conversiones de PDF
grep "PDF generado exitosamente" storage/logs/laravel.log
```

El log mostrará mensajes como:

```
[2025-12-10 10:30:45] local.INFO: Convirtiendo a PDF con LibreOffice... {"version":"7.6.4.1"}
[2025-12-10 10:30:47] local.INFO: PDF generado exitosamente con LibreOffice {"pdf_path":"...","size_kb":1523.45}
```

## Configuración Avanzada

### Variable de Entorno Personalizada

Si LibreOffice está instalado en una ubicación no estándar:

```env
# .env
LIBREOFFICE_PATH="C:\Custom\Path\LibreOffice\program\soffice.exe"
```

### Troubleshooting

#### LibreOffice no se detecta automáticamente

1. Verifica que esté instalado correctamente
2. Reinicia tu terminal/IDE después de instalar
3. Verifica la ruta con: `where soffice.exe` (Windows) o `which libreoffice` (Linux/Mac)

#### Error: "LibreOffice no encontrado en el sistema"

- Instala LibreOffice usando una de las opciones anteriores
- Verifica que el ejecutable esté en el PATH del sistema
- Define manualmente `LIBREOFFICE_PATH` en el archivo `.env`

#### PDF generado pero con formato incorrecto

- Verifica que el DOCX fuente esté bien formado
- Revisa los logs para ver si se usó un motor de fallback
- Prueba convertir el DOCX manualmente con LibreOffice para descartar problemas en el documento

#### Conversión muy lenta

- LibreOffice puede tardar 2-5 segundos en la primera conversión
- Conversiones subsecuentes suelen ser más rápidas
- Considera aumentar recursos del servidor en producción

## Ventajas sobre Otros Métodos

### vs DomPDF (Fallback Actual)

- ❌ DomPDF: Baja fidelidad, problemas con tablas complejas y estilos
- ✅ LibreOffice: Alta fidelidad, soporta todos los elementos de Word

### vs Microsoft Word COM

- ❌ Word COM: Solo Windows, requiere Office instalado, licencia comercial
- ✅ LibreOffice: Multiplataforma, gratuito, no requiere licencias

### vs Servicios en la Nube

- ❌ Cloud: Requiere internet, costos recurrentes, problemas de privacidad
- ✅ LibreOffice: Local, sin costos, datos privados

## Recomendaciones de Producción

1. **Instalar LibreOffice en servidor de producción**
2. **Verificar instalación después de deploy**
3. **Monitorear logs para detectar fallbacks**
4. **Considerar headless mode** (ya configurado por defecto con `--headless`)

## Soporte

Si encuentras problemas con la conversión a PDF:

1. Verifica los logs: `storage/logs/laravel.log`
2. Confirma que LibreOffice está instalado
3. Prueba conversión manual: `soffice --convert-to pdf archivo.docx`
4. Revisa permisos del directorio temporal

## Referencias

- LibreOffice Official: https://www.libreoffice.org/
- Documentación de línea de comandos: https://wiki.documentfoundation.org/Documentation/HowTo/CommandLine
