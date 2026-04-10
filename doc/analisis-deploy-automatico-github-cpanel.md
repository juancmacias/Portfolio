# Análisis: Deploy Automático GitHub → cPanel

**Fecha:** 5 de marzo de 2026  
**Versión del Proyecto:** v1.2.1  
**Objetivo:** Evaluar opciones para automatizar el despliegue desde GitHub a cPanel sin subidas manuales

---

## 📋 Resumen Ejecutivo

**¿Es posible?** ✅ **SÍ** - Existen múltiples métodos para automatizar el deploy desde GitHub a cPanel.

**Complejidad:** 🟡 **Media** - Requiere configuración inicial pero ahorra tiempo a largo plazo.

**Recomendación:** ⭐ **Git Version Control (cPanel integrado)** + GitHub Actions para builds de React.

---

## 🎯 Opciones Disponibles

### **Opción 1: Git Version Control de cPanel** ⭐ RECOMENDADA
**Descripción:** cPanel incluye una herramienta nativa de Git que permite clonar y sincronizar repositorios.

#### ✅ Ventajas
- ✅ Nativo de cPanel (no requiere herramientas externas)
- ✅ Interfaz gráfica amigable
- ✅ Deploy con un clic desde el panel
- ✅ Soporte para GitHub, GitLab, Bitbucket
- ✅ Webhook support para auto-deploy
- ✅ Gestión de claves SSH integrada

#### ❌ Desventajas
- ❌ No ejecuta `npm run build` automáticamente
- ❌ Requiere subir el build precompilado o configurar post-deploy hooks
- ❌ Algunos hostings baratos desactivan esta función

#### 📊 Valoración: **8/10**

#### 🔧 Requisitos
- cPanel versión 58+ (mayoría de hostings modernos)
- Acceso SSH (opcional pero recomendado)
- Git instalado en el servidor

---

### **Opción 2: GitHub Actions + FTP/SFTP Deploy**
**Descripción:** Pipeline CI/CD que compila el frontend y despliega automáticamente vía FTP.

#### ✅ Ventajas
- ✅ Totalmente automático (push → build → deploy)
- ✅ Compila React automáticamente (`npm run build`)
- ✅ Control total del pipeline
- ✅ Puede ejecutar tests antes de deplegar
- ✅ Funciona con cualquier hosting (incluso los más básicos)

#### ❌ Desventajas
- ❌ Requiere credenciales FTP en GitHub Secrets
- ❌ Las transferencias FTP pueden ser lentas
- ❌ Sube TODO el proyecto en cada deploy (no solo cambios)

#### 📊 Valoración: **7/10**

#### 🔧 Requisitos
- Credenciales FTP/SFTP del hosting
- Configurar GitHub Secrets
- Crear workflow `.github/workflows/deploy.yml`

---

### **Opción 3: Git Deployment Scripts (post-receive hooks)**
**Descripción:** Scripts personalizados que se ejecutan automáticamente tras un `git pull`.

#### ✅ Ventajas
- ✅ Control total sobre el proceso de deploy
- ✅ Puede ejecutar `npm run build`, migraciones DB, etc.
- ✅ Más rápido que FTP (solo cambios incrementales)
- ✅ Integración perfecta con Git

#### ❌ Desventajas
- ❌ Requiere acceso SSH obligatorio
- ❌ Configuración técnica compleja
- ❌ Necesita Node.js instalado en el servidor
- ❌ Algunos hostings compartidos bloquean hooks

#### 📊 Valoración: **9/10** (si tienes acceso SSH)

#### 🔧 Requisitos
- Acceso SSH con permisos
- Node.js y npm en el servidor
- Conocimientos de bash scripting

---

### **Opción 4: Deploy Manual con Git Pull**
**Descripción:** Conectarse por SSH y ejecutar `git pull` manualmente.

#### ✅ Ventajas
- ✅ Simple y directo
- ✅ Control total de cuándo se despliega
- ✅ Ideal para proyectos pequeños

#### ❌ Desventajas
- ❌ No es automático (requiere intervención)
- ❌ Propenso a errores humanos
- ❌ No compila el frontend automáticamente

#### 📊 Valoración: **5/10**

---

### **Opción 5: Servicios de CI/CD Externos**
**Descripción:** Plataformas como DeployHQ, Buddy, CircleCI que gestionan el deploy.

#### ✅ Ventajas
- ✅ Interfaz profesional y fácil de usar
- ✅ Soporte para builds complejos
- ✅ Rollbacks automáticos
- ✅ Notifications y logs detallados

