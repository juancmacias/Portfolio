---
name: github-workflows
description: Comprehensive Git and GitHub workflow management including push, pull, sync, branch operations, conflict resolution, repository maintenance, and collaboration workflows. Use this skill whenever users mention git operations, GitHub synchronization, merging branches, resolving conflicts, cleaning repositories, managing remotes, handling pull requests, forking projects, contributing to open source, or any git/GitHub workflow task. Essential for version control, team collaboration, and repository management.
---

# Git & GitHub Workflows Management

Este skill te guía en operaciones completas de Git y GitHub, desde comandos básicos hasta workflows avanzados de colaboración y mantenimiento.

## Prerequisitos

Verifica las herramientas instaladas:

```bash
# Git
git --version

# GitHub CLI
gh --version
```

**Instalación si es necesario:**
- **Git**: https://git-scm.com/downloads
- **GitHub CLI**: 
  - Windows: `winget install GitHub.cli`
  - macOS: `brew install gh`
  - Linux: https://github.com/cli/cli/blob/trunk/docs/install_linux.md

Verifica la autenticación de GitHub CLI:

```bash
gh auth status
```

Si no está autenticado:

```bash
gh auth login
```

## Configuración Inicial de Git

Antes de comenzar, asegúrate de que el usuario tenga configurado git:

```bash
# Verificar configuración actual
git config --list

# Configurar nombre y email (si no está configurado)
git config --global user.name "Tu Nombre"
git config --global user.email "tu@email.com"

# Configurar editor por defecto (opcional)
git config --global core.editor "code --wait"  # VS Code
# o
git config --global core.editor "nano"  # nano
```

## 1. Operaciones Básicas de Sincronización

### Clonar un Repositorio

```bash
# Clonar con HTTPS
git clone https://github.com/usuario/repositorio.git

# Clonar con SSH
git clone git@github.com:usuario/repositorio.git

# Clonar usando gh CLI
gh repo clone usuario/repositorio
```

Clonar a un directorio específico:

```bash
git clone https://github.com/usuario/repositorio.git nombre-directorio
```

### Actualizar desde Remoto (Pull)

```bash
# Pull con merge (por defecto)
git pull

# Pull con rebase (más limpio)
git pull --rebase

# Pull de una rama específica
git pull origin main

# Pull con estrategia específica
git pull --strategy=ours
```

**Buena práctica**: Siempre hacer pull antes de push para evitar conflictos.

### Fetch (Descargar sin Fusionar)

```bash
# Fetch de todos los remotes
git fetch --all

# Fetch de un remote específico
git fetch origin

# Fetch y eliminar referencias a ramas remotas eliminadas
git fetch --prune
```

### Subir Cambios (Push)

```bash
# Push a la rama actual
git push

# Push y establecer upstream (primera vez)
git push -u origin nombre-rama

# Push forzado (usar con precaución)
git push --force

# Push forzado seguro (no sobrescribe si hay cambios remotos)
git push --force-with-lease

# Push todas las ramas
git push --all

# Push tags
git push --tags
```

### Sincronizar Fork con Upstream

Cuando trabajas en un fork:

```bash
# 1. Agregar el repositorio original como upstream (solo una vez)
git remote add upstream https://github.com/original/repo.git

# 2. Verificar remotes
git remote -v

# 3. Fetch cambios del upstream
git fetch upstream

# 4. Fusionar cambios del upstream a tu main
git checkout main
git merge upstream/main

# 5. Actualizar tu fork en GitHub
git push origin main
```

Usando GitHub CLI (más simple):

```bash
# Sincronizar fork automáticamente
gh repo sync usuario/fork -b main
```

## 2. Gestión de Branches (Ramas)

### Crear y Cambiar de Rama

```bash
# Crear rama
git branch nombre-rama

# Cambiar a rama existente
git checkout nombre-rama
# o (más moderno)
git switch nombre-rama

# Crear y cambiar en un comando
git checkout -b nombre-rama
# o
git switch -c nombre-rama

# Crear rama desde un commit específico
git checkout -b nombre-rama abc123

# Crear rama usando gh
gh repo create-branch nombre-rama
```

### Listar Ramas

