# Portfolio v1.6.0 - Optimización Frontend y Mejoras SEO

## 🎯 Cambios Principales

### ⚡ Optimización de Rendimiento
**Reducción del 70% en tamaño del bundle principal**

- **Bundle size**: 878 KB → 265 KB gzipped (↓70%)
- **TerserPlugin**: Eliminación automática de `console.*` en producción
- **Source maps**: Deshabilitados en build de producción (13.2 MB ahorrados)
- **Cache headers**: 1 año para assets estáticos
- **Gzip**: Compresión level 6 para todos los recursos

**Archivos optimizados:**
- `frontend/config-overrides.js` - Configuración TerserPlugin con opciones:
  - `drop_console: true`
  - `drop_debugger: true`
  - `pure_funcs: ['console.log', 'console.info', 'console.debug']`

### 🏗️ Soporte para Hosting Compartido (public_html/)
**Sistema robusto de detección de templates para producción**

**Detección multicapa con wildcards:**
- `public_html/templates/` (priority 1)
- `public_html/public/templates/`
- `/home/*/public_html/templates/` (con expansión glob)
- `$_SERVER['DOCUMENT_ROOT'] . '/templates'`
- Fallbacks para desarrollo local

**Archivos modificados:**
- `frontend/index.php` - Detección de templates con 8+ rutas fallback
- `frontend/build/index.php` - Sincronizado con lógica de producción
- `frontend/build/diagnostic.php` - Script temporal para troubleshooting

**Características:**
- Expansión de wildcards con `glob()` para detectar username en hosting compartido
- Logs detallados con `error_log()` para debugging
- Compatible con cPanel, DirectAdmin y hosting genérico

### 🔍 Mejoras SEO Avanzadas

#### JSON-LD Dual Entity (@graph)
**Implementación de esquema semántico Organization + Person**

```json
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "Organization",
      "@id": "#organization",
      "name": "Juan Carlos Macias",
      "founder": { "@id": "#person" }
      // ... datos corporativos
    },
    {
      "@type": "Person",
      "@id": "#person",
      "name": "Juan Carlos Macías Moreno",
      "jobTitle": "Desarrollador Full Stack e Ingeniero en IA",
      "knowsAbout": ["React", "Java", "PHP", "Python", "Machine Learning", "LLMs", "MLOps"],
      "mainEntityOfPage": "https://www.juancarlosmacias.es/project"
      // ... perfil profesional
    }
  ]
}
```

**Beneficios:**
- ✅ Dual visibility en Google (persona + marca)
- ✅ Knowledge Graph eligibility
- ✅ Rich Snippets para búsquedas profesionales
- ✅ Indexación mejorada: "desarrollador LLM Madrid", "ingeniero IA"

**Archivos:**
- `frontend/public/templates/Layout.php` - JSON-LD generado con `json_encode()` UTF-8
- Flags: `JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT`

#### Meta Description Optimizada
**168 caracteres con keywords estratégicas**

```html
<meta name="description" content="Portfolio de Juan Carlos Macias: Desarrollador Full Stack PHP/React, 
especializado en inteligencia artificial, LLMs y gestión empresarial. Proyectos con Java, Python y Spring Boot">
```

### 🔧 Correcciones Técnicas

#### .htaccess Limpio
**Removidas directivas problemáticas que causaban Error 500:**

- ❌ `ErrorDocument` con URLs absolutas
- ❌ `Header unset ETag/Last-Modified` (incompatible con mod_headers)
- ❌ `DeflateCompressionLevel` (no universal)
- ❌ `AddLanguage` (sintaxis problemática)

**Mantenidas:**
- ✅ Reglas de compresión gzip para JS, CSS, HTML
- ✅ Cache headers con ExpiresActive
- ✅ Rewrite rules para React Router

#### URLs Dinámicas
**Sistema getBaseUrl() para detección automática de entorno**

```php
function getBaseUrl() {
    $host = $_SERVER['HTTP_HOST'];
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    
    if ($host === 'localhost' || $host === 'www.frontend.pru') {
        return 'http://www.frontend.pru';
    }
    return $protocol . '://' . $host;
}
```

**Archivos modificados:**
- `frontend/public/templates/ArticleView.php` - URLs dinámicas eliminando hardcoded localhost
- Detección automática para OG tags, canonical URLs, JSON-LD