#### ❌ Desventajas
- ❌ Costo mensual ($10-50/mes según plan)
- ❌ Overkill para proyectos personales
- ❌ Dependencia de terceros

#### 📊 Valoración: **6/10** (solo si es proyecto comercial)

---

## 🏆 Recomendación Final: SOLUCIÓN HÍBRIDA

### **Configuración Recomendada para tu Portfolio**

#### **1. Desarrollo Local → GitHub**
```bash
# Local
git add .
git commit -m "feat: nueva funcionalidad"
git push origin main
```

#### **2. GitHub Actions → Build Automático**
Workflow que compila el frontend y genera `frontend/build/`:
```yaml
# .github/workflows/build.yml
name: Build Frontend
on:
  push:
    branches: [main]
jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: '18'
      - name: Install dependencies
        run: cd frontend && npm ci
      - name: Build React
        run: cd frontend && npm run build
      - name: Commit build
        run: |
          git config user.name "GitHub Actions"
          git config user.email "actions@github.com"
          git add frontend/build/
          git commit -m "chore: auto-build frontend [skip ci]" || exit 0
          git push
```

#### **3. cPanel Git → Deploy Automático**
- Clonar repo en cPanel Git Version Control
- Configurar webhook de GitHub
- Cada push actualiza automáticamente el servidor

#### **Resultado:**
```
Local (código) → GitHub (push) → Actions (build) → cPanel (deploy) → PRODUCCIÓN
```

---

## 📝 Plan de Implementación Detallado

### **Fase 1: Preparación (15 minutos)**

#### A. Verificar Requisitos del Hosting
```bash
# Conectar por SSH y verificar:
ssh usuario@tudominio.com

# Verificar Git
git --version
# Debe mostrar: git version 2.x.x

# Verificar Node.js (opcional pero recomendado)
node --version
npm --version
```

#### B. Comprobar Acceso cPanel Git
1. Acceder a cPanel
2. Buscar "Git Version Control" en herramientas
3. Si no aparece → contactar soporte del hosting

---

### **Fase 2: Configurar GitHub Actions (30 minutos)**

#### Archivo: `.github/workflows/build-and-commit.yml`
```yaml
name: Build and Commit Frontend

on:
  push:
    branches: [main]
    paths:
      - 'frontend/src/**'
      - 'frontend/public/**'
      - 'frontend/package.json'

jobs:
  build:
    runs-on: ubuntu-latest
    
    steps:
      - name: Checkout code
        uses: actions/checkout@v3
        with:
          token: ${{ secrets.GITHUB_TOKEN }}
          
      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: '18'
          cache: 'npm'
          cache-dependency-path: frontend/package-lock.json
          
      - name: Install dependencies
        run: |
          cd frontend
          npm ci
          
      - name: Build React
        run: |
          cd frontend
          npm run build
          
      - name: Commit build files
        run: |
          git config --local user.email "actions@github.com"
          git config --local user.name "GitHub Actions"
          git add frontend/build/ -f
          git diff --quiet && git diff --staged --quiet || \
            (git commit -m "chore: auto-build frontend [skip ci]" && git push)
```

#### Configurar GitHub Secrets
1. Ve a Settings → Secrets and variables → Actions
2. No necesitas agregar secrets si usas `GITHUB_TOKEN` (automático)

---

### **Fase 3: Configurar cPanel Git (20 minutos)**

#### A. Crear Repositorio en cPanel
1. Acceder a cPanel → Git Version Control
2. Clic en "Create"
3. Configurar:
   - **Repository Path:** `/home/usuario/public_html/Portfolio`
   - **Repository Name:** `Portfolio`
   - **Clone URL:** `https://github.com/juancmacias/Portfolio.git`
   - **Branch:** `main`

#### B. Configurar Claves SSH (Recomendado)
```bash
# En tu máquina local, generar clave SSH si no tienes
ssh-keygen -t ed25519 -C "cpanel-deploy"

# Copiar clave pública
cat ~/.ssh/id_ed25519.pub

# En cPanel → SSH Access → Manage SSH Keys
# Importar la clave pública

# En GitHub → Settings → Deploy keys
# Agregar la clave pública con acceso de solo lectura
```

#### C. Configurar Webhook (Auto-Deploy)
1. En cPanel Git → Clic en "Manage" del repo
2. Copiar la **Webhook URL** (ej: `https://tudominio.com:2083/cpsess.../webhook/...`)
3. En GitHub → Settings → Webhooks → Add webhook
4. Configurar:
   - **Payload URL:** La URL copiada de cPanel
   - **Content type:** `application/json`
   - **Events:** Solo "Push events"
   - **Active:** ✅