```bash
# Listar ramas locales
git branch

# Listar ramas remotas
git branch -r

# Listar todas las ramas (locales y remotas)
git branch -a

# Listar con información adicional
git branch -v

# Listar ramas fusionadas con main
git branch --merged main

# Listar ramas NO fusionadas
git branch --no-merged
```

### Renombrar Rama

```bash
# Renombrar rama actual
git branch -m nuevo-nombre

# Renombrar otra rama
git branch -m viejo-nombre nuevo-nombre

# Actualizar en remoto
git push origin -u nuevo-nombre
git push origin --delete viejo-nombre
```

### Eliminar Ramas

```bash
# Eliminar rama local (solo si está fusionada)
git branch -d nombre-rama

# Eliminar rama local forzadamente
git branch -D nombre-rama

# Eliminar rama remota
git push origin --delete nombre-rama
# o
git push origin :nombre-rama

# Limpiar referencias a ramas remotas eliminadas
git fetch --prune
```

### Fusionar Ramas (Merge)

```bash
# Fusionar una rama a la actual
git merge nombre-rama

# Merge con mensaje personalizado
git merge nombre-rama -m "Mensaje del merge"

# Merge sin fast-forward (crea commit de merge)
git merge --no-ff nombre-rama

# Merge con estrategia específica
git merge -X theirs nombre-rama  # Preferir cambios de ellos
git merge -X ours nombre-rama    # Preferir nuestros cambios

# Abortar merge en progreso
git merge --abort
```

### Rebase (Reorganizar Historial)

Rebase es útil para mantener un historial lineal:

```bash
# Rebase sobre main
git checkout feature-branch
git rebase main

# Rebase interactivo (para editar commits)
git rebase -i HEAD~3  # últimos 3 commits

# Continuar después de resolver conflictos
git rebase --continue

# Saltar commit problemático
git rebase --skip

# Abortar rebase
git rebase --abort
```

**⚠️ Advertencia**: No hagas rebase de commits ya pusheados a remoto compartido.

## 3. Resolución de Conflictos

### Identificar Conflictos

Cuando hay conflictos, Git mostrará:

```bash
# Ver archivos en conflicto
git status

# Ver diferencias
git diff
```

Los archivos con conflictos tendrán marcadores:

```
<<<<<<< HEAD
// Tu versión
=======
// Versión de ellos
>>>>>>> branch-name
```

### Estrategias de Resolución

#### Método 1: Edición Manual

1. Abre el archivo en conflicto
2. Busca los marcadores `<<<<<<<`, `=======`, `>>>>>>>`
3. Decide qué código mantener
4. Elimina los marcadores
5. Guarda el archivo

```bash
# Marcar como resuelto
git add archivo-resuelto

# Continuar con merge/rebase
git merge --continue
# o
git rebase --continue
```

#### Método 2: Usar Herramienta de Merge

```bash
# Abrir herramienta de merge visual
git mergetool

# Configurar VS Code como mergetool
git config --global merge.tool vscode
git config --global mergetool.vscode.cmd 'code --wait $MERGED'
```

#### Método 3: Aceptar Todo de Una Versión

```bash
# Aceptar nuestra versión (HEAD)
git checkout --ours archivo.txt

# Aceptar su versión (incoming)
git checkout --theirs archivo.txt

# Marcar como resuelto
git add archivo.txt
```

#### Método 4: Cherry-pick Específico

Cuando solo quieres ciertos cambios:

```bash
# Cherry-pick un commit específico
git cherry-pick abc123

# Cherry-pick sin hacer commit automático
git cherry-pick -n abc123

# Continuar después de resolver conflictos
git cherry-pick --continue

# Abortar cherry-pick
git cherry-pick --abort
```

### Prevenir Conflictos

Mejores prácticas para evitar conflictos:

1. **Pull frecuentemente**: `git pull --rebase` antes de empezar a trabajar
2. **Commits pequeños**: Commit cambios relacionados juntos
3. **Comunicación**: Avisa al equipo cuando trabajas en archivos importantes
4. **Feature branches**: Trabaja en ramas separadas
5. **Merge frecuente**: No dejes ramas sin fusionar por mucho tiempo

### Resolver Conflictos Complejos

