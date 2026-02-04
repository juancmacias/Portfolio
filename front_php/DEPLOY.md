# 📦 Guía de Deploy - front_php (SSR PHP + React Hydration)

## 🎯 Descripción

Sistema de **Server-Side Rendering (SSR)** híbrido que combina:
- **PHP backend** para renderizado inicial HTML completo
- **React 18** con hydration para interactividad dinámica
- **Arquitectura similar a Next.js** pero sin Node.js en producción

## 📂 Estructura de Archivos para Producción

```
front_php/
├── index.php                    # ✅ Router SSR principal - OBLIGATORIO
├── .htaccess                    # ✅ Rewrite rules Apache - OBLIGATORIO
│
├── templates/                   # ✅ Templates PHP SSR - OBLIGATORIO
│   ├── Layout.php              #    HTML shell con __INITIAL_STATE__
│   └── ArticleView.php         #    Template de artículos (replica React)
│
├── static/                      # ✅ Assets compilados - OBLIGATORIO
│   ├── js/
│   │   ├── main.js             #    React bundle (262 KB gzipped)
│   │   ├── 453.chunk.js        #    Chunk adicional
│   │   └── main.js.LICENSE.txt #    Licencias open source
│   ├── css/
│   │   └── main.css            #    Estilos compilados (38 KB)
│   └── media/                  #    Imágenes procesadas por React
│       ├── about.png
│       ├── cv.pdf
│       └── pre.svg
│
├── public/                      # ✅ Assets públicos - OBLIGATORIO
│   ├── Assets/                 #    Recursos públicos (imágenes, PDFs)
│   ├── favicon.png
│   ├── manifest.json
│   ├── robots.txt
│   └── sitemap.xml
│
└── README.md                    # ⚠️ Documentación (opcional)
```

### ❌ NO Necesarios en Producción (NO subir)

```
front_php/
├── src/                         # ❌ Código fuente React (ya compilado)
├── build/                       # ❌ Output temporal (ya copiado a static/)
├── node_modules/                # ❌ Dependencias npm (solo para build)
├── package.json                 # ❌ Config npm (solo desarrollo)
├── package-lock.json            # ❌ Lock file npm
├── config-overrides.js          # ❌ Config webpack (solo build)
└── *.php (debug/test files)     # ❌ Archivos de testing ya eliminados
```

## 🔧 Requisitos del Servidor

### Software
- **PHP**: 7.4+ (recomendado 8.0+)
- **Apache**: 2.4+ con `mod_rewrite` habilitado
- **MySQL/PostgreSQL**: Base de datos con tabla `articles`

### Extensiones PHP Requeridas
```bash
# Verificar con: php -m
php_pdo
php_pdo_mysql  # o php_pdo_pgsql
php_json
php_mbstring
```

### Configuración Apache

#### 1. Habilitar mod_rewrite
```apache
# En httpd.conf
LoadModule rewrite_module modules/mod_rewrite.so
```

#### 2. Virtual Host Configurado
```apache
<VirtualHost *:80>
    ServerName tudominio.com
    DocumentRoot "E:/path/to/front_php"
    
    <Directory "E:/path/to/front_php">
        AllowOverride All        # ⚠️ CRÍTICO para .htaccess
        Require all granted
        Options -Indexes +FollowSymLinks
    </Directory>
    
    # Logs opcionales
    ErrorLog "logs/frontend-error.log"
    CustomLog "logs/frontend-access.log" common
</VirtualHost>
```

## 🗄️ Configuración de Base de Datos

### 1. Crear Archivo de Configuración
```bash
# En el directorio front_php/
cp config.example.php config.php
```

### 2. Editar Credenciales
```php
// config.php
define('DB_TYPE', 'mysql');          // o 'pgsql'
define('DB_HOST', 'localhost');      // Host de tu BD
define('DB_NAME', 'tu_base_datos');  // Nombre de tu BD
define('DB_USER', 'tu_usuario');     // Usuario de BD
define('DB_PASS', 'tu_contraseña');  // Contraseña
define('DB_PORT', 3306);             // 3306 MySQL, 5432 PostgreSQL
define('DB_CHARSET', 'utf8mb4');     // Codificación
```

**⚠️ IMPORTANTE**: 
- El archivo `config.php` **NO** debe subirse a Git (está en `.gitignore`)
- Solo sube `config.example.php` como referencia
- Cada servidor debe tener su propio `config.php` con credenciales reales

### 3. Verificar Tabla Articles
```sql
-- La tabla debe tener estos campos mínimos
CREATE TABLE IF NOT EXISTS articles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    slug VARCHAR(255) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    excerpt TEXT,
    content LONGTEXT,
    featured_image VARCHAR(500),
    tags JSON,
    published_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('draft', 'published') DEFAULT 'draft',
    INDEX idx_slug (slug),
    INDEX idx_status (status)
);
```

### 4. Test de Conexión
```bash
# Verificar que PHP puede conectar
php -r "define('ADMIN_ACCESS', true); require 'config.php'; echo 'Conexión OK';"
```

## 🚀 Pasos de Instalación

### 1. Subir Archivos
```bash
# Subir solo los archivos necesarios
scp -r front_php/ usuario@servidor:/var/www/html/
```

### 2. Configurar Permisos
```bash
# En el servidor
cd /var/www/html/front_php
chmod 644 index.php config.php
chmod 644 .htaccess
chmod -R 755 templates/
chmod -R 755 static/
chmod -R 755 public/
```

### 3. Configurar Base de Datos
```bash
# Crear archivo de configuración desde el ejemplo
cp config.example.php config.php

# Editar con tus credenciales reales
nano config.php  # o vim, o cualquier editor
```