---

### **Fase 4: Configurar Post-Deploy Hook (Opcional - Avanzado)**

Si necesitas ejecutar comandos después del deploy:

#### Archivo: `.cpanel.yml` (en raíz del proyecto)
```yaml
---
deployment:
  tasks:
    - export DEPLOYPATH=/home/usuario/public_html/Portfolio
    - /bin/cp -R frontend/build/* $DEPLOYPATH/
    - /bin/cp rss.php $DEPLOYPATH/
    - /bin/cp .htaccess $DEPLOYPATH/
    - /bin/cp -R admin $DEPLOYPATH/
    - /bin/cp -R api $DEPLOYPATH/
    # Si tienes Node.js en el servidor:
    # - cd $DEPLOYPATH/frontend && npm ci && npm run build
```

**Nota:** `.cpanel.yml` solo funciona si tu hosting tiene "cPanel Deployment Pipeline" habilitado.

---

## ⚠️ Consideraciones Importantes

### **1. Archivos Sensibles**
```
✅ Ya tienes config.local.php en .gitignore
✅ Crear config.local.php directamente en el servidor
✅ No commitear credenciales nunca
```

### **2. Build de React**
**Opción A: Build en GitHub Actions** (Recomendada)
- ✅ No requiere Node.js en el servidor
- ✅ Más rápido (build en GitHub, no en tu hosting)
- ❌ El repo crece (frontend/build/ se versiona)

**Opción B: Build en el servidor**
- ✅ Repo más limpio (no versionar build/)
- ❌ Requiere Node.js en cPanel
- ❌ Builds lentos (servidores compartidos son débiles)

### **3. Base de Datos**
```bash
# Las migraciones DB NO se ejecutan automáticamente
# Debes hacerlas manualmente por SSH:
ssh usuario@tudominio.com
cd /home/usuario/public_html/Portfolio
mysql -u usuario -p basedatos < database/migrations/nueva_migracion.sql
```

### **4. Permisos de Archivos**
```bash
# Después del primer deploy, configurar permisos:
chmod 755 logs/ logs/chat/ logs/chat/sessions/
chmod 755 uploads/ uploads/documents/
chmod 644 admin/config/config.local.php
```

---

## 📊 Comparativa de Métodos

| Método | Automatización | Complejidad | Costo | Requiere SSH | Build React | Valoración |
|--------|---------------|-------------|-------|--------------|-------------|------------|
| **cPanel Git + Actions** | ⭐⭐⭐⭐⭐ | 🟡 Media | Gratis | Opcional | ✅ | **9/10** |
| **GitHub Actions + FTP** | ⭐⭐⭐⭐⭐ | 🟡 Media | Gratis | No | ✅ | 7/10 |
| **Git Hooks** | ⭐⭐⭐⭐ | 🔴 Alta | Gratis | ✅ | ✅ | 9/10 |
| **Deploy Manual** | ⭐ | 🟢 Baja | Gratis | ✅ | ❌ | 5/10 |
| **CI/CD Externo** | ⭐⭐⭐⭐⭐ | 🟢 Baja | $10-50/mes | No | ✅ | 6/10 |

---

## 🚀 Flujo de Trabajo Recomendado

### **Situación Actual (Manual)**
```
1. Desarrollo local
2. git push origin main
3. Compilar frontend: npm run build
4. Subir vía FTP/cPanel File Manager:
   - frontend/build/
   - rss.php
   - admin/
   - api/
   - .htaccess
5. Verificar que funciona
```
⏱️ **Tiempo:** 10-15 minutos por deploy

---

### **Situación Propuesta (Automática)**
```
1. Desarrollo local
2. git push origin main
   ↓ (automático)
3. GitHub Actions compila React
   ↓ (automático)
4. GitHub Actions hace commit del build
   ↓ (automático)
5. Webhook dispara cPanel Git
   ↓ (automático)
6. cPanel actualiza archivos
   ↓
7. ✅ PRODUCCIÓN ACTUALIZADA
```
⏱️ **Tiempo:** 2-3 minutos (todo automático)

---

## 💰 Análisis Coste-Beneficio

### **Tiempo Ahorrado**
- Deploys por mes: ~10-20
- Tiempo manual por deploy: 10 min
- Tiempo automático: 0 min (solo push)
- **Ahorro mensual: 100-200 minutos = 1.5-3 horas**