Para conflictos difíciles:

```bash
# Ver el historial de un archivo
git log --follow -p -- archivo.txt

# Ver quién modificó qué línea
git blame archivo.txt

# Comparar con versión anterior
git diff HEAD~1 archivo.txt

# Ver cambios entre ramas
git diff main..feature-branch archivo.txt
```

## 4. Issues y Pull Requests (usando GitHub CLI)

### Gestión de Issues

#### Listar Issues

```bash
# Listar issues abiertos
gh issue list

# Listar con filtros
gh issue list --state all
gh issue list --label bug
gh issue list --assignee @me
gh issue list --author usuario

# Buscar issues
gh issue list --search "error en login"
```

#### Crear Issue

```bash
# Crear interactivamente
gh issue create

# Crear con parámetros
gh issue create --title "Bug en login" --body "Descripción del problema"

# Crear con labels y asignados
gh issue create --title "Feature request" --label enhancement --assignee usuario
```

#### Ver y Actualizar Issue

```bash
# Ver detalles
gh issue view 123

# Ver en el navegador
gh issue view 123 --web

# Comentar
gh issue comment 123 --body "Comentario aquí"

# Cerrar issue
gh issue close 123

# Reabrir issue
gh issue reopen 123
```

### Gestión de Pull Requests

#### Crear Pull Request

```bash
# Crear PR interactivo
gh pr create

# Crear con parámetros
gh pr create --title "Fix login bug" --body "Descripción de cambios"

# Crear PR en draft
gh pr create --draft

# Crear PR con reviewers
gh pr create --reviewer usuario1,usuario2

# Crear desde rama específica a otra
gh pr create --base main --head feature-branch
```

#### Listar y Ver PRs

```bash
# Listar PRs abiertos
gh pr list

# Listar todos
gh pr list --state all

# Ver detalles
gh pr view 456

# Ver en navegador
gh pr view 456 --web

# Ver diff
gh pr diff 456

# Ver checks/CI status
gh pr checks 456
```

#### Checkout de PR para Revisión

```bash
# Checkout PR localmente
gh pr checkout 456

# Ver cambios localmente
git log main..HEAD
git diff main
```

#### Revisar y Comentar PR

```bash
# Comentar
gh pr comment 456 --body "Se ve bien!"

# Aprobar
gh pr review 456 --approve

# Solicitar cambios
gh pr review 456 --request-changes --body "Cambios necesarios"

# Comentar sin aprobar/rechazar
gh pr review 456 --comment --body "Pregunta sobre línea X"
```

#### Fusionar PR

```bash
# Merge normal
gh pr merge 456

# Merge con squash
gh pr merge 456 --squash

# Merge con rebase
gh pr merge 456 --rebase

# Merge y eliminar rama
gh pr merge 456 --delete-branch

# Merge automático cuando checks pasen
gh pr merge 456 --auto --squash
```

#### Cerrar PR sin Merge

```bash
gh pr close 456
```

## 5. Workflow de Contribución a Proyecto Open Source

### Flujo Completo Fork → PR

Cuando el usuario quiera contribuir a un proyecto externo:

**Paso 1: Fork del Repositorio**

```bash
# Fork usando gh
gh repo fork usuario/proyecto

# O fork y clonar en un comando
gh repo fork usuario/proyecto --clone
```

**Paso 2: Crear Rama para tu Feature**

```bash
cd proyecto
git checkout -b fix-typo-readme
```

**Paso 3: Hacer Cambios y Commit**

```bash
# Hacer cambios en archivos
# ...

# Ver estado
git status

# Agregar cambios
git add README.md

# Commit
git commit -m "Fix typo in installation section"
```

**Paso 4: Push a tu Fork**

```bash
git push -u origin fix-typo-readme
```

**Paso 5: Crear Pull Request**

```bash
# Crear PR al repositorio original
gh pr create --repo usuario/proyecto --title "Fix typo in README" --body "Fixed spelling error in installation section"
```

**Paso 6: Mantener Sincronizado con Upstream**

Mientras esperas review del PR:

```bash
# Sincronizar con upstream
gh repo sync

# O manualmente:
git fetch upstream
git checkout main
git merge upstream/main
git push origin main

# Actualizar tu rama de feature
git checkout fix-typo-readme
git rebase main
git push --force-with-lease
```

