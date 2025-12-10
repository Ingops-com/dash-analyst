# Guía de Conversión DOCX a PDF

Esta aplicación utiliza múltiples métodos para convertir documentos DOCX a PDF, con un sistema de fallback automático para garantizar la máxima disponibilidad.

## Métodos de Conversión (en orden de prioridad)

### 1. ConvertAPI (Método Principal) ✅

**Servicio:** [ConvertAPI](https://www.convertapi.com/)  
**Ventajas:**
- Alta fidelidad de conversión
- Servicio en la nube confiable
- No requiere instalación local
- Soporta documentos complejos con imágenes y estilos
- API REST simple y bien documentada

**Configuración:**
```env
CONVERT_API_SECRET=tu_token_aqui
```

**Obtener Token:**
1. Crear cuenta en https://www.convertapi.com/
2. Ir a https://www.convertapi.com/a/auth
3. Copiar tu "Secret" token
4. Agregar al archivo `.env`

**Plan Gratuito:**
- 250 conversiones/mes gratuitas
- Sin tarjeta de crédito requerida
- Suficiente para testing y proyectos pequeños

**Costos:**
- Planes desde $9.99/mes para 1,500 conversiones
- Pay-as-you-go disponible
- Ver precios: https://www.convertapi.com/prices

**Ejemplo de Request:**
```bash
curl -X POST https://v2.convertapi.com/convert/docx/to/pdf \
  -H "Authorization: Bearer TU_TOKEN_AQUI" \
  -F "StoreFile=true" \
  -F "File=@/path/to/documento.docx"
```

**Respuesta:**
```json
{
  "ConversionTime": 2,
  "Files": [
    {
      "FileName": "documento.pdf",
      "FileSize": 245678,
      "Url": "https://v2.convertapi.com/d/ABC123/documento.pdf"
    }
  ]
}
```

### 2. Endpoint HTTP Externo (Fallback)

**Servicio:** Endpoint Python Flask personalizado  
**URL:** `http://178.16.141.125:5050/convert`

**Ventajas:**
- Gratuito
- Control total del servicio

**Desventajas:**
- Requiere mantener servidor
- Disponibilidad no garantizada
- Puede tener problemas con documentos complejos

**Configuración:**
```env
PDF_CONVERSION_ENDPOINT=http://178.16.141.125:5050/convert
```

**Nota:** Este método se usa automáticamente si ConvertAPI falla o no está configurado.

### 3. MS Word COM (Fallback Final - Solo Windows)

**Requisitos:**
- Windows OS
- Microsoft Word instalado
- Extensión PHP COM habilitada

**Ventajas:**
- No requiere conexión a internet
- Conversión nativa de Word
- Gratuito (si ya tienes Word)

**Desventajas:**
- Solo funciona en Windows
- Requiere Word instalado
- No funciona en servidores Linux
- Más lento que métodos HTTP

**Configuración:**
No requiere configuración. Se activa automáticamente si:
1. ConvertAPI falla
2. Endpoint externo no disponible
3. Sistema operativo es Windows
4. COM está habilitado en PHP

## Flujo de Conversión

```
┌─────────────────────┐
│ Generar Documento   │
│      (DOCX)         │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  ConvertAPI         │◄─── Método Principal
│  (Cloud Service)    │
└──────────┬──────────┘
           │ ✓ Success
           ├──────────────────┐
           │ ✗ Fail           │
           ▼                  ▼
┌─────────────────────┐  ┌──────────┐
│ Endpoint Externo    │  │   PDF    │
│ (Flask API)         │  │ Generado │
└──────────┬──────────┘  └──────────┘
           │ ✓ Success
           ├──────────────────┘
           │ ✗ Fail
           ▼
┌─────────────────────┐
│  MS Word COM        │
│  (Solo Windows)     │
└──────────┬──────────┘
           │ ✓ Success
           ├──────────────────┐
           │ ✗ Fail           │
           ▼                  ▼
┌─────────────────────┐  ┌──────────┐
│   Error Total       │  │   PDF    │
│  (No PDF generado)  │  │ Generado │
└─────────────────────┘  └──────────┘
```

## Configuración Recomendada

### Para Producción:
```env
# Token de ConvertAPI (REQUERIDO)
CONVERT_API_SECRET=tu_token_real_aqui

# Endpoint fallback (OPCIONAL)
PDF_CONVERSION_ENDPOINT=http://tu-servidor.com/convert
```

### Para Desarrollo Local:
```env
# Usar endpoint local si tienes uno
PDF_CONVERSION_ENDPOINT=http://localhost:5050/convert

# O usar ConvertAPI con plan gratuito
CONVERT_API_SECRET=tu_token_de_prueba
```

## Logs y Debugging

El sistema registra automáticamente cada intento de conversión:

```php
// Logs generados automáticamente
Log::info('Convirtiendo DOCX a PDF usando ConvertAPI');
Log::warning('ConvertAPI falló, intentando con endpoint fallback');
Log::error('Todos los métodos de conversión fallaron');
```

**Ver logs:**
```bash
# Logs en tiempo real
php artisan tail

# O revisar archivo
tail -f storage/logs/laravel.log
```

## Troubleshooting

### ConvertAPI retorna error 401
**Causa:** Token inválido o no configurado  
**Solución:** Verificar `CONVERT_API_SECRET` en `.env`

### ConvertAPI retorna error 402
**Causa:** Cuota de conversiones agotada  
**Solución:** Actualizar plan o esperar al próximo mes

### Todos los métodos fallan
**Causa:** Problemas de conectividad o configuración  
**Solución:** 
1. Verificar logs en `storage/logs/laravel.log`
2. Probar endpoint manualmente con curl
3. Verificar que el archivo DOCX existe

### PDF generado está corrupto
**Causa:** Documento DOCX tiene errores o elementos no soportados  
**Solución:** 
1. Verificar plantilla DOCX
2. Probar conversión manual en Word
3. Revisar logs para errores específicos

## Testing Manual

### Probar ConvertAPI:
```bash
curl -X POST https://v2.convertapi.com/convert/docx/to/pdf \
  -H "Authorization: Bearer TU_TOKEN" \
  -F "StoreFile=true" \
  -F "File=@ruta/a/tu/documento.docx"
```

### Probar Endpoint Local:
```bash
curl -X POST http://localhost:5050/convert \
  -F "file=@ruta/a/tu/documento.docx" \
  --output salida.pdf
```

## Monitoreo

Se recomienda monitorear:
- Tasa de éxito de ConvertAPI
- Uso de cuota mensual
- Tiempo promedio de conversión
- Tasa de fallback a métodos alternativos

Todos estos datos están disponibles en los logs de Laravel.

## Recursos Adicionales

- **ConvertAPI Docs:** https://www.convertapi.com/doc/node
- **ConvertAPI Dashboard:** https://www.convertapi.com/a
- **Support:** https://www.convertapi.com/support

## Código Relevante

Toda la lógica de conversión está en:
```
app/Http/Controllers/ProgramController.php
├── convertDocxToPdfWithLibreOffice()  # Método principal
├── tryConvertWithConvertAPI()         # ConvertAPI
├── tryConvertWithExternalEndpoint()   # Fallback HTTP
└── tryConvertWithMsWord()             # Fallback Windows
```
