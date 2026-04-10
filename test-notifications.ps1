# ========================================
# SCRIPT DE PRUEBA RÁPIDA - NOTIFICACIONES
# ========================================
# 
# Ejecuta tests de notificaciones a buscadores desde PowerShell.
# Proporciona resumen visual de los resultados.
#
# Uso:
#   .\test-notifications.ps1
#   .\test-notifications.ps1 -Provider google
#   .\test-notifications.ps1 -Provider bing
#   .\test-notifications.ps1 -Provider indexnow
#

param(
    [string]$Provider = "all",
    [switch]$Verbose
)

# Colores
function Write-Success { param($msg) Write-Host "✓ $msg" -ForegroundColor Green }
function Write-Error { param($msg) Write-Host "✗ $msg" -ForegroundColor Red }
function Write-Info { param($msg) Write-Host "→ $msg" -ForegroundColor Cyan }
function Write-Warning { param($msg) Write-Host "⚠ $msg" -ForegroundColor Yellow }

# Banner
Write-Host ""
Write-Host "╔════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║   TEST DE NOTIFICACIONES - PORTFOLIO JCMS                 ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

# Verificar ubicación
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$adminPath = Join-Path $scriptPath "admin\pages"

if (-not (Test-Path $adminPath)) {
    Write-Error "No se encuentra el directorio admin/pages"
    Write-Info "Ejecutar desde la raíz del proyecto Portfolio"
    exit 1
}

$testScript = Join-Path $adminPath "test-notifications-cli.php"

if (-not (Test-Path $testScript)) {
    Write-Error "No se encuentra test-notifications-cli.php"
    Write-Info "Archivo esperado: $testScript"
    exit 1
}

# Verificar PHP
try {
    $phpVersion = php -v 2>$null
    if ($LASTEXITCODE -ne 0) {
        Write-Error "PHP no está instalado o no está en PATH"
        Write-Info "Instalar PHP o agregarlo a las variables de entorno"
        exit 1
    }
    
    Write-Success "PHP encontrado"
    
    if ($Verbose) {
        Write-Info "Versión: $($phpVersion[0])"
    }
} catch {
    Write-Error "Error al verificar PHP: $_"
    exit 1
}

# Ejecutar test
Write-Info "Ejecutando tests para: $Provider"
Write-Host ""

Set-Location $adminPath

try {
    $output = php test-notifications-cli.php $Provider 2>&1
    
    # Mostrar output
    $output | ForEach-Object {
        Write-Host $_
    }
    
    Write-Host ""
    
    # Analizar resultados
    if ($output -match "Todas las pruebas pasaron") {
        Write-Host ""
        Write-Success "DIAGNÓSTICO: Todas las configuraciones están correctas"
        Write-Host ""
        exit 0
    } elseif ($output -match "Algunas pruebas fallaron") {
        Write-Host ""
        Write-Warning "DIAGNÓSTICO: Revisar configuraciones con errores"
        Write-Info "Ver documentación: doc\GUIA-CONFIGURACION-NOTIFICACIONES.md"
        Write-Host ""
        exit 1
    } elseif ($output -match "Todas las pruebas fallaron") {
        Write-Host ""
        Write-Error "DIAGNÓSTICO: Configuración incompleta o incorrecta"
        Write-Info "Ver documentación: doc\GUIA-CONFIGURACION-NOTIFICACIONES.md"
        Write-Host ""
        exit 1
    } else {
        Write-Host ""
        Write-Info "Test completado - revisar salida"
        Write-Host ""
        exit 0
    }
} catch {
    Write-Error "Error al ejecutar test: $_"
    exit 1
} finally {
    Set-Location $scriptPath
}
