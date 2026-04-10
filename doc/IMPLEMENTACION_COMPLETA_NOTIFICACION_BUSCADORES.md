# Sistema de Notificación a Buscadores - Implementación Completa

**Fecha**: <?php echo date('Y-m-d'); ?>  
**Estado**: ✅ Implementación completada  
**Versión**: 1.0

---

## 📋 Resumen Ejecutivo

Se ha implementado un **sistema completo de notificación automática a buscadores** que reemplaza la funcionalidad anterior en `admin/`. El nuevo sistema soporta **3 proveedores principales**:

1. **IndexNow API** - Notifica simultáneamente a Bing, Yandex, Naver y Seznam
2. **Bing Webmaster Tools API** - Integración oficial con Bing
3. **Google Search Console API** - Envío de sitemap via Service Account

---

## 🏗️ Arquitectura Implementada

### Patrón de Diseño
- **Provider Pattern**: Cada proveedor implementa una interfaz común
- **Orchestrator**: `SearchEngineNotifier` coordina todos los proveedores
- **Logging centralizado**: `SearchEngineLogger` para debugging

### Componentes Creados

#### 1. Sistema de Logs
**Archivo**: `admin/classes/SearchEngineLogger.php`

```php
// Uso
$logger = new SearchEngineLogger('IndexNow');
$logger->log('Mensaje', ['contexto' => 'valor']);
```

**Ubicación logs**: `logs/search-engines/{provider}_YYYY-MM-DD.log`

---

#### 2. Proveedores de Notificación

##### IndexNowProvider
**Archivo**: `admin/classes/Providers/IndexNowProvider.php`

**Características**:
- Notifica a 4 buscadores con una sola llamada API
- Procesamiento por lotes (máximo 10,000 URLs por batch)
- Verificación mediante archivo `{key}.txt` en raíz pública

**Requisitos**:
- Archivo: `admin/config/indexnow-key.txt` (32 caracteres hexadecimales)
- Archivo público: `/{key}.txt` en raíz del sitio

**Endpoint**: `https://api.indexnow.org/indexnow`

---

##### BingWebmasterProvider
**Archivo**: `admin/classes/Providers/BingWebmasterProvider.php`

**Características**:
- API oficial de Bing Webmaster Tools
- Procesamiento por lotes (máximo 10,000 URLs)
- Autenticación por API Key

**Requisitos**:
- Archivo: `admin/config/bing-api-key.txt`
- Obtener key desde: https://www.bing.com/webmasters

**Endpoint**: `https://ssl.bing.com/webmaster/api.svc/json/SubmitUrlbatch`

---

##### GoogleSearchConsoleProvider
**Archivo**: `admin/classes/Providers/GoogleSearchConsoleProvider.php`

**Características**:
- Autenticación OAuth 2.0 con Service Account
- Envío de sitemap.xml completo (no URLs individuales)
- Manejo de errores 403/404

**Requisitos**:
- Archivo: `admin/config/service-account-credentials.json`
- Librería: `google/apiclient` (instalar via Composer)
- Service Account con permisos en Google Search Console

**Endpoint**: Google Search Console API v3

**Configuración Service Account**:
```json
{
  "type": "service_account",
  "project_id": "tu-proyecto",
  "private_key_id": "...",
  "private_key": "-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----\n",
  "client_email": "cuenta@proyecto.iam.gserviceaccount.com"
}
```

**Pasos configuración**:
1. Crear Service Account en Google Cloud Console
2. Descargar JSON de credenciales
3. Añadir email del Service Account como propietario en Search Console
4. Verificar propiedad del dominio

---

#### 3. Orquestador Central
**Archivo**: `admin/classes/SearchEngineNotifier.php`

**Métodos principales**:

```php
// Notificar a todos los proveedores
$notifier = new SearchEngineNotifier();
$result = $notifier->notifyAll($sitemapUrl, $urls);

// Notificar solo a proveedores seleccionados
$result = $notifier->notifySelected($sitemapUrl, $urls, ['google', 'indexnow']);
```

**Estructura de respuesta**:
```json
{
  "success": true,
  "providers_total": 3,
  "providers_success": 2,
  "providers_failed": 1,
  "execution_time": "1.45s",
  "urls_count": 150,
  "results": {
    "google": {
      "success": true,
      "provider": "Google Search Console",
      "message": "Sitemap enviado correctamente"
    },
    "bing": {
      "success": true,
      "provider": "Bing Webmaster Tools",
      "message": "10000 URLs notificadas en 1 batch"
    },
    "indexnow": {
      "success": false,
      "provider": "IndexNow",
      "message": "Error: Invalid API key"
    }
  }
}
```

