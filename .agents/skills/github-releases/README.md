# GitHub Releases Skill

Skill para gestión completa de releases de GitHub usando GitHub CLI (`gh`).

## Descripción

Este skill proporciona guía completa para trabajar con releases de GitHub, cubriendo todas las operaciones principales y workflows comunes.

## Funcionalidades Cubiertas

### Operaciones Principales
- ✅ Crear releases (normales, drafts, pre-releases)
- ✅ Listar releases
- ✅ Ver detalles de releases
- ✅ Actualizar releases existentes
- ✅ Eliminar releases
- ✅ Gestión completa de assets (subir, descargar, listar, eliminar)

### Workflows Especializados
- 🚀 Release completo desde cero (tag → build → publish)
- 📝 Generación automática de changelog
- 🤖 Automatización en CI/CD (GitHub Actions y otros)
- 🔐 Creación de checksums para verificación
- 📦 Ejemplos específicos por tipo de proyecto (Node.js, Python, Go, Rust)

### Conceptos Cubiertos
- Versionado Semántico (SemVer)
- Pre-releases (alpha, beta, rc)
- Draft releases
- Gestión de tags de Git
- Buenas prácticas de release

## Cuándo se Activa

El skill se activa automáticamente cuando el usuario menciona:
- GitHub releases o release management
- Publicación de versiones
- Release notes o changelog
- Subir/descargar assets de releases
- Pre-releases o versiones beta
- Automatización de releases
- Semantic versioning en contexto de releases
- CI/CD con releases de GitHub

## Requisitos

- GitHub CLI (`gh`) instalado y autenticado
- Permisos de escritura en el repositorio (para crear/modificar releases)
- Git configurado (para operaciones con tags)

## Estructura del Skill

```
github-releases/
├── SKILL.md       # Instrucciones completas del skill
└── README.md      # Este archivo
```

## Ejemplos de Uso

### Crear un release simple
```bash
gh release create v1.0.0 --generate-notes
```

### Crear release con assets
```bash
gh release create v1.0.0 --generate-notes dist/*.zip dist/*.tar.gz
```

### Crear pre-release
```bash
gh release create v1.0.0-beta.1 --prerelease --title "Beta 1" --notes "Testing version"
```

### Listar todos los releases
```bash
gh release list
```

### Descargar assets del último release
```bash
gh release download
```

## Características Especiales

- **Detección automática de tipo de proyecto**: Proporciona comandos de build específicos según el lenguaje
- **Adaptación al SO**: Comandos específicos para Windows/Linux/macOS
- **Soporte completo de jq**: Consultas avanzadas con formato JSON
- **Templates de CI/CD**: Ejemplos listos para GitHub Actions
- **Troubleshooting incluido**: Soluciones a errores comunes

## Mantenimiento

Para actualizar este skill:
1. Editar `SKILL.md` con los cambios
2. Probar con casos de uso reales
3. Actualizar este README si se añaden funcionalidades mayores

## Versión

- **Creado**: 10 de abril de 2026
- **Última actualización**: 10 de abril de 2026
- **Convenciones**: Sigue las convenciones de Anthropic skill-creator
