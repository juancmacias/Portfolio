# Portfolio - Guía para Agentes de IA

## 🏗️ Arquitectura General

**Sistema híbrido PHP + React** con tres capas principales:
- **Backend Admin PHP** (`admin/`): Panel de administración completo con gestión de artículos, proyectos e IA
- **API REST PHP** (`api/portfolio/`): Endpoints públicos para el frontend (proyectos, artículos, metadata, chat-rag)
- **Frontend React SPA** (`frontend/src/`): Portfolio público con React 18 + React Router 6 + Bootstrap 5

### Flujo de Datos
```
React Frontend → API PHP (public) → MySQL Database
Admin PHP → Classes → Database → MySQL
```

## 🗄️ Base de Datos

**Patrón Adaptativo**: `admin/config/database.php` detecta automáticamente PDO o MySQLi disponible.
- Clase singleton: `Database::getInstance()`
- Configuración local: `admin/config/config.local.php` (copiar desde `config.local.example.php`)
- Métodos principales: `fetchAll()`, `fetchOne()`, `execute()`, `lastInsertId()`

**Tablas clave**:
- `projects`: Proyectos del portfolio
- `articles`: Sistema de blog con soporte IA (campo `ai_generated`, `ai_model`, `ai_prompt`)
- `rag_documents`, `rag_chunks`, `rag_prompts`: Sistema RAG conversacional

## 🤖 Sistema de IA Multi-Proveedor

**Arquitectura de Proveedores** (`admin/classes/`):
- `AIProviderInterface.php`: Interfaz común para todos los proveedores
- `AIProviders.php`: Implementaciones concretas (GroqProvider, HuggingFaceProvider, OpenAIProvider)
- `AIContentGenerator.php`: Orquestador principal que coordina proveedores

**Configuración de API Keys**: 3 niveles de prioridad
1. Variables de entorno (`GROQ_API_KEY`, `HUGGINGFACE_API_KEY`)
2. Archivo local `admin/config/config.local.php` función `get_ai_config()`
3. Base de datos `system_config` tabla

**Modelos disponibles**:
- Groq: `llama-3.1-8b-instant` (default), `llama-3.1-70b-versatile`, `mixtral-8x7b-32768`
- Hugging Face: `meta-llama/Llama-3.2-3B-Instruct`, `mistralai/Mixtral-8x7B-Instruct-v0.1`

### Generación de Contenido
```php
$generator = new AIContentGenerator();
$result = $generator->generate($prompt, [
    'provider' => 'groq',
    'model' => 'llama-3.1-8b-instant',
    'max_tokens' => 1000,
    'temperature' => 0.7
]);
```

## 🔐 Sistema de Autenticación

**AdminAuth** (`admin/config/auth.php`):
- Sesiones PHP tradicionales (timeout 1 hora por defecto)
- Protección de rutas: Verificar `$auth->isLoggedIn()` en cada página admin
- Login: `admin/pages/login.php`
- Constante requerida: `define('ADMIN_ACCESS', true)` antes de incluir configs

**Proteger una página**:
```php
define('ADMIN_ACCESS', true);
require_once __DIR__ . '/../config/auth.php';
$auth = new AdminAuth();
$auth->requireLogin(); // Redirige si no autenticado
```

## 📚 Sistema RAG (Retrieval Augmented Generation)

**Ubicación**: `admin/classes/RAG/` y `admin/pages/rag/`

**Componentes**:
- `SemanticSearchEngine.php`: Búsqueda semántica con embeddings TF-IDF simples
- `PromptManager.php`: Gestión de prompts con variables dinámicas (`{{variable}}`)
- Endpoint público: `api/portfolio/chat-rag.php` con logging detallado en `logs/chat/`

**Flujo RAG**:
1. Usuario envía mensaje → `chat-rag.php`
2. Búsqueda semántica en documentos → Top 3 chunks relevantes
3. Prompt dinámico con contexto + mensaje → Groq LLM
4. Respuesta enriquecida con fuentes

## 🎨 Frontend React

**Configuración de URLs** (`frontend/src/Services/urls.js`):
- Detección automática entorno: `localhost/perfil.in` → local, resto → producción
- URLs dinámicas exportadas en `API_ENDPOINTS`
- **Importante**: Always use `API_ENDPOINTS.portfolio.*` para llamadas API

**Rutas principales**:
- `/`: Home con proyectos destacados
- `/project`: Lista completa de proyectos
- `/about`: Sobre mí
- `/resume`: CV
- `/articles`: Blog con sistema de búsqueda y filtros
- `/articles/:slug`: Detalle de artículo

**Componentes clave**:
- `components/Articles/ArticlesPage.js`: Lista paginada con filtros
- `components/Chat/ChatModal.js`: Chat RAG conversacional
- `components/Projects/Projects.js`: Filtros por tipo (web/app)

### Build y Deploy
```bash
cd frontend
npm install
npm start          # Desarrollo (puerto 3000)
npm run build      # Producción → frontend/build/
```

## 📝 Gestión de Artículos