### 📦 Sistema de Releases

**Estructura implementada:**
```
releases/
├── README.md                      # Convenciones y formato
├── RELEASE_NOTES_v1.3.0.md
├── RELEASE_NOTES_v1.5.0.md
└── RELEASE_NOTES_v1.6.0.md        # Este archivo

scripts/
└── manage-release.ps1             # Script PowerShell para gestión
```

**Comandos disponibles:**
```powershell
# Listar releases
.\scripts\manage-release.ps1 -Action list

# Crear release
.\scripts\manage-release.ps1 -Action create -Version v1.6.0 -Title "Optimización Frontend"

# Crear draft
.\scripts\manage-release.ps1 -Action draft -Version v1.6.0
```

## 📊 Archivos Modificados

### Frontend Core
- `frontend/config-overrides.js` - TerserPlugin + optimizations
- `frontend/index.php` - Template detection multicapa
- `frontend/public/.htaccess` → `frontend/build/.htaccess` - Clean version
- `frontend/public/index.html` - Meta description actualizada

### Templates SSR
- `frontend/public/templates/Layout.php` - JSON-LD @graph con json_encode()
- `frontend/public/templates/ArticleView.php` - URLs dinámicas con getBaseUrl()
- `frontend/templates/*.php` - Sincronizados con public/

### Build System
- `frontend/build/index.php` - Producción-ready con detección public_html/
- `frontend/build/templates/` - Copiados automáticamente en build
- `frontend/build/diagnostic.php` - **TEMPORAL** - Troubleshooting script

### Backend Admin
- `admin/api/ai.php` - Mejoras menores
- `admin/pages/article-create.php` - Ajustes UI

### Componentes React
- `frontend/src/components/Home/Home.js` - Optimizaciones menores

### Releases & Scripts
- `.agents/skills/github-releases/SKILL.md` - Documentación completa
- `scripts/manage-release.ps1` - **NUEVO** - Script gestión releases
- `releases/README.md` - **NUEVO** - Guía de releases
- `releases/RELEASE_NOTES_v1.3.0.md` - Migrado desde raíz
- `releases/RELEASE_NOTES_v1.5.0.md` - **NUEVO** - Release anterior
- `releases/RELEASE_NOTES_v1.6.0.md` - **NUEVO** - Esta release

## 🚀 Despliegue

### Archivos para Subir a Producción (public_html/)

1. **index.php** → `public_html/index.php`
2. **.htaccess** → `public_html/.htaccess`
3. **templates/** → `public_html/templates/` (carpeta completa)
   - Layout.php
   - ArticleView.php
4. **diagnostic.php** → `public_html/diagnostic.php` (temporal - eliminar después)

### Verificación Post-Deploy

1. Ejecutar: `https://www.juancarlosmacias.es/diagnostic.php`
2. Verificar detección de templates en primera ruta
3. Probar artículo: `https://www.juancarlosmacias.es/article/conseguir-resenas-de-google`
4. Validar JSON-LD en source HTML
5. Eliminar `diagnostic.php`

### Pruebas de Rendimiento

```bash
# Verificar gzip
curl -I -H "Accept-Encoding: gzip" https://www.juancarlosmacias.es/static/js/main.*.js

# Validar cache headers
curl -I https://www.juancarlosmacias.es/static/js/main.*.js | grep -E "(Cache-Control|Expires)"
```

## 📈 Impacto

- **Performance**: 70% reducción bundle → Mejora PageSpeed Insights
- **SEO**: Dual entity JSON-LD → Mayor visibilidad en Google Search
- **Hosting**: Soporte hosting compartido → Compatible con cualquier cPanel
- **Mantenibilidad**: Sistema de releases → Trazabilidad completa

## 🔗 Links Útiles

- **Repositorio**: https://github.com/juancmacias/Portfolio
- **Production**: https://www.juancarlosmacias.es
- **Dev Local**: http://www.frontend.pru

## 👨‍💻 Autor

**Juan Carlos Macías Moreno**  
Desarrollador Full Stack e Ingeniero en IA

---

**Versión**: 1.6.0  
**Fecha**: 16 de abril de 2026  
**Commit**: 7c775b8
