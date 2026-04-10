# GitHub Workflows Skill

Skill comprehensivo para gestión de Git y GitHub, cubriendo desde operaciones básicas hasta workflows avanzados de colaboración y mantenimiento.

## Descripción

Este skill proporciona guía completa para trabajar con Git y GitHub, incluyendo comandos básicos, resolución de conflictos, mantenimiento de repositorios, y flujos de trabajo de colaboración.

## Funcionalidades Cubiertas

### Operaciones Básicas
- ✅ Clonar, pull, fetch, push, sync
- ✅ Gestión completa de branches (crear, cambiar, fusionar, eliminar)
- ✅ Commits, staging, unstaging
- ✅ Tags y versionado
- ✅ Configuración de Git

### Resolución de Conflictos
- 🔧 Identificación y resolución de conflictos de merge
- 🔧 Estrategias de resolución (manual, herramientas visuales, checkout)
- 🔧 Cherry-pick y rebase
- 🔧 Prevención de conflictos
- 🔧 Herramientas para conflictos complejos (blame, log, diff)

### Issues y Pull Requests
- 📋 Crear, listar, ver, actualizar issues
- 🔄 Crear y gestionar pull requests
- 👀 Revisar PRs (approve, request changes, comment)
- ✔️ Fusionar PRs (merge, squash, rebase)
- 🔍 Checkout de PRs para revisión local

### Workflow de Contribución Open Source
- 🍴 Fork de repositorios
- 🌿 Crear feature branches
- 📤 Push y crear PRs al upstream
- 🔄 Sincronizar fork con upstream
- 🤝 Colaboración con mantenedores

### Mantenimiento de Repositorio
- 🧹 Limpiar archivos no rastreados
- 🗑️ Eliminar ramas antiguas y huérfanas
- ⚡ Optimizar repositorio (gc, fsck)
- 📦 Limpiar historial de archivos grandes
- 🔧 Gestionar .gitignore
- 🌐 Gestionar remotes
- 💾 Stash (guardar trabajo temporal)
- ↩️ Revertir cambios

### GitHub Repository Management
- 📁 Crear y configurar repositorios
- 👥 Gestionar colaboradores
- 🛡️ Branch protection rules
- ⚙️ Configuración de repositorio

### Workflows Avanzados
- 🔍 Git bisect (encontrar bugs)
- 🌳 Git worktree (múltiples branches simultáneos)
- 📦 Submodules
- 📜 Reflog (recuperar commits perdidos)
- 🔐 Git LFS para archivos grandes

## Cuándo se Activa

El skill se activa automáticamente cuando el usuario menciona:
- Operaciones de git (push, pull, fetch, merge, rebase)
- Sincronización con GitHub
- Problemas de merge o conflictos
- Limpieza o mantenimiento de repositorio
- Pull requests o issues de GitHub
- Fork de proyectos
- Contribuir a open source
- Problemas con branches
- Gestión de remotes
- Cualquier workflow de git/GitHub

## Requisitos

- **Git** instalado y configurado (nombre, email)
- **GitHub CLI (gh)** instalado y autenticado
- Permisos adecuados en los repositorios (lectura/escritura según operación)

## Estructura del Skill

```
github-workflows/
├── SKILL.md       # Instrucciones completas del skill (900+ líneas)
└── README.md      # Este archivo
```

## Secciones Principales del SKILL.md

1. **Operaciones Básicas de Sincronización** - Clone, pull, fetch, push, sync de fork
2. **Gestión de Branches** - Crear, listar, renombrar, eliminar, merge, rebase
3. **Resolución de Conflictos** - Estrategias completas con ejemplos prácticos
4. **Issues y Pull Requests** - Gestión completa usando GitHub CLI
5. **Workflow de Contribución** - Flujo completo fork → feature → PR
6. **Mantenimiento de Repositorio** - Limpieza, optimización, gestión
7. **Configuración de Repositorio** - Crear repos, colaboradores, protección
8. **Workflows Avanzados** - Bisect, worktree, submodules, reflog
9. **Troubleshooting Común** - Soluciones a errores frecuentes
10. **Mejores Prácticas** - Commits, branches, colaboración, seguridad

## Características Especiales

### Énfasis en Resolución de Conflictos
- Múltiples estrategias explicadas (manual, visual, checkout)
- Comandos para conflictos complejos
- Prevención de conflictos
- Cherry-pick y rebase con conflictos

