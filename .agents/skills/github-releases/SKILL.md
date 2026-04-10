---
name: github-releases
description: Manage GitHub releases including creating, listing, updating, deleting releases and managing release assets. Use this skill whenever users mention GitHub releases, version publishing, release notes, changelog generation, uploading release artifacts, downloading release assets, managing pre-releases or beta versions, automating release workflows, semantic versioning, or any task involving GitHub release management. Essential for CI/CD pipelines, version control workflows, and software distribution.
---

# GitHub Releases Management

Este skill te guía en la gestión completa de releases de GitHub utilizando GitHub CLI (`gh`).

## Prerequisitos

Verifica que el usuario tenga GitHub CLI instalado:

```bash
gh --version
```

Si no está instalado, indica al usuario cómo instalarlo:
- **Windows**: `winget install GitHub.cli` o descarga desde https://cli.github.com
- **macOS**: `brew install gh`
- **Linux**: Ver https://github.com/cli/cli/blob/trunk/docs/install_linux.md

Verifica la autenticación:

```bash
gh auth status
```

Si no está autenticado:

```bash
gh auth login
```

## Funcionalidades Principales

### 1. Crear un Release

Cuando el usuario quiera crear un release, considera estos escenarios:

#### Crear release desde un tag existente

```bash
gh release create v1.0.0 --title "Version 1.0.0" --notes "Release notes here"
```

#### Crear release con generación automática de changelog

```bash
gh release create v1.0.0 --generate-notes
```

#### Crear un pre-release (beta, alpha, rc)

```bash
gh release create v1.0.0-beta.1 --prerelease --title "Beta Release" --notes "Beta version for testing"
```

#### Crear release como draft (borrador)

Útil cuando el usuario quiere preparar el release antes de publicarlo:

```bash
gh release create v1.0.0 --draft --title "Version 1.0.0" --notes "WIP release"
```

#### Crear release con assets (archivos adjuntos)

```bash
gh release create v1.0.0 --title "Version 1.0.0" --notes "Release with binaries" dist/*.zip dist/*.tar.gz
```

**Patrones de assets comunes:**
- Binarios compilados: `dist/*.exe`, `bin/*`, `build/*.app`
- Archivos comprimidos: `*.zip`, `*.tar.gz`, `*.7z`
- Instaladores: `*.msi`, `*.deb`, `*.rpm`, `*.dmg`
- Checksums: `checksums.txt`, `SHA256SUMS`

#### Crear release con notas desde archivo

Cuando las release notes son extensas:

```bash
gh release create v1.0.0 --title "Version 1.0.0" --notes-file CHANGELOG.md
```

### 2. Listar Releases

#### Listar todos los releases

```bash
gh release list
```

#### Listar con límite específico

```bash
gh release list --limit 10
```

#### Listar incluyendo drafts y pre-releases

```bash
gh release list --exclude-drafts=false --exclude-pre-releases=false
```

### 3. Ver Detalles de un Release

```bash
gh release view v1.0.0
```

Ver en formato web:

```bash
gh release view v1.0.0 --web
```

Ver el release más reciente:

```bash
gh release view --web
```

### 4. Actualizar un Release Existente

#### Actualizar el título y notas

```bash
gh release edit v1.0.0 --title "New Title" --notes "Updated release notes"
```

#### Marcar como latest release

```bash
gh release edit v1.0.0 --latest
```

#### Convertir draft a release publicado

```bash
gh release edit v1.0.0 --draft=false
```

#### Cambiar de release normal a pre-release

```bash
gh release edit v1.0.0 --prerelease
```

### 5. Gestión de Assets

#### Subir assets a un release existente

```bash
gh release upload v1.0.0 dist/app.exe dist/app.dmg
```

Sobrescribir assets existentes:

```bash
gh release upload v1.0.0 dist/app.exe --clobber
```

#### Listar assets de un release

```bash
gh release view v1.0.0 --json assets --jq '.assets[].name'
```

#### Descargar assets de un release

Descargar todos los assets:

```bash
gh release download v1.0.0
```

Descargar un asset específico:

