# Script para preparar el proyecto para despliegue en producción
# Ejecutar desde la raíz del proyecto: .\deploy-prepare.ps1

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  PREPARANDO PROYECTO PARA DESPLIEGUE  " -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# 1. Verificar que estamos en la raíz correcta
if (-not (Test-Path ".\artisan")) {
    Write-Host "[ERROR] No se encuentra el archivo artisan. Estas en la raiz del proyecto?" -ForegroundColor Red
    exit 1
}

Write-Host "[OK] Ubicacion del proyecto verificada" -ForegroundColor Green

# 2. Limpiar cachés
Write-Host ""
Write-Host "Limpiando caches de Laravel..." -ForegroundColor Yellow
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
Write-Host "[OK] Caches limpiados" -ForegroundColor Green

# 3. Instalar dependencias de Composer (producción)
Write-Host ""
Write-Host "Instalando dependencias de Composer (modo produccion)..." -ForegroundColor Yellow
composer install --optimize-autoloader --no-dev

if ($LASTEXITCODE -ne 0) {
    Write-Host "[ERROR] Error al instalar dependencias de Composer" -ForegroundColor Red
    exit 1
}
Write-Host "[OK] Dependencias de Composer instaladas" -ForegroundColor Green

# 4. Instalar dependencias de Node
Write-Host ""
Write-Host "Instalando dependencias de Node..." -ForegroundColor Yellow
npm install

if ($LASTEXITCODE -ne 0) {
    Write-Host "[ERROR] Error al instalar dependencias de Node" -ForegroundColor Red
    exit 1
}
Write-Host "[OK] Dependencias de Node instaladas" -ForegroundColor Green

# 5. Construir assets para producción
Write-Host ""
Write-Host "Construyendo assets de produccion..." -ForegroundColor Yellow
npm run build

if ($LASTEXITCODE -ne 0) {
    Write-Host "[ERROR] Error al construir assets" -ForegroundColor Red
    exit 1
}
Write-Host "[OK] Assets construidos exitosamente" -ForegroundColor Green

# 6. Verificar que public/build existe
if (-not (Test-Path ".\public\build")) {
    Write-Host "[ERROR] No se genero la carpeta public/build" -ForegroundColor Red
    exit 1
}
Write-Host "[OK] Carpeta public/build verificada" -ForegroundColor Green

# 7. Optimizar autoload
Write-Host ""
Write-Host "Optimizando autoload de Composer..." -ForegroundColor Yellow
composer dump-autoload --optimize
Write-Host "[OK] Autoload optimizado" -ForegroundColor Green

# 8. Cachear configuración
Write-Host ""
Write-Host "Cacheando configuracion para produccion..." -ForegroundColor Yellow
php artisan config:cache
php artisan route:cache
Write-Host "[OK] Configuracion cacheada" -ForegroundColor Green

# 9. Crear carpeta de despliegue
Write-Host ""
Write-Host "Preparando carpeta de despliegue..." -ForegroundColor Yellow

$deployFolder = ".\deploy-package"
if (Test-Path $deployFolder) {
    Remove-Item $deployFolder -Recurse -Force
}
New-Item -ItemType Directory -Path $deployFolder | Out-Null

# 10. Copiar archivos necesarios
Write-Host "Copiando archivos..." -ForegroundColor Yellow

$filesToCopy = @(
    "app",
    "bootstrap",
    "config",
    "database",
    "public",
    "resources",
    "routes",
    "storage",
    "vendor",
    "artisan",
    "composer.json",
    "composer.lock",
    ".env.example"
)

foreach ($item in $filesToCopy) {
    if (Test-Path $item) {
        Write-Host "  Copiando $item..." -ForegroundColor Gray
        Copy-Item -Path $item -Destination "$deployFolder\$item" -Recurse -Force
    } else {
        Write-Host "  [ADVERTENCIA] No se encontro: $item" -ForegroundColor Yellow
    }
}

# 11. Limpiar archivos innecesarios del paquete
Write-Host ""
Write-Host "Limpiando archivos innecesarios del paquete..." -ForegroundColor Yellow

# Limpiar storage de archivos temporales
$storagePaths = @(
    "$deployFolder\storage\logs\*.log",
    "$deployFolder\storage\framework\cache\data\*",
    "$deployFolder\storage\framework\sessions\*",
    "$deployFolder\storage\framework\views\*"
)

