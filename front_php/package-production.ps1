# Script para empaquetar solo archivos necesarios para producción
# Uso: .\package-production.ps1

$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$outputDir = "front_php_production_$timestamp"
$zipFile = "front_php_production_$timestamp.zip"

Write-Host "📦 Empaquetando front_php para producción..." -ForegroundColor Cyan

# Crear directorio temporal
New-Item -ItemType Directory -Force -Path $outputDir | Out-Null

# Copiar archivos principales
Write-Host "✅ Copiando archivos principales..." -ForegroundColor Green
Copy-Item -Path "index.php" -Destination $outputDir
Copy-Item -Path ".htaccess" -Destination $outputDir
Copy-Item -Path "README.md" -Destination $outputDir -ErrorAction SilentlyContinue
Copy-Item -Path "DEPLOY.md" -Destination $outputDir -ErrorAction SilentlyContinue

# Copiar templates
Write-Host "✅ Copiando templates PHP..." -ForegroundColor Green
Copy-Item -Path "templates" -Destination $outputDir -Recurse -Force

# Copiar static (assets compilados)
Write-Host "✅ Copiando archivos compilados (static/)..." -ForegroundColor Green
Copy-Item -Path "static" -Destination $outputDir -Recurse -Force

# Copiar public (assets públicos)
Write-Host "✅ Copiando assets públicos (public/)..." -ForegroundColor Green
Copy-Item -Path "public" -Destination $outputDir -Recurse -Force

# Crear archivo .env.example si no existe
Write-Host "✅ Creando .env.example..." -ForegroundColor Green
$envContent = @"
# Configuración de Base de Datos
DB_TYPE=mysql
DB_HOST=localhost
DB_NAME=tu_base_datos
DB_USER=tu_usuario
DB_PASS=tu_contraseña
DB_PORT=3306
DB_CHARSET=utf8mb4

# URLs
BASE_URL=https://tudominio.com
"@
$envContent | Out-File -FilePath "$outputDir\.env.example" -Encoding UTF8

# Crear estructura de directorios vacía para logs (opcional)
# New-Item -ItemType Directory -Force -Path "$outputDir\logs" | Out-Null

# Comprimir todo
Write-Host "🗜️  Comprimiendo archivos..." -ForegroundColor Yellow
Compress-Archive -Path $outputDir -DestinationPath $zipFile -Force

# Limpiar directorio temporal
Remove-Item -Path $outputDir -Recurse -Force

Write-Host ""
Write-Host "✨ ¡Empaquetado completado!" -ForegroundColor Green
Write-Host "📦 Archivo creado: $zipFile" -ForegroundColor Cyan
Write-Host ""
Write-Host "📋 Contenido del paquete:" -ForegroundColor Yellow
Write-Host "  - index.php (router SSR)" -ForegroundColor White
Write-Host "  - .htaccess (Apache rewrites)" -ForegroundColor White
Write-Host "  - templates/ (Layout.php + ArticleView.php)" -ForegroundColor White
Write-Host "  - static/ (JS y CSS compilados)" -ForegroundColor White
Write-Host "  - public/ (Assets públicos)" -ForegroundColor White
Write-Host "  - DEPLOY.md (guía de instalación)" -ForegroundColor White
Write-Host "  - .env.example (template de configuración)" -ForegroundColor White
Write-Host ""
Write-Host "📊 Tamaño del paquete:" -ForegroundColor Yellow
$size = (Get-Item $zipFile).Length / 1MB
Write-Host "  $([math]::Round($size, 2)) MB" -ForegroundColor White
Write-Host ""
Write-Host "🚀 Siguiente paso:" -ForegroundColor Cyan
Write-Host "  1. Sube $zipFile a tu servidor" -ForegroundColor White
Write-Host "  2. Descomprime: unzip $zipFile" -ForegroundColor White
Write-Host "  3. Configura base de datos (ver DEPLOY.md)" -ForegroundColor White
Write-Host "  4. Configura Apache Virtual Host" -ForegroundColor White
Write-Host "  5. Reinicia Apache: sudo systemctl restart apache2" -ForegroundColor White
Write-Host ""