---

#### 4. Endpoint AJAX
**Archivo**: `admin/api/notify-search-engines.php`

**Método**: `POST`  
**Autenticación**: Sesión admin requerida  
**Parámetros**: Ninguno (usa sitemap existente)

**Flujo de ejecución**:
1. Verifica autenticación admin
2. Comprueba existencia de `sitemap.xml`
3. Extrae URLs del sitemap
4. Invoca `SearchEngineNotifier::notifyAll()`
5. Retorna resultado JSON

**Ejemplo uso desde JavaScript**:
```javascript
fetch('../api/notify-search-engines.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    }
})
.then(response => response.json())
.then(result => {
    console.log(result);
});
```

---

#### 5. Integración UI
**Archivo**: `admin/pages/sitemap-manager.php`

**Cambios realizados**:

1. **Botón de notificación** actualizado:
```javascript
notifyBtn.addEventListener('click', function() {
    if (confirm('¿Enviar sitemap.xml actual a todos los buscadores (Google, Bing, IndexNow)?')) {
        // Llama al nuevo endpoint
        fetch('../api/notify-search-engines.php', { method: 'POST' })
            .then(response => response.json())
            .then(result => {
                showNewNotificationResult(result);
            });
    }
});
```

2. **Nueva función de visualización**:
```javascript
function showNewNotificationResult(result) {
    // Muestra resultados de los 3 proveedores con iconos y badges
    // Estadísticas: éxitos/fallos/URLs enviadas
    // Información detallada por proveedor
}
```

**Interfaz visual**:
- ✅ Icono verde para proveedores exitosos
- ❌ Icono rojo para proveedores fallidos
- Badges con estado (Éxito/Error)
- Contador de URLs enviadas
- Tiempo de ejecución total

---

#### 6. Extensión SitemapGenerator
**Archivo**: `admin/classes/SitemapGenerator.php`

**Nuevo método añadido**:
```php
public function extractUrlsFromSitemap($sitemapPath) {
    // Lee sitemap.xml
    // Extrae todas las URLs de etiquetas <loc>
    // Retorna array de URLs
}
```

**Uso**:
```php
$generator = new SitemapGenerator();
$urls = $generator->extractUrlsFromSitemap(__DIR__ . '/../../sitemap.xml');
// ['https://ejemplo.com/', 'https://ejemplo.com/about', ...]
```

---

## 🔐 Seguridad y Credenciales

### Archivos de Configuración Sensibles

#### .gitignore actualizado
```gitignore
# Credenciales de APIs de búsqueda
admin/config/bing-api-key.txt
admin/config/indexnow-key.txt
admin/config/service-account-credentials.json

# Archivos de verificación pública IndexNow
/*.txt
!robots.txt
```

### Permisos Recomendados
```bash
chmod 600 admin/config/indexnow-key.txt
chmod 600 admin/config/bing-api-key.txt
chmod 600 admin/config/service-account-credentials.json
```

---

## 🚀 Guía de Configuración Inicial

### Paso 1: Configurar IndexNow

1. **Ejecutar script de setup** (UNA VEZ):
   ```
   http://tusitio.com/setup-indexnow.php
   ```

2. **Verificar archivos creados**:
   - ✅ `admin/config/indexnow-key.txt` (privado)
   - ✅ `/{key}.txt` (público en raíz)

3. **Probar accesibilidad**:
   ```
   http://tusitio.com/{key}.txt
   ```
   Debe retornar exactamente el contenido de la clave.

4. **ELIMINAR** `setup-indexnow.php` del servidor por seguridad.

---

### Paso 2: Configurar Bing Webmaster Tools

1. Acceder a: https://www.bing.com/webmasters
2. Añadir tu sitio web
3. Verificar propiedad (DNS, archivo HTML, o meta tag)
4. Ir a: **Settings → API Access**
5. Generar **API Key**
6. Guardar en: `admin/config/bing-api-key.txt`

---

### Paso 3: Configurar Google Search Console

#### 3.1 Crear Service Account