```bash
gh release download v1.0.0 --pattern "*.exe"
```

Descargar a un directorio específico:

```bash
gh release download v1.0.0 --dir ./downloads
```

#### Eliminar un asset específico

```bash
gh release delete-asset v1.0.0 app.exe --yes
```

### 6. Eliminar un Release

```bash
gh release delete v1.0.0 --yes
```

**Importante**: Eliminar un release NO elimina el tag de git. Para eliminar también el tag:

```bash
gh release delete v1.0.0 --yes --cleanup-tag
```

## Workflows Comunes

### Workflow: Release Completo desde Cero

Guía al usuario a través de este proceso paso a paso:

1. **Asegurar que los cambios estén committeados**:
   ```bash
   git status
   ```

2. **Crear y pushear el tag**:
   ```bash
   git tag -a v1.0.0 -m "Version 1.0.0"
   git push origin v1.0.0
   ```

3. **Construir los artefactos** (adaptar según el proyecto):
   ```bash
   # Ejemplo para un proyecto Node.js
   npm run build
   
   # Ejemplo para Python
   python setup.py sdist bdist_wheel
   
   # Ejemplo para Go
   go build -o dist/app
   ```

4. **Crear el release con assets**:
   ```bash
   gh release create v1.0.0 --generate-notes dist/*
   ```

### Workflow: Generar Changelog Automático

Cuando el usuario quiera un changelog bien formateado:

1. **Generar changelog usando commits convencionales**:
   ```bash
   gh release create v1.0.0 --generate-notes
   ```

2. **Si necesita más control**, ayuda a crear un script que extraiga commits:
   ```bash
   git log v0.9.0..v1.0.0 --pretty=format:"- %s (%h)" --reverse > RELEASE_NOTES.md
   ```

3. **Categorizar por tipo de commit**:
   ```bash
   echo "## Features" > RELEASE_NOTES.md
   git log v0.9.0..v1.0.0 --grep="^feat" --pretty=format:"- %s" >> RELEASE_NOTES.md
   echo "\n## Bug Fixes" >> RELEASE_NOTES.md
   git log v0.9.0..v1.0.0 --grep="^fix" --pretty=format:"- %s" >> RELEASE_NOTES.md
   ```

### Workflow: Automatización en CI/CD

Cuando el usuario quiera automatizar releases desde GitHub Actions, proporciona este template:

```yaml
name: Release
on:
  push:
    tags:
      - 'v*'

jobs:
  release:
    runs-on: ubuntu-latest
    permissions:
      contents: write
    steps:
      - uses: actions/checkout@v4
      
      - name: Build artifacts
        run: |
          # Comandos de build del proyecto
          npm run build
          
      - name: Create Release
        env:
          GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
        run: |
          gh release create ${{ github.ref_name }} \
            --generate-notes \
            dist/*
```

Para otros CI/CD (GitLab CI, CircleCI, etc.), proporciona el equivalente usando `gh` CLI.

### Workflow: Release con Checksums

Cuando se requieran checksums para verificación de integridad:

1. **Generar checksums**:
   ```bash
   # En Windows (PowerShell)
   Get-FileHash -Algorithm SHA256 dist\* | Format-List | Out-File checksums.txt
   
   # En Linux/macOS
   shasum -a 256 dist/* > checksums.txt
   # o
   sha256sum dist/* > checksums.txt
   ```

2. **Incluir en el release**:
   ```bash
   gh release create v1.0.0 --generate-notes dist/* checksums.txt
   ```

## Versionado Semántico

Cuando ayudes al usuario a decidir el número de versión, sigue Semantic Versioning (SemVer):

- **MAJOR (1.0.0)**: Cambios incompatibles con versiones anteriores
- **MINOR (0.1.0)**: Nuevas funcionalidades compatibles hacia atrás
- **PATCH (0.0.1)**: Bug fixes compatibles hacia atrás

Para pre-releases:
- **Alpha**: `1.0.0-alpha.1` (muy temprano, inestable)
- **Beta**: `1.0.0-beta.1` (features completas, puede tener bugs)
- **RC**: `1.0.0-rc.1` (release candidate, casi listo)