## 6. Mantenimiento de Repositorio

### Limpiar Archivos No Rastreados

```bash
# Ver qué se eliminaría (dry-run)
git clean -n

# Eliminar archivos no rastreados
git clean -f

# Eliminar directorios también
git clean -fd

# Incluir archivos ignorados
git clean -fdx
```

### Limpiar Ramas Antiguas

```bash
# Listar ramas ya fusionadas
git branch --merged main

# Eliminar todas las ramas fusionadas (excepto main/master)
git branch --merged main | grep -v "main" | xargs git branch -d

# En Windows PowerShell
git branch --merged main | Where-Object { $_ -notmatch 'main' } | ForEach-Object { git branch -d $_.Trim() }

# Eliminar ramas remotas huérfanas localmente
git fetch --prune
```

### Optimizar Repositorio

```bash
# Ejecutar garbage collection
git gc

# GC agresivo (más lento pero más efectivo)
git gc --aggressive

# Verificar integridad del repositorio
git fsck

# Ver tamaño del repositorio
du -sh .git
# En Windows
Get-ChildItem .git -Recurse | Measure-Object -Property Length -Sum
```

### Limpiar Historial (Archivos Grandes)

Si accidentalmente commiteaste archivos grandes:

```bash
# Encontrar archivos grandes en historial
git rev-list --objects --all | \
  git cat-file --batch-check='%(objecttype) %(objectname) %(objectsize) %(rest)' | \
  grep '^blob' | \
  sort -k3 -n -r | \
  head -20

# Usar git filter-repo (herramienta recomendada)
# Instalar: pip install git-filter-repo

# Eliminar archivo del historial
git filter-repo --path archivo-grande.zip --invert-paths

# O eliminar todos los archivos mayores a X MB
git filter-repo --strip-blobs-bigger-than 10M
```

**⚠️ Importante**: Esto reescribe la historia. Todos los colaboradores necesitarán re-clonar.

### Mantener .gitignore Actualizado

```bash
# Ver archivos ignorados
git status --ignored

# Agregar pattern al .gitignore
echo "node_modules/" >> .gitignore
echo "*.log" >> .gitignore

# Dejar de rastrear archivo ya commiteado sin eliminarlo
git rm --cached archivo.txt

# Commit el .gitignore actualizado
git add .gitignore
git commit -m "Update .gitignore"
```

Templates útiles de .gitignore: https://github.com/github/gitignore

### Gestionar Remotes

```bash
# Listar remotes
git remote -v

# Agregar remote
git remote add nombre-remote https://github.com/usuario/repo.git

# Cambiar URL de remote
git remote set-url origin https://github.com/usuario/nuevo-repo.git

# Renombrar remote
git remote rename origin upstream

# Eliminar remote
git remote remove nombre-remote
```

### Stash (Guardar Cambios Temporalmente)

```bash
# Guardar cambios sin commit
git stash

# Guardar con mensaje
git stash save "trabajo en progreso en feature X"

# Listar stashes
git stash list

# Aplicar último stash
git stash apply

# Aplicar stash específico
git stash apply stash@{2}

# Aplicar y eliminar del stash
git stash pop

# Eliminar stash
git stash drop stash@{0}

# Crear rama desde stash
git stash branch nombre-rama
```

### Revertir Cambios

```bash
# Descartar cambios en archivo no staged
git checkout -- archivo.txt
# o (más moderno)
git restore archivo.txt

# Descartar todos los cambios no staged
git restore .

# Unstage archivo
git reset HEAD archivo.txt
# o
git restore --staged archivo.txt

# Revertir último commit (mantiene cambios)
git reset --soft HEAD~1

# Revertir último commit (descarta cambios)
git reset --hard HEAD~1

# Revertir a commit específico
git reset --hard abc123

# Crear commit que revierte otro commit
git revert abc123
```

### Tags (Etiquetas)