1. Acceder a: https://console.cloud.google.com
2. Crear nuevo proyecto (o usar existente)
3. Habilitar **Google Search Console API**
4. Ir a: **IAM & Admin → Service Accounts → Create Service Account**
5. Nombre: `search-console-notifier`
6. Rol: Sin roles necesarios (se dan permisos desde Search Console)
7. Crear **Key** (tipo JSON)
8. Descargar JSON

#### 3.2 Configurar Permisos

1. Acceder a: https://search.google.com/search-console
2. Añadir propiedad: **sc-domain:tusitio.com**
3. Verificar propiedad (DNS TXT record)
4. Ir a: **Settings → Users and permissions**
5. Añadir usuario: `email-del-service-account@proyecto.iam.gserviceaccount.com`
6. Rol: **Owner** (propietario)

#### 3.3 Instalar Dependencias

```bash
cd admin/classes/Providers
composer require google/apiclient:"^2.0"
```

#### 3.4 Guardar Credenciales

Copiar JSON descargado a:
```
admin/config/service-account-credentials.json
```

**Estructura JSON**:
```json
{
  "type": "service_account",
  "project_id": "proyecto-123456",
  "private_key_id": "abc123...",
  "private_key": "-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----\n",
  "client_email": "search-console-notifier@proyecto.iam.gserviceaccount.com",
  "client_id": "1234567890",
  "auth_uri": "https://accounts.google.com/o/oauth2/auth",
  "token_uri": "https://oauth2.googleapis.com/token"
}
```

---

## 📊 Logs y Debugging

### Ubicación de Logs
```
logs/search-engines/
├── IndexNow_2024-01-15.log
├── Bing_2024-01-15.log
└── Google_2024-01-15.log
```

### Formato de Log
```
[2024-01-15 14:30:45] Iniciando notificación de 150 URLs
[2024-01-15 14:30:45] Total batches necesarios: 1
[2024-01-15 14:30:46] Batch 1 enviado correctamente - HTTP 200
[2024-01-15 14:30:46] Notificación completada exitosamente
```

### Contexto de Logs
```json
{
  "total_urls": 150,
  "batches": 1,
  "response_code": 200,
  "execution_time": "1.2s"
}
```

---

## 🧪 Testing

### Pruebas Manuales

#### Test 1: Verificar Proveedores Individualmente
```php
// admin/test-search-engines.php
require_once 'config/auth.php';
require_once 'classes/SearchEngineLogger.php';
require_once 'classes/Providers/IndexNowProvider.php';

$logger = new SearchEngineLogger('IndexNow');
$provider = new IndexNowProvider($logger);

$result = $provider->notifyUrls('https://tusitio.com/sitemap.xml', [
    'https://tusitio.com/',
    'https://tusitio.com/about'
]);

var_dump($result);
```

#### Test 2: Verificar Orquestador
```php
require_once 'classes/SearchEngineNotifier.php';

$notifier = new SearchEngineNotifier();
$result = $notifier->notifyAll('https://tusitio.com/sitemap.xml', [
    'https://tusitio.com/',
    'https://tusitio.com/contact'
]);

print_r($result);
```

#### Test 3: Endpoint AJAX
```bash
curl -X POST http://tusitio.com/admin/api/notify-search-engines.php \
     -H "Cookie: PHPSESSID=..." \
     -H "Content-Type: application/json"
```

---

## ⚠️ Manejo de Errores

### Errores Comunes

#### IndexNow
| Error | Causa | Solución |
|-------|-------|----------|
| HTTP 403 | Archivo de verificación inaccesible | Verificar `/{key}.txt` accesible públicamente |
| HTTP 400 | Formato JSON inválido | Revisar estructura del payload |
| HTTP 429 | Rate limit excedido | Esperar 1 minuto entre requests |

#### Bing Webmaster
| Error | Causa | Solución |
|-------|-------|----------|
| Invalid API key | Key incorrecta o revocada | Generar nueva key desde panel Bing |
| siteName not verified | Sitio no verificado en Webmaster | Verificar propiedad en Bing |
| Quota exceeded | Límite diario alcanzado | Esperar 24 horas |

#### Google Search Console
| Error | Causa | Solución |
|-------|-------|----------|
| 403 Forbidden | Service Account sin permisos | Añadir como Owner en Search Console |
| 404 Not Found | Propiedad no existe | Verificar formato `sc-domain:tusitio.com` |
| Invalid credentials | JSON credenciales inválido | Re-descargar desde Cloud Console |

---

## 📈 Métricas y Rendimiento

### Límites de API