## Consultas Avanzadas con jq

GitHub CLI soporta formato JSON para procesamiento avanzado:

### Obtener la URL de descarga del último release

```bash
gh release view --json assets --jq '.assets[0].url'
```

### Listar solo releases estables (no pre-releases)

```bash
gh release list --json tagName,isPrerelease --jq '.[] | select(.isPrerelease == false) | .tagName'
```

### Obtener assets de un release específico

```bash
gh release view v1.0.0 --json assets --jq '.assets[] | "\(.name) - \(.size) bytes"'
```

### Contar releases totales

```bash
gh release list --limit 1000 --json tagName --jq 'length'
```

## Troubleshooting

### Error: "release not found"

Verifica que el tag exista:
```bash
git tag -l
gh release list
```

### Error: "authentication required"

Reautenticar:
```bash
gh auth login
```

Verificar scopes (debe incluir `repo`):
```bash
gh auth status
```

### Error: "asset already exists"

Usar `--clobber` para sobrescribir:
```bash
gh release upload v1.0.0 file.zip --clobber
```

### No puedo eliminar un release

Verificar permisos en el repositorio. Necesitas permisos de escritura.

## Buenas Prácticas

Cuando guíes al usuario, recomienda:

1. **Siempre crear tags anotados**: `git tag -a` en lugar de `git tag`
2. **Usar versionado semántico consistente**
3. **Incluir changelog significativo**: Usa `--generate-notes` o escribe notas detalladas
4. **Assets con nombres descriptivos**: Incluir versión y plataforma en el nombre
5. **Checksums para verificación**: Especialmente importante para binarios
6. **Drafts para preparación**: Usa `--draft` para revisar antes de publicar
7. **Pre-releases para testing**: Marca versiones beta/rc como `--prerelease`
8. **No eliminar releases en producción**: Solo si es absolutamente necesario

## Ejemplos Completos por Tipo de Proyecto

### Proyecto Node.js

```bash
# 1. Actualizar version en package.json
npm version 1.0.0

# 2. Build
npm run build

# 3. Crear release
gh release create v1.0.0 --generate-notes dist/
```

### Proyecto Python

```bash
# 1. Build
python -m build

# 2. Crear release con wheels
gh release create v1.0.0 --generate-notes dist/*.whl dist/*.tar.gz
```

### Proyecto Go

```bash
# 1. Build para múltiples plataformas
GOOS=windows GOARCH=amd64 go build -o dist/app-windows.exe
GOOS=linux GOARCH=amd64 go build -o dist/app-linux
GOOS=darwin GOARCH=amd64 go build -o dist/app-macos

# 2. Crear release
gh release create v1.0.0 --generate-notes dist/app-*
```

### Proyecto Rust

```bash
# 1. Build release
cargo build --release

# 2. Copiar binario
cp target/release/app dist/

# 3. Crear release
gh release create v1.0.0 --generate-notes dist/app
```

## Notas Importantes

- **GitHub CLI automáticamente detecta el repositorio** del directorio actual. Para trabajar en otro repo, usa `-R owner/repo`
- **Los permisos son críticos**: Asegúrate de que el usuario tenga permisos de escritura
- **Tags y releases son conceptos separados**: Un tag de Git puede existir sin release, pero un release siempre necesita un tag
- **Draft releases son útiles**: Permiten preparar todo antes de hacerlo público
- **La flag `--generate-notes` es poderosa**: Usa PRs y commits para crear changelog automático

## Adaptación al Contexto del Usuario

Cuando uses este skill:

1. **Identifica el tipo de proyecto** (Node.js, Python, Go, etc.) para dar comandos de build apropiados
2. **Verifica el sistema operativo** del usuario para comandos específicos de plataforma
3. **Considera el workflow existente** del usuario (¿usan CI/CD? ¿releases manuales?)
4. **Ajusta la complejidad** según la experiencia del usuario con git y GitHub
5. **Pregunta sobre requisitos específicos** antes de proponer una solución completa