```bash
# Listar tags
git tag

# Crear tag ligero
git tag v1.0.0

# Crear tag anotado (recomendado)
git tag -a v1.0.0 -m "Version 1.0.0"

# Crear tag en commit específico
git tag -a v1.0.0 abc123 -m "Version 1.0.0"

# Push tags
git push origin v1.0.0
git push --tags  # todos los tags

# Eliminar tag local
git tag -d v1.0.0

# Eliminar tag remoto
git push origin --delete v1.0.0
```

## 7. Configuración de Repositorio (GitHub)

### Crear Repositorio

```bash
# Crear repo público
gh repo create nombre-repo --public

# Crear repo privado
gh repo create nombre-repo --private

# Crear con descripción y README
gh repo create nombre-repo --public --description "Descripción" --add-readme

# Crear desde directorio actual
gh repo create --source=. --push
```

### Ver Información del Repositorio

```bash
# Ver info general
gh repo view

# Ver en navegador
gh repo view --web

# Ver de otro repo
gh repo view usuario/repo
```

### Configuración de Colaboradores

```bash
# Listar colaboradores
gh api repos/:owner/:repo/collaborators

# Agregar colaborador (requiere permisos de admin)
gh api repos/:owner/:repo/collaborators/username -X PUT
```

### Gestión de Branch Protection

```bash
# Ver reglas de protección
gh api repos/:owner/:repo/branches/main/protection

# Configurar protección básica en main
gh api repos/:owner/:repo/branches/main/protection -X PUT -f required_status_checks=null -f enforce_admins=true -f required_pull_request_reviews='{"required_approving_review_count":1}' -f restrictions=null
```

## 8. Workflows Avanzados

### Git Bisect (Encontrar Commit que Introdujo Bug)

```bash
# Iniciar bisect
git bisect start

# Marcar commit actual como malo
git bisect bad

# Marcar commit antiguo conocido como bueno
git bisect good abc123

# Git checkout a commit intermedio - probar
# Marcar como bueno o malo
git bisect good  # si funciona
git bisect bad   # si está roto

# Repetir hasta encontrar el commit culpable

# Terminar bisect
git bisect reset
```

### Git Worktree (Múltiples Branches Simultáneamente)

```bash
# Crear worktree en otro directorio
git worktree add ../proyecto-feature feature-branch

# Listar worktrees
git worktree list

# Eliminar worktree
git worktree remove ../proyecto-feature
```

### Submodules (Repositorios Anidados)

```bash
# Agregar submodule
git submodule add https://github.com/usuario/libreria.git libs/libreria

# Clonar repo con submodules
git clone --recurse-submodules url-del-repo

# Actualizar submodules
git submodule update --remote

# Inicializar submodules después de clonar
git submodule init
git submodule update
```

### Reflog (Historial de Referencias)

Útil para recuperar commits "perdidos":

```bash
# Ver reflog
git reflog

# Recuperar commit después de reset --hard
git reset --hard abc123  # del reflog
```

## 9. Troubleshooting Común

### Error: "Your branch is ahead of origin/main"

```bash
# Push tus cambios
git push

# O si quieres descartar commits locales
git reset --hard origin/main
```

### Error: "Your branch is behind origin/main"

```bash
# Pull cambios
git pull

# O con rebase para historial más limpio
git pull --rebase
```

### Error: "Your branch and origin/main have diverged"

```bash
# Opción 1: Pull con merge
git pull

# Opción 2: Pull con rebase
git pull --rebase

# Opción 3: Reset a remoto (descarta cambios locales)
git reset --hard origin/main
```

### Error: "Permission denied (publickey)"

Configurar SSH:

```bash
# Generar clave SSH
ssh-keygen -t ed25519 -C "tu@email.com"

# Agregar a ssh-agent
eval "$(ssh-agent -s)"
ssh-add ~/.ssh/id_ed25519

# Agregar clave pública a GitHub
cat ~/.ssh/id_ed25519.pub
# Copiar y pegar en GitHub Settings → SSH Keys
```

### Error: "Large files detected"

```bash
# Usar Git LFS para archivos grandes
git lfs install
git lfs track "*.psd"
git lfs track "*.mp4"

git add .gitattributes
git add archivo-grande.psd
git commit -m "Add large file with LFS"
git push
```

### Deshacer Push Accidental

```bash
# Si nadie ha pulled aún
git reset --hard HEAD~1
git push --force-with-lease

# Si otros ya han pulled, revert es más seguro
git revert HEAD
git push
```

