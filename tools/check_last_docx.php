<?php
$dir = __DIR__ . '/../storage/app/temp';
$files = glob($dir . '/final_document_*.docx');
if (!$files) { echo "No generated docx found\n"; exit(1); }
rsort($files);
$last = $files[0];
echo "Checking: $last\n";
$zip = new ZipArchive();
if ($zip->open($last) === TRUE) {
    $idx = $zip->locateName('word/document.xml');
    if ($idx === false) {
        echo "document.xml not found in docx\n";
        $zip->close();
        exit(1);
    }
    $xml = $zip->getFromName('word/document.xml');
    $zip->close();
    $checks = ['Test Company', '900', 'Testing', 'Plan de Saneamiento', 'Anexo 1', 'Anexo 2', 'Anexo 3', 'Anexo 4', 'Anexo 5', 'Poes'];
    foreach ($checks as $c) {
        $found = strpos($xml, $c) !== false ? 'FOUND' : 'MISSING';
        echo str_pad($c,20) . ": " . $found . PHP_EOL;
    }
        // List media files
        $zip = new ZipArchive();
        if ($zip->open($last) === TRUE) {
            echo "\nMedia files in docx:\n";
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (str_starts_with($name, 'word/media/')) echo " - " . $name . PHP_EOL;
            }
            $zip->close();
        }
} else {
    echo "Failed to open zip\n";
}
