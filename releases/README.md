# Release Notes

Esta carpeta contiene las notas de release para cada versión del Portfolio.

## Estructura

Cada archivo de release notes sigue el patrón:
```
RELEASE_NOTES_v{version}.md
```

Ejemplo: `RELEASE_NOTES_v1.5.0.md`

## Uso

### Crear un release manualmente con gh CLI

```bash
# Crear release como borrador
gh release create v1.5.0 --draft --title "Portfolio v1.5.0 - Título" --notes-file releases/RELEASE_NOTES_v1.5.0.md

# Crear release publicado directamente
gh release create v1.5.0 --title "Portfolio v1.5.0 - Título" --notes-file releases/RELEASE_NOTES_v1.5.0.md
```

### Usar el script automatizado

```powershell
# Crear como borrador
.\scripts\manage-release.ps1 -Action draft -Version "v1.5.0" -Title "Portfolio v1.5.0 - Título"

# Publicar directamente
.\scripts\manage-release.ps1 -Action create -Version "v1.5.0" -Title "Portfolio v1.5.0 - Título"
```

El script buscará automáticamente el archivo en `releases/RELEASE_NOTES_v{version}.md`

## Formato Recomendado

```markdown
# Portfolio v{version} - Título Principal

## 🎯 Nuevas Funcionalidades Principales

### Funcionalidad 1
Descripción...

## 🛠️ Mejoras Técnicas

### Backend
- Mejora 1
- Mejora 2

### Frontend
- Mejora 1

## 📚 Documentación

Nueva documentación...

## 🔧 Scripts y Utilidades

Scripts añadidos...

## 📦 Archivos Principales Añadidos

Lista de archivos nuevos...

## ⚙️ Configuración Requerida

Configuraciones necesarias...

## 🚀 Próximos Pasos

Pasos a seguir después del release...
```

## Releases Existentes

- **v1.5.0**: Sistema de Notificaciones y Inspector GSC
- **v1.4.0**: (Published)
- **v1.3.0**: (Deprecated)