| Proveedor | Límite por Request | Límite Diario | Rate Limit |
|-----------|-------------------|---------------|------------|
| IndexNow | 10,000 URLs | Ilimitado | ~1 req/min recomendado |
| Bing | 10,000 URLs | 100,000 URLs | No especificado |
| Google | 1 sitemap | 100 sitemaps | No especificado |

### Procesamiento por Lotes

El sistema divide automáticamente grandes conjuntos de URLs:

```php
// Ejemplo: 25,000 URLs se procesan en 3 batches
Batch 1: URLs 1-10,000
Batch 2: URLs 10,001-20,000  
Batch 3: URLs 20,001-25,000
```

**Tiempo estimado**: ~0.5-2 segundos por batch

---

## 🔄 Flujo Completo de Notificación

```mermaid
graph TD
    A[Usuario hace clic en botón] --> B[Confirmación JS]
    B --> C[POST a notify-search-engines.php]
    C --> D{Autenticado?}
    D -->|No| E[Error 401]
    D -->|Sí| F{Existe sitemap?}
    F -->|No| G[Error 404]
    F -->|Sí| H[Extraer URLs]
    H --> I[SearchEngineNotifier::notifyAll]
    I --> J[NotificarGoogle]
    I --> K[Notificar Bing]
    I --> L[Notificar IndexNow]
    J --> M[Consolidar resultados]
    K --> M
    L --> M
    M --> N[Retornar JSON]
    N --> O[showNewNotificationResult]
    O --> P[Mostrar resultados en UI]
```

---

## 📚 Documentación de Referencia

### APIs Oficiales

- **IndexNow**: https://www.indexnow.org/documentation
- **Bing Webmaster Tools**: https://docs.microsoft.com/en-us/bingwebmaster/
- **Google Search Console**: https://developers.google.com/webmaster-tools/v1/sitemaps

### Librerías Usadas

- **google/apiclient**: https://github.com/googleapis/google-api-php-client

---

## ✅ Checklist de Implementación Completada

- [x] Crear `SearchEngineLogger` con rotación diaria
- [x] Implementar `IndexNowProvider` con batch processing
- [x] Implementar `BingWebmasterProvider` con batch processing
- [x] Implementar `GoogleSearchConsoleProvider` con OAuth 2.0
- [x] Crear `SearchEngineNotifier` orquestador
- [x] Crear endpoint `admin/api/notify-search-engines.php`
- [x] Extender `SitemapGenerator` con `extractUrlsFromSitemap()`
- [x] Actualizar JavaScript en `sitemap-manager.php`
- [x] Crear función `showNewNotificationResult()` UI
- [x] Actualizar `.gitignore` para proteger credenciales
- [x] Crear script `setup-indexnow.php` para configuración inicial
- [x] Documentar sistema completo

---

## 🎯 Próximos Pasos Recomendados

1. **Ejecutar** `setup-indexnow.php` y configurar credenciales
2. **Probar** notificación manual desde `sitemap-manager.php`
3. **Revisar** logs en `logs/search-engines/` para debugging
4. **Automatizar** notificaciones (opcional):
   - Cron job después de regenerar sitemap
   - Webhook al publicar nuevo contenido
5. **Monitorear** métricas en:
   - Google Search Console → Sitemaps
   - Bing Webmaster Tools → Sitemaps
6. **Eliminar** `setup-indexnow.php` tras configuración inicial

---

## 🆘 Soporte y Troubleshooting

### Debugging Paso a Paso

1. **Verificar credenciales**:
   ```bash
   ls -la admin/config/
   # Debe mostrar archivos con permisos 600
   ```

2. **Revisar logs**:
   ```bash
   tail -f logs/search-engines/IndexNow_$(date +%Y-%m-%d).log
   ```

3. **Probar conectividad**:
   ```bash
   curl -I https://api.indexnow.org
   curl -I https://ssl.bing.com/webmaster/api.svc
   ```

4. **Validar JSON**:
   ```bash
   php -r "json_decode(file_get_contents('admin/config/service-account-credentials.json'));"
   ```

### Contacto

Para problemas específicos:
- **IndexNow**: https://www.indexnow.org/faq
- **Bing**: https://www.bing.com/webmasters/help
- **Google**: https://support.google.com/webmasters

---

**Documento generado**: <?php echo date('Y-m-d H:i:s'); ?>  
**Última actualización**: <?php echo date('Y-m-d H:i:s'); ?>
