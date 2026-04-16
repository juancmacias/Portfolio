# Script para gestionar releases de GitHub usando gh CLI
# Uso: .\manage-release.ps1 -Action <list|create|draft> [-Version <v1.3.0>] [-Title <"Título">] [-NotesFile <archivo.md>]

param(
    [Parameter(Mandatory=$false)]
    [ValidateSet('list', 'create', 'draft', 'auth-status')]
    [string]$Action = 'list',
    
    [Parameter(Mandatory=$false)]
    [string]$Version = '',
    
    [Parameter(Mandatory=$false)]
    [string]$Title = '',
    
    [Parameter(Mandatory=$false)]
    [string]$NotesFile = '',
    
    [Parameter(Mandatory=$false)]
    [string]$Repo = 'juancmacias/Portfolio'
)

# Colores para output
function Write-ColorOutput($ForegroundColor) {
    $fc = $host.UI.RawUI.ForegroundColor
    $host.UI.RawUI.ForegroundColor = $ForegroundColor
    if ($args) {
        Write-Output $args
    }
    $host.UI.RawUI.ForegroundColor = $fc
}

# Verificar que gh está instalado
function Test-GhCli {
    try {
        $version = & gh --version 2>&1 | Select-Object -First 1
        Write-ColorOutput Green "✓ GitHub CLI instalado: $version"
        return $true
    }
    catch {
        Write-ColorOutput Red "✗ GitHub CLI no está instalado"
        Write-Output "Instala gh desde: https://cli.github.com/"
        return $false
    }
}

# Verificar autenticación
function Test-GhAuth {
    Write-ColorOutput Cyan "`n=== Estado de Autenticación ==="
    & gh auth status
    
    if ($LASTEXITCODE -ne 0) {
        Write-ColorOutput Yellow "`nNo estás autenticado. Ejecuta: gh auth login"
        return $false
    }
    return $true
}

# Listar releases existentes
function Get-Releases {
    Write-ColorOutput Cyan "`n=== Releases Existentes en $Repo ==="
    & gh release list --repo $Repo --limit 10
}

# Crear release
function New-Release {
    param(
        [bool]$IsDraft = $false
    )
    
    # Validaciones
    if ([string]::IsNullOrEmpty($Version)) {
        Write-ColorOutput Red "✗ Debes especificar una versión con -Version"
        return
    }
    
    if ([string]::IsNullOrEmpty($Title)) {
        $Title = "Release $Version"
    }
    
    # Construir ruta del archivo de notas si no se especificó
    if ([string]::IsNullOrEmpty($NotesFile)) {
        $NotesFile = "releases/RELEASE_NOTES_$Version.md"
    }
    
    # Verificar si existe el archivo de notas
    if (-not (Test-Path $NotesFile)) {
        Write-ColorOutput Yellow "⚠ Archivo de notas no encontrado: $NotesFile"
        $createNotes = Read-Host "¿Quieres crear notas automáticas? (s/n)"
        
        if ($createNotes -eq 's' -or $createNotes -eq 'S') {
            # Asegurar que existe la carpeta releases
            $releasesDir = "releases"
            if (-not (Test-Path $releasesDir)) {
                New-Item -ItemType Directory -Path $releasesDir -Force | Out-Null
            }
            
            $defaultNotes = @"
# $Title

## 🚀 Novedades

- Nueva funcionalidad agregada

## 🐛 Correcciones

- Bugs corregidos

## 📝 Cambios

- Mejoras generales
"@
            Set-Content -Path $NotesFile -Value $defaultNotes -Encoding UTF8
            Write-ColorOutput Green "✓ Archivo de notas creado: $NotesFile"
            Write-Output "Edita el archivo y vuelve a ejecutar el script."
            return
        } else {
            Write-ColorOutput Red "✗ Operación cancelada"
            return
        }
    }
    
    # Crear el release
    Write-ColorOutput Cyan "`n=== Creando Release ==="
    Write-Output "Versión: $Version"
    Write-Output "Título: $Title"
    Write-Output "Notas: $NotesFile"
    Write-Output "Repositorio: $Repo"
    Write-Output "Borrador: $(if ($IsDraft) { 'Sí' } else { 'No' })"
    
    $confirm = Read-Host "`n¿Continuar? (s/n)"
    if ($confirm -ne 's' -and $confirm -ne 'S') {
        Write-ColorOutput Yellow "Operación cancelada"
        return
    }
    
    $args = @(
        'release', 'create', $Version,
        '--title', $Title,
        '--notes-file', $NotesFile,
        '--repo', $Repo
    )
    
    if ($IsDraft) {
        $args += '--draft'
    }
    
    & gh @args
    
    if ($LASTEXITCODE -eq 0) {
        Write-ColorOutput Green "`n✓ Release creado exitosamente"
        if ($IsDraft) {
            Write-ColorOutput Yellow "→ Revisa el borrador en: https://github.com/$Repo/releases"
        } else {
            Write-ColorOutput Green "→ Ver release en: https://github.com/$Repo/releases/tag/$Version"
        }
    } else {
        Write-ColorOutput Red "`n✗ Error al crear el release"
    }
}

# MAIN
Clear-Host
Write-ColorOutput Cyan "╔════════════════════════════════════════╗"
Write-ColorOutput Cyan "║   GitHub Release Manager - Portfolio  ║"
Write-ColorOutput Cyan "╔════════════════════════════════════════╗"

# Verificar gh CLI
if (-not (Test-GhCli)) {
    exit 1
}

# Ejecutar acción
switch ($Action) {
    'auth-status' {
        Test-GhAuth
    }
    'list' {
        if (Test-GhAuth) {
            Get-Releases
        }
    }
    'create' {
        if (Test-GhAuth) {
            New-Release -IsDraft $false
        }
    }
    'draft' {
        if (Test-GhAuth) {
            New-Release -IsDraft $true
        }
    }
}

Write-Output "`n"