### Énfasis en Mantenimiento
- Limpieza de branches antiguas
- Optimización de repositorio
- Gestión de archivos grandes
- Herramientas de análisis (gc, fsck, filter-repo)

### Comandos Híbridos Git + GitHub CLI
- Combina git tradicional con gh CLI
- Aprovecha las ventajas de ambas herramientas
- Shortcuts y métodos más eficientes

### Adaptativo al Usuario
- Detecta nivel de experiencia
- Ajusta al sistema operativo (PowerShell vs Bash)
- Considera el flujo de trabajo del equipo
- Proporciona alternativas según contexto

### Troubleshooting Completo
- Errores comunes con soluciones
- SSH y autenticación
- Archivos grandes y LFS
- Deshacer cambios y recuperación

### Referencias Rápidas
- Comandos del flujo diario
- Templates para workflows comunes
- Mejores prácticas resumidas

## Ejemplos de Uso

### Flujo básico diario
```bash
git pull --rebase
# trabajar...
git add .
git commit -m "feat: nueva funcionalidad"
git push
```

### Resolver conflictos
```bash
git pull
# conflicto detectado
git status
# editar archivos en conflicto
git add archivo-resuelto.txt
git merge --continue
git push
```

### Contribuir a proyecto open source
```bash
gh repo fork usuario/proyecto --clone
cd proyecto
git checkout -b fix-typo
# hacer cambios
git add .
git commit -m "fix: corregir typo en README"
git push -u origin fix-typo
gh pr create
```

### Limpiar repositorio
```bash
git fetch --prune
git branch --merged main | grep -v "main" | xargs git branch -d
git clean -fd
git gc --aggressive
```

### Crear release workflow
```bash
git checkout main
git pull
git tag -a v1.0.0 -m "Version 1.0.0"
git push origin v1.0.0
gh release create v1.0.0 --generate-notes
```

## Integración con Otros Skills

Este skill complementa perfectamente:
- **github-releases**: Para gestionar releases después de crear tags
- Skills de CI/CD: Para automatización de workflows
- Skills de testing: Para pre-commit hooks y validaciones

## Convenciones Seguidas

✅ **Estructura clara**: Secciones bien organizadas y numeradas  
✅ **Ejemplos prácticos**: Cada comando con contexto de uso  
✅ **Explicaciones del "por qué"**: No solo comandos, sino razones  
✅ **Warnings apropiados**: Alertas sobre comandos destructivos  
✅ **Alternativas**: Múltiples formas de lograr el mismo objetivo  
✅ **Troubleshooting**: Soluciones a problemas comunes  
✅ **Mejores prácticas**: Guía sobre cómo trabajar profesionalmente  
✅ **Adaptabilidad**: Instrucciones para ajustar al contexto del usuario  
✅ **Progresivo**: De básico a avanzado

## Diferenciadores

Este skill se diferencia de documentación estándar porque:

1. **Combina Git + GitHub**: No solo git, sino también GitHub CLI
2. **Contexto de uso**: Cada comando con escenario de cuándo usarlo
3. **Resolución de problemas**: No solo qué hacer, sino qué hacer cuando falla
4. **Workflows completos**: No solo comandos aislados, sino flujos de trabajo
5. **Específico para conflictos**: Múltiples estrategias de resolución
6. **Mantenimiento enfocado**: Herramientas para mantener repos saludables
7. **Colaboración real**: Flujos de trabajo de contribución realistas
8. **Best practices**: Guía sobre cómo trabajar profesionalmente

## Mantenimiento

Para actualizar este skill:
1. Editar `SKILL.md` con los cambios
2. Probar comandos en diferentes contextos
3. Actualizar este README si se añaden secciones mayores
4. Verificar compatibilidad con versiones recientes de Git y GitHub CLI

## Recursos Complementarios

- Git Book oficial (español): https://git-scm.com/book/es
- GitHub Docs: https://docs.github.com
- GitHub CLI Manual: https://cli.github.com/manual
- Learn Git Branching: https://learngitbranching.js.org

## Versión

- **Creado**: 10 de abril de 2026
- **Última actualización**: 10 de abril de 2026
- **Convenciones**: Sigue las convenciones de Anthropic skill-creator
- **Líneas de código**: 900+ líneas de instrucciones detalladas