### 4. Verificar Rutas (si usas estructura diferente)
```php
// Si necesitas ajustar rutas, edita index.php línea ~42
$localConfig = __DIR__ . '/config.php';  // Ruta al config local
```

## 🧪 Testing Post-Deploy

### 1. Verificar Apache Config
```bash
# En el servidor
apache2ctl configtest  # o httpd -t
sudo systemctl restart apache2
```

### 2. Probar Endpoints

**Home (CSR)**
```bash
curl -I https://tudominio.com/
# Debe retornar: 200 OK
# Content-Type: text/html
```

**Artículo (SSR)**
```bash
curl https://tudominio.com/article/ejemplo-slug
# Debe contener HTML completo con contenido del artículo
# Buscar: <script id="__INITIAL_STATE__">
```

### 3. Verificar Hydration en Navegador

**Abrir DevTools Console:**
```javascript
// Debe aparecer:
✅ Initial state cargado para hidratación: article/ejemplo-slug
🚀 Hidratando aplicación con SSR state: {route, title, isSSR: true}
```

**NO debe aparecer:**
```javascript
❌ Uncaught Error: Minified React error #423
❌ Hydration mismatch warnings
```

### 4. Test de Performance

**Lighthouse Audit:**
- **SEO Score**: 90+ ✅
- **First Contentful Paint**: < 1.5s ✅
- **Time to Interactive**: < 3s ✅

**Verificar Meta Tags:**
```bash
curl -s https://tudominio.com/article/ejemplo | grep -E '<title>|<meta'
# Debe mostrar título y description dinámicos
```

## 🔍 Troubleshooting

### Error: 403 Forbidden
**Causa**: `AllowOverride` no configurado
```apache
# Solución: En VirtualHost
<Directory "/path/to/front_php">
    AllowOverride All
</Directory>
```

### Error: 404 en rutas de artículos
**Causa**: mod_rewrite no habilitado o .htaccess no se lee
```bash
# Verificar
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Error: "Acceso directo no permitido"
**Causa**: Falta constante de seguridad
```php
// Solución: Verifica que index.php tenga al inicio
define('ADMIN_ACCESS', true);
```

### Error: "No se encontró el archivo de configuración"
**Causa**: Falta el archivo `config.php`
```bash
# Solución:
cp config.example.php config.php
nano config.php  # Edita con tus credenciales
```

### Error: "No se puede conectar a la base de datos"
**Causa**: Credenciales incorrectas o extensión PHP faltante
```bash
# 1. Verificar extensiones PHP
php -m | grep -E 'pdo|mysqli'

# 2. Verificar credenciales en config.php
# 3. Test de conexión
php -r "
define('ADMIN_ACCESS', true);
require 'config.php';
try {
    \$db = Database::getInstance();
    echo 'Conexión exitosa';
} catch (Exception \$e) {
    echo 'Error: ' . \$e->getMessage();
}
"
```

### Hydration Mismatch (Error #423)
**Causa**: HTML del template PHP no coincide con React
```bash
# Verificar formato de fecha en templates/ArticleView.php
# Debe usar: formatDateSpanish() no date('d M Y')
```

### Contenido no aparece (solo shell vacío)
**Causa**: Base de datos no conecta o query falla
```php
// Debug: Añadir al inicio de index.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

## 📊 Monitorización

### Logs a Revisar
```bash
# Apache error log
tail -f /var/log/apache2/error.log

# Apache access log
tail -f /var/log/apache2/access.log

# PHP error log
tail -f /var/log/php/error.log
```

### Métricas Clave
- **Tiempo respuesta SSR**: < 200ms
- **Tamaño HTML inicial**: ~50-100 KB
- **Bundle JS total**: ~264 KB (gzipped)
- **Cache hit rate**: > 80% (si se implementa)

## 🔐 Seguridad

### Recomendaciones
1. **HTTPS Obligatorio**: Usar Let's Encrypt o similar
2. **Headers de seguridad**: Añadir en .htaccess
   ```apache
   Header set X-Content-Type-Options "nosniff"
   Header set X-Frame-Options "DENY"
   Header set X-XSS-Protection "1; mode=block"
   ```
3. **Sanitización**: Todas las salidas usan `htmlspecialchars()`
4. **Prepared Statements**: Todas las queries usan PDO preparado

## 🎨 Personalización

### Cambiar URL Base
```php
// templates/ArticleView.php - Línea ~80
$urlApi = 'https://tudominio.com/';
```

### Añadir Nuevas Rutas SSR
```php
// index.php - Función getRoute()
// Añadir nuevo case:
case 'nueva-ruta':
    echo renderLayout(renderNuevaRuta(), generateState(...));
    break;
```

### Añadir Nuevos Templates
```php
// 1. Crear templates/NuevaRuta.php
function renderNuevaRuta() {
    // HTML que replica componente React
}

// 2. Crear src/components/NuevaRuta.js
// Componente React que coincida exactamente
```

## 📈 Optimizaciones Futuras

### Caché PHP
```php
// Implementar OPcache
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
```

### CDN para Estáticos
```html
<!-- Servir JS/CSS desde CDN -->
<script src="https://cdn.tudominio.com/static/js/main.js"></script>
```

### Lazy Loading de Rutas
```javascript
// React.lazy() para code splitting
const ArticleView = React.lazy(() => import('./components/Articles/ArticleView'));
```

## 📚 Documentación Adicional

- [Arquitectura técnica completa](../doc/arquitectura-tecnica.md)
- [Guía de implementación original](../doc/guia-implementacion.md)
- [React 18 Hydration API](https://react.dev/reference/react-dom/client/hydrateRoot)

---

**Versión**: 1.0.0-poc  
**Última actualización**: 2 de febrero de 2026  
**Autor**: Juan Carlos Macías
