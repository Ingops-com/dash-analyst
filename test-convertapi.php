<?php
/**
 * Script de prueba para ConvertAPI
 * 
 * Uso: php test-convertapi.php
 * 
 * Verifica que la integración con ConvertAPI funcione correctamente
 * sin necesidad de generar un documento completo
 */

require __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;

// Cargar configuración
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiSecret = $_ENV['CONVERT_API_SECRET'] ?? '';

if (empty($apiSecret)) {
    echo "❌ ERROR: CONVERT_API_SECRET no está configurado en .env\n";
    echo "   Por favor agrega tu token de ConvertAPI al archivo .env\n";
    echo "   Ejemplo: CONVERT_API_SECRET=tu_token_aqui\n\n";
    echo "   Obtén tu token en: https://www.convertapi.com/a/auth\n";
    exit(1);
}

echo "🔍 Verificando conexión con ConvertAPI...\n\n";
echo "   Token: " . substr($apiSecret, 0, 10) . "..." . substr($apiSecret, -5) . "\n";
echo "   URL: https://v2.convertapi.com/convert/docx/to/pdf\n\n";

// Crear un documento DOCX de prueba muy simple
$testDocxPath = __DIR__ . '/storage/app/test_document.docx';
$testDir = dirname($testDocxPath);

if (!is_dir($testDir)) {
    mkdir($testDir, 0755, true);
}

// Crear un DOCX mínimo válido (ZIP con estructura básica)
$zip = new ZipArchive();
if ($zip->open($testDocxPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
    // Agregar estructura mínima de DOCX
    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
    
    $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
    
    $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>Test Document for ConvertAPI</w:t></w:r></w:p></w:body></w:document>');
    
    $zip->close();
    echo "✅ Documento de prueba creado: $testDocxPath\n\n";
} else {
    echo "❌ ERROR: No se pudo crear el documento de prueba\n";
    exit(1);
}

echo "🚀 Enviando documento a ConvertAPI...\n";

try {
    $client = new Client([
        'timeout' => 30,
        'verify' => true,
    ]);

    $startTime = microtime(true);
    
    $response = $client->post('https://v2.convertapi.com/convert/docx/to/pdf', [
        'headers' => [
            'Authorization' => 'Bearer ' . $apiSecret,
        ],
        'multipart' => [
            [
                'name' => 'StoreFile',
                'contents' => 'true'
            ],
            [
                'name' => 'File',
                'contents' => fopen($testDocxPath, 'r'),
                'filename' => 'test_document.docx',
            ]
        ]
    ]);

    $execTime = round(microtime(true) - $startTime, 2);

    if ($response->getStatusCode() === 200) {
        $result = json_decode($response->getBody()->getContents(), true);
        
        echo "✅ Conversión exitosa!\n\n";
        echo "   Tiempo de conversión: {$execTime} segundos\n";
        echo "   Tiempo reportado por API: {$result['ConversionTime']} segundos\n";
        
        if (isset($result['Files'][0])) {
            $file = $result['Files'][0];
            echo "   Archivo generado: {$file['FileName']}\n";
            echo "   Tamaño: " . round($file['FileSize'] / 1024, 2) . " KB\n";
            echo "   URL de descarga: {$file['Url']}\n\n";
            
            // Intentar descargar el PDF para verificar
            echo "📥 Descargando PDF generado...\n";
            $pdfResponse = $client->get($file['Url']);
            $pdfContent = $pdfResponse->getBody()->getContents();
            
            $pdfPath = __DIR__ . '/storage/app/test_output.pdf';
            file_put_contents($pdfPath, $pdfContent);
            
            echo "✅ PDF descargado exitosamente: $pdfPath\n";
            echo "   Tamaño del PDF: " . round(strlen($pdfContent) / 1024, 2) . " KB\n\n";
            
            echo "🎉 PRUEBA COMPLETADA EXITOSAMENTE!\n";
            echo "   ConvertAPI está funcionando correctamente.\n";
            echo "   La aplicación puede usar este servicio para conversión DOCX a PDF.\n\n";
        }
    } else {
        echo "❌ ERROR: Respuesta inesperada de ConvertAPI\n";
        echo "   Status Code: {$response->getStatusCode()}\n";
        echo "   Body: {$response->getBody()->getContents()}\n";
        exit(1);
    }
    
} catch (\GuzzleHttp\Exception\ClientException $e) {
    echo "❌ ERROR DEL CLIENTE (4xx):\n";
    echo "   Código: {$e->getCode()}\n";
    
    if ($e->hasResponse()) {
        $errorBody = $e->getResponse()->getBody()->getContents();
        $errorData = json_decode($errorBody, true);
        
        echo "   Mensaje: " . ($errorData['Message'] ?? $e->getMessage()) . "\n\n";
        
        if ($e->getCode() === 401) {
            echo "   CAUSA: Token de autenticación inválido\n";
            echo "   SOLUCIÓN: Verifica que CONVERT_API_SECRET en .env sea correcto\n";
            echo "            Obtén tu token en: https://www.convertapi.com/a/auth\n";
        } elseif ($e->getCode() === 402) {
            echo "   CAUSA: Cuota de conversiones agotada\n";
            echo "   SOLUCIÓN: Actualiza tu plan en https://www.convertapi.com/prices\n";
            echo "            O espera al siguiente ciclo de facturación\n";
        }
    } else {
        echo "   Mensaje: {$e->getMessage()}\n";
    }
    
    exit(1);
    
} catch (\GuzzleHttp\Exception\ServerException $e) {
    echo "❌ ERROR DEL SERVIDOR (5xx):\n";
    echo "   Código: {$e->getCode()}\n";
    echo "   Mensaje: {$e->getMessage()}\n";
    echo "\n   CAUSA: Problema temporal en ConvertAPI\n";
    echo "   SOLUCIÓN: Reintentar en unos minutos\n";
    exit(1);
    
} catch (\GuzzleHttp\Exception\RequestException $e) {
    echo "❌ ERROR DE CONEXIÓN:\n";
    echo "   Mensaje: {$e->getMessage()}\n";
    echo "\n   CAUSA: No se pudo conectar con ConvertAPI\n";
    echo "   SOLUCIÓN: Verifica tu conexión a internet\n";
    exit(1);
    
} catch (\Exception $e) {
    echo "❌ ERROR INESPERADO:\n";
    echo "   Mensaje: {$e->getMessage()}\n";
    echo "   Archivo: {$e->getFile()}\n";
    echo "   Línea: {$e->getLine()}\n";
    exit(1);
}

// Limpiar archivos de prueba
echo "\n🧹 Limpiando archivos de prueba...\n";
if (file_exists($testDocxPath)) {
    unlink($testDocxPath);
    echo "   ✓ test_document.docx eliminado\n";
}

echo "\n✨ Test completado!\n";
