<?php
// Test script: verificar que Word COM está accesible desde PHP
try {
    if (!class_exists('COM')) {
        die("ERROR: Clase COM no disponible. Extensión php_com_dotnet no está cargada.\n");
    }
    
    echo "COM class disponible. Intentando crear instancia de Word...\n";
    
    $word = new COM('Word.Application');
    
    if (!$word) {
        die("ERROR: No se pudo crear instancia de Word.Application\n");
    }
    
    echo "✓ Microsoft Word COM accesible exitosamente\n";
    echo "Versión de Word: " . $word->Version . "\n";
    
    $word->Quit(false);
    $word = null;
    
    echo "\n✓ Todo OK. Word COM está funcionando correctamente.\n";
    
} catch (Exception $e) {
    echo "ERROR al intentar acceder a Word COM:\n";
    echo $e->getMessage() . "\n";
    echo "\nPosibles causas:\n";
    echo "1. Microsoft Word no está instalado\n";
    echo "2. Versión de Word incompatible con PHP bitness\n";
    echo "3. Permisos insuficientes para automatización COM\n";
}