**ArticleManager** (`admin/classes/ArticleManager.php`):
- CRUD completo con paginación y filtros
- Slug auto-generado desde título
- Tags en formato JSON array
- Contador de vistas (`incrementViews()`)
- Tiempo de lectura calculado automáticamente

**Campos especiales**:
- `featured_image`: URL de imagen destacada
- `reading_time`: Minutos estimados (calculado desde content length)
- `views`: Contador público (inicializado vía `admin/pages/article-actions.php?action=initialize_views`)

## 🗺️ Generador de Sitemap

**SitemapGenerator** (`admin/classes/SitemapGenerator.php`):
- Genera XML para SEO con detección de rutas React
- Crawl automático de rutas SPA
- URL base detectada: `localhost` → `http://www.perfil.in/`, producción → `https://www.juancarlosmacias.es/`
- Ejecutar desde: `admin/pages/sitemap-manager.php`

## 🛠️ Convenciones del Proyecto

### Rutas y URLs
- **NUNCA hardcodear URLs completas** en frontend: usar `API_ENDPOINTS` de `urls.js`
- Backend detecta entorno con `$_SERVER['HTTP_HOST']` (localhost vs producción)
- Admin URLs siempre relativas: `/admin/pages/...`

### Archivos de Configuración
- `config.local.php` **NO** debe estar en Git (ver `.gitignore`)
- Usar `config.local.example.php` como plantilla
- Credenciales sensibles: preferir variables de entorno

### Estructura de Clases PHP
- Una clase por archivo
- Autoload en `admin/includes/config.php`
- Namespace implícito por carpeta: `admin/classes/RAG/` para clases RAG

### Logs
- Logs de chat: `logs/chat/chat_YYYYMMDD.log` y `logs/chat/sessions/session_*.log`
- Usar `error_log()` para logs generales PHP
- `logChatEvent()` y `logDetailedChat()` en `chat-rag.php`

### Validación de Entrada
- Sanitizar con `htmlspecialchars()` en outputs HTML
- Prepared statements siempre en queries SQL
- Validar tipos de archivo en uploads: `allowed_image_types` en config

## 🚀 Workflows Comunes

### Agregar un Nuevo Proveedor de IA
1. Implementar `AIProviderInterface` en `admin/classes/AIProviders.php`
2. Registrar en `AIContentGenerator::$providers`
3. Añadir API key en `config.local.php` → `get_ai_config()`
4. Actualizar UI en `admin/pages/article-create.php` (dropdown de proveedores)

### Crear Nueva Página Admin
1. Crear archivo en `admin/pages/`
2. Incluir: `define('ADMIN_ACCESS', true)` + `require auth.php`
3. Usar layout: `admin/includes/layouts/header.php` y `footer.php`
4. Añadir enlace en `admin/includes/components/sidebar.php`

### Modificar Endpoints API
- Archivos en `api/portfolio/`
- Headers CORS configurados en cada endpoint
- Respuesta JSON: `header('Content-Type: application/json')` + `echo json_encode()`
- Manejar `OPTIONS` para preflight CORS

## 🔍 Debugging

- **Entorno local**: `display_errors = 1` automático si `localhost` detectado
- **Frontend**: Console logs en `urls.js` muestran entorno detectado
- **RAG**: Logs detallados en `logs/chat/` con timestamps y metadata completa
- **Database**: Queries fallidas lanzan excepciones (capturar con try-catch)

## � Control de Versiones (Git/GitHub)

### Convenciones de Commits
Usar **Conventional Commits** con prefijos:
- `feat:` - Nueva funcionalidad
- `fix:` - Corrección de bugs
- `chore:` - Tareas de mantenimiento
- `docs:` - Cambios en documentación
- `style:` - Cambios de formato/estilo
- `refactor:` - Refactorización de código
- `test:` - Agregar o modificar tests

**Ejemplos**:
```bash
feat: Sistema RAG completo con chat conversacional
fix: sitemap-manager route in admin navigation
chore: Ignorar carpetas logs y uploads
```

### Archivos NO Versionados (`.gitignore`)
**Crítico - nunca commitear**:
- `admin/config/config.local.php` - Credenciales de DB y API keys
- `.env`, `.env.local` - Variables de entorno
- `logs/` - Archivos de log
- `uploads/` - Archivos subidos por usuarios
- `.vscode/`, `.idea/` - Configuraciones IDE

### Workflow de Branches
- `main`: Branch principal, siempre estable
- Tags semánticos: `v1.0.x` para releases
- PRs: Usar template en `.github/PULL_REQUEST_TEMPLATE.md`

### Pull Requests
**Checklist obligatorio antes de merge**:
- [ ] Self-review del código
- [ ] Tests agregados para features core
- [ ] Considerar si requiere analytics
- [ ] Documentar en README si es feature importante

## �📦 Dependencias Externas

- React 18 + React Router 6 + Bootstrap 5
- PHP 7.4+ con PDO/MySQLi y cURL
- MySQL 5.7+
- APIs externas: Groq, Hugging Face, OpenAI (opcionales según configuración)