foreach ($path in $storagePaths) {
    if (Test-Path $path) {
        Remove-Item $path -Force -ErrorAction SilentlyContinue
    }
}

# Crear archivo .gitkeep en carpetas vacías
$gitkeepFolders = @(
    "$deployFolder\storage\logs",
    "$deployFolder\storage\framework\cache\data",
    "$deployFolder\storage\framework\sessions",
    "$deployFolder\storage\framework\views"
)

foreach ($folder in $gitkeepFolders) {
    if (Test-Path $folder) {
        New-Item -ItemType File -Path "$folder\.gitkeep" -Force | Out-Null
    }
}

Write-Host "[OK] Archivos limpiados" -ForegroundColor Green

# 12. Crear archivo ZIP
Write-Host ""
Write-Host "Creando archivo ZIP para despliegue..." -ForegroundColor Yellow

# Detener procesos PHP que puedan estar usando archivos
$phpProcesses = Get-Process -Name php -ErrorAction SilentlyContinue
if ($phpProcesses) {
    Write-Host "Deteniendo procesos PHP activos..." -ForegroundColor Yellow
    $phpProcesses | Stop-Process -Force -ErrorAction SilentlyContinue
    Start-Sleep -Seconds 2
}

$zipFile = ".\dash-analyst-deploy-$(Get-Date -Format 'yyyyMMdd-HHmmss').zip"

# Intentar comprimir con reintentos
$maxAttempts = 3
$attempt = 1
$success = $false

while ($attempt -le $maxAttempts -and -not $success) {
    try {
        if ($attempt -gt 1) {
            Write-Host "Reintento $attempt de $maxAttempts..." -ForegroundColor Yellow
            Start-Sleep -Seconds 2
        }
        
        Compress-Archive -Path "$deployFolder\*" -DestinationPath $zipFile -Force -ErrorAction Stop
        $success = $true
    } catch {
        Write-Host "Intento $attempt fallo: $($_.Exception.Message)" -ForegroundColor Yellow
        $attempt++
    }
}

if (Test-Path $zipFile) {
    $zipSize = [math]::Round(((Get-Item $zipFile).Length / 1MB), 2)
    Write-Host "[OK] Archivo ZIP creado: $zipFile ($zipSize MB)" -ForegroundColor Green
} else {
    Write-Host "[ERROR] No se pudo crear el archivo ZIP despues de $maxAttempts intentos" -ForegroundColor Red
    Write-Host "Sugerencia: Cierra manualmente el servidor PHP (php artisan serve) y otros procesos" -ForegroundColor Yellow
    Write-Host "La carpeta $deployFolder contiene los archivos listos para comprimir manualmente" -ForegroundColor Yellow
    exit 1
}

# 13. Limpiar carpeta temporal
Write-Host ""
Write-Host "Limpiando carpeta temporal..." -ForegroundColor Yellow
Remove-Item $deployFolder -Recurse -Force
Write-Host "[OK] Carpeta temporal eliminada" -ForegroundColor Green

# 14. Resumen final
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "      PREPARACION COMPLETADA       " -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Archivo listo para despliegue:" -ForegroundColor Yellow
Write-Host "   $zipFile" -ForegroundColor White
Write-Host ""
Write-Host "Proximos pasos:" -ForegroundColor Yellow
Write-Host "   1. Sube el archivo ZIP al servidor" -ForegroundColor White
Write-Host "   2. Descomprimelo en el directorio web" -ForegroundColor White
Write-Host "   3. Crea y configura el archivo .env" -ForegroundColor White
Write-Host "   4. Ejecuta: php artisan key:generate" -ForegroundColor White
Write-Host "   5. Ejecuta: php artisan storage:link" -ForegroundColor White
Write-Host "   6. Configura permisos (storage y bootstrap/cache)" -ForegroundColor White
Write-Host "   7. Importa la base de datos" -ForegroundColor White
Write-Host "   8. Configura el servidor web (Nginx/Apache)" -ForegroundColor White
Write-Host ""
Write-Host "Guia completa en: DEPLOYMENT_GUIDE.md" -ForegroundColor Cyan
Write-Host ""