### **Costos**
- GitHub Actions: Gratis (2000 minutos/mes en plan free)
- cPanel Git: Incluido en la mayoría de hostings
- **Costo total: $0**

### **ROI (Return on Investment)**
- Inversión inicial: 1 hora de configuración
- Recuperación: Después del 3er o 4to deploy
- **Beneficio neto: ∞ (automático para siempre)**

---

## 🎓 Recursos y Documentación

### **Documentación Oficial**
- [cPanel Git Version Control](https://docs.cpanel.net/cpanel/files/git-version-control/)
- [GitHub Actions Docs](https://docs.github.com/en/actions)
- [Deployment con GitHub](https://docs.github.com/en/actions/deployment)

### **Tutoriales Recomendados**
- [cPanel Git Deploy Tutorial](https://www.youtube.com/results?search_query=cpanel+git+deploy)
- [GitHub Actions for FTP Deploy](https://github.com/marketplace/actions/ftp-deploy)

### **Herramientas Útiles**
- [GitHub Actions Marketplace](https://github.com/marketplace?type=actions&query=deploy)
- [Webhook Testing](https://webhook.site/)

---

## ✅ Checklist de Implementación

### **Pre-requisitos**
- [ ] Verificar versión de cPanel (mínimo v58)
- [ ] Confirmar que Git Version Control está disponible
- [ ] Tener acceso SSH (recomendado)
- [ ] Verificar espacio en disco suficiente (+500MB)

### **Configuración Básica**
- [ ] Crear `.github/workflows/build-and-commit.yml`
- [ ] Probar workflow en GitHub Actions
- [ ] Configurar repo en cPanel Git Version Control
- [ ] Hacer primer clone manual
- [ ] Verificar que archivos se clonan correctamente

### **Configuración Avanzada**
- [ ] Generar claves SSH
- [ ] Agregar deploy key en GitHub
- [ ] Configurar webhook GitHub → cPanel
- [ ] Probar auto-deploy con un commit test
- [ ] Crear `.cpanel.yml` si es necesario

### **Post-Configuración**
- [ ] Crear `config.local.php` en el servidor
- [ ] Configurar permisos de logs/ y uploads/
- [ ] Importar/actualizar base de datos
- [ ] Probar todas las URLs del sitio
- [ ] Verificar que RSS funciona
- [ ] Revisar error logs

---

## 🔮 Recomendaciones Futuras

### **Corto Plazo (1-2 semanas)**
1. Implementar deploy automático básico (cPanel Git)
2. Configurar GitHub Actions para builds
3. Documentar el proceso en README.md

### **Medio Plazo (1-2 meses)**
1. Agregar tests automáticos antes de deploy
2. Implementar staging environment (rama develop)
3. Configurar notificaciones de deploy (Slack/Email)

### **Largo Plazo (3-6 meses)**
1. Considerar migrar a VPS con Docker (más control)
2. Implementar rollback automático en caso de errores
3. Configurar monitoreo de uptime (UptimeRobot)

---

## 📞 Soporte y Ayuda

### **Si algo falla:**
1. Revisar logs de GitHub Actions (pestaña Actions en repo)
2. Revisar logs de cPanel (Track Changes)
3. Consultar documentación del hosting
4. Contactar soporte técnico de cPanel

### **Comandos Útiles de Diagnóstico**
```bash
# Ver último deploy
cd /home/usuario/public_html/Portfolio
git log -1

# Ver estado del repo
git status

# Forzar pull (si hay conflictos)
git fetch origin
git reset --hard origin/main

# Ver webhooks recibidos
# (en cPanel Git → Manage → View Logs)
```

---

## 📌 Conclusión

**¿Vale la pena implementarlo?** ✅ **ABSOLUTAMENTE SÍ**

**Razones:**
1. ⏱️ Ahorra 100+ minutos al mes
2. 🐛 Reduce errores humanos
3. 🚀 Deploy consistente y reproducible
4. 💰 Costo: $0
5. 📈 Escalable para futuros proyectos

**Dificultad real:** 🟡 Media-Baja (1 hora de setup inicial)

**Próximo paso sugerido:** Empezar con cPanel Git básico (sin webhook) para familiarizarte, luego agregar automatización.

---

**Autor:** Análisis realizado para Portfolio v1.2.1  
**Estado:** ✅ Listo para implementación  
**Prioridad:** 🟡 Media-Alta (mejora de workflow, no urgente)