### Cambiar Commit Message del Último Commit

```bash
# Antes de push
git commit --amend -m "Nuevo mensaje"

# Después de push (evitar si posible)
git commit --amend -m "Nuevo mensaje"
git push --force-with-lease
```

### Ver Cambios No Guardados

```bash
# Ver cambios en working directory
git diff

# Ver cambios staged
git diff --staged

# Ver cambios de archivo específico
git diff archivo.txt
```

## 10. Mejores Prácticas

Cuando guíes al usuario, enfatiza:

### Commits

- ✅ **Commits atómicos**: Un commit = un cambio lógico
- ✅ **Mensajes descriptivos**: Qué y por qué, no solo qué
- ✅ **Formato de mensajes**:
  ```
  tipo: descripción breve (max 50 chars)
  
  Explicación detallada si es necesario (wrap a 72 chars)
  ```
  Tipos comunes: feat, fix, docs, style, refactor, test, chore

### Branches

- ✅ **Nombres descriptivos**: `feature/login-page`, `fix/navbar-alignment`, `hotfix/critical-bug`
- ✅ **Ramas cortas**: Fusionar frecuentemente
- ✅ **Limpiar después**: Eliminar ramas fusionadas

### Colaboración

- ✅ **Pull antes de push**: Evita conflictos
- ✅ **Review de código**: Usa PRs incluso en proyectos pequeños
- ✅ **Comunicación**: Comenta tus PRs y commits
- ✅ **No reescribir historia pública**: Evita `rebase`/`force push` en ramas compartidas

### Seguridad

- 🚫 **No commitear secretos**: Claves API, passwords, tokens
- ✅ **Usar .gitignore**: Evitar archivos sensibles
- ✅ **Git-secrets**: Herramienta para prevenir commits de secretos
  ```bash
  # Instalar git-secrets
  brew install git-secrets  # macOS
  
  # Configurar
  git secrets --install
  git secrets --register-aws
  ```

### Performance

- ✅ **Shallow clone** para repos grandes:
  ```bash
  git clone --depth 1 url-del-repo
  ```
- ✅ **Partial clone** para monorepos:
  ```bash
  git clone --filter=blob:none url-del-repo
  ```

## Comandos Rápidos de Referencia

### Flujo Diario Básico

```bash
# Inicio del día
git pull --rebase

# Durante el trabajo
git status
git add .
git commit -m "mensaje"

# Fin del día
git push
```

### Crear Feature Branch

```bash
git checkout main
git pull
git checkout -b feature/nueva-funcionalidad
# trabajar...
git add .
git commit -m "feat: nueva funcionalidad"
git push -u origin feature/nueva-funcionalidad
gh pr create
```

### Actualizar Branch con Main

```bash
git checkout feature-branch
git fetch origin
git rebase origin/main
git push --force-with-lease
```

### Limpiar Repositorio Local

```bash
git fetch --prune
git branch --merged main | grep -v "main" | xargs git branch -d
git gc
```

## Adaptación al Contexto

Cuando uses este skill, considera:

1. **Nivel de experiencia**: Simplifica comandos para principiantes, ofrece opciones avanzadas a expertos
2. **Sistema operativo**: Ajusta comandos (PowerShell vs Bash)
3. **Flujo de trabajo del equipo**: Pregunta sobre convenciones existentes
4. **Tamaño del repositorio**: Sugiere optimizaciones para repos grandes
5. **Situación específica**: Adapta la solución al contexto (¿es urgente? ¿afecta a producción?)

## Recursos Adicionales

- **Git Book oficial**: https://git-scm.com/book/es
- **GitHub Docs**: https://docs.github.com
- **GitHub CLI Manual**: https://cli.github.com/manual
- **Oh My Git!**: Juego para aprender git - https://ohmygit.org
- **Learn Git Branching**: Tutorial interactivo - https://learngitbranching.js.org

Recuerda: Git es poderoso pero puede ser intimidante. Explica el "por qué" detrás de cada comando, no solo el "cómo". Anima al usuario a experimentar en repositorios de práctica antes de aplicar comandos destructivos en proyectos reales.
