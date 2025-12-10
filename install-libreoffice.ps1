# Script para verificar e instalar LibreOffice en Windows
# Ejecutar como administrador: .\install-libreoffice.ps1

Write-Host "=== Verificador/Instalador de LibreOffice ===" -ForegroundColor Cyan
Write-Host ""

# Verificar si LibreOffice ya está instalado
$libreOfficePaths = @(
    "C:\Program Files\LibreOffice\program\soffice.exe",
    "C:\Program Files (x86)\LibreOffice\program\soffice.exe"
)

$installed = $false
foreach ($path in $libreOfficePaths) {
    if (Test-Path $path) {
        $installed = $true
        Write-Host "✓ LibreOffice encontrado en: $path" -ForegroundColor Green
        
        # Obtener versión
        $version = & $path --version 2>&1 | Select-String "LibreOffice" | ForEach-Object { $_.Line }
        Write-Host "  Versión: $version" -ForegroundColor Gray
        break
    }
}

if ($installed) {
    Write-Host ""
    Write-Host "LibreOffice está instalado y listo para usar." -ForegroundColor Green
    Write-Host ""
    Write-Host "Puedes probar la conversión con:" -ForegroundColor Yellow
    Write-Host '  php artisan tinker' -ForegroundColor Gray
    Write-Host '  > App\Services\LibreOfficePdfConverter::isAvailable()' -ForegroundColor Gray
    Write-Host '  > App\Services\LibreOfficePdfConverter::getVersion()' -ForegroundColor Gray
    exit 0
}

Write-Host "✗ LibreOffice no está instalado." -ForegroundColor Red
Write-Host ""
Write-Host "Opciones de instalación:" -ForegroundColor Yellow
Write-Host ""
Write-Host "1. Instalación automática con winget (recomendado):" -ForegroundColor Cyan
Write-Host "   winget install --id TheDocumentFoundation.LibreOffice --silent" -ForegroundColor Gray
Write-Host ""
Write-Host "2. Instalación manual:" -ForegroundColor Cyan
Write-Host "   - Descargar desde: https://www.libreoffice.org/download/download/" -ForegroundColor Gray
Write-Host "   - Instalar la versión estable más reciente" -ForegroundColor Gray
Write-Host ""

$response = Read-Host "¿Deseas instalar LibreOffice automáticamente con winget? (S/N)"

if ($response -eq "S" -or $response -eq "s") {
    Write-Host ""
    Write-Host "Instalando LibreOffice..." -ForegroundColor Yellow
    
    # Verificar si winget está disponible
    $wingetPath = Get-Command winget -ErrorAction SilentlyContinue
    if (-not $wingetPath) {
        Write-Host "✗ winget no está disponible en este sistema." -ForegroundColor Red
        Write-Host "Por favor, instala LibreOffice manualmente desde:" -ForegroundColor Yellow
        Write-Host "https://www.libreoffice.org/download/download/" -ForegroundColor Cyan
        exit 1
    }
    
    # Instalar LibreOffice
    try {
        winget install --id TheDocumentFoundation.LibreOffice --silent --accept-package-agreements --accept-source-agreements
        
        Write-Host ""
        Write-Host "✓ LibreOffice instalado exitosamente." -ForegroundColor Green
        Write-Host ""
        Write-Host "IMPORTANTE: Es posible que necesites reiniciar tu terminal o IDE" -ForegroundColor Yellow
        Write-Host "para que los cambios surtan efecto." -ForegroundColor Yellow
        Write-Host ""
        Write-Host "Después de reiniciar, verifica la instalación con:" -ForegroundColor Cyan
        Write-Host "  php artisan tinker" -ForegroundColor Gray
        Write-Host "  > App\Services\LibreOfficePdfConverter::isAvailable()" -ForegroundColor Gray
    }
    catch {
        Write-Host "✗ Error durante la instalación: $_" -ForegroundColor Red
        Write-Host ""
        Write-Host "Por favor, instala LibreOffice manualmente desde:" -ForegroundColor Yellow
        Write-Host "https://www.libreoffice.org/download/download/" -ForegroundColor Cyan
        exit 1
    }
} else {
    Write-Host ""
    Write-Host "Para instalar LibreOffice manualmente:" -ForegroundColor Yellow
    Write-Host "1. Visita: https://www.libreoffice.org/download/download/" -ForegroundColor Gray
    Write-Host "2. Descarga la versión estable más reciente" -ForegroundColor Gray
    Write-Host "3. Ejecuta el instalador como administrador" -ForegroundColor Gray
    Write-Host "4. Una vez instalado, ejecuta este script nuevamente para verificar" -ForegroundColor Gray
}
