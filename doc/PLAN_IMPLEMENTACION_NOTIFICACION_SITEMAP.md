# 🚀 Plan de Implementación: Sistema de Notificación de Sitemap a Buscadores

**Fecha:** 9 de abril de 2026  
**Proyecto:** Portfolio JCMS  
**Objetivo:** Implementar sistema completo de notificación automática de sitemap a Google, Bing y motores IndexNow

---

## 📋 Resumen Ejecutivo

**Sistema a implementar:**
- ✅ **IndexNow API** → Bing + Yandex + Naver + Seznam
- ✅ **Bing Webmaster Tools API** → Bing (tracking oficial)
- ✅ **Google Search Console API** → Google (indexación oficial)

**Tiempo estimado total:** 8-10 horas  
**Complejidad:** Media-Alta  
**ROI SEO:** Muy Alto

---

## 🏗️ Arquitectura del Sistema

### Estructura de Archivos (Nueva)

```
Portfolio/
├── admin/
│   ├── classes/
│   │   ├── SitemapGenerator.php (EXTENDER)
│   │   ├── SearchEngineNotifier.php (NUEVO - Orquestador)
│   │   └── Providers/ (NUEVO)
│   │       ├── IndexNowProvider.php
│   │       ├── BingWebmasterProvider.php
│   │       └── GoogleSearchConsoleProvider.php
│   │
│   ├── config/
│   │   ├── bing-api-key.txt (NUEVO - credencial)
│   │   ├── indexnow-key.txt (NUEVO - credencial)
│   │   └── service-account-credentials.json (NUEVO - credencial)
│   │
│   ├── api/
│   │   └── notify-search-engines.php (NUEVO - endpoint AJAX)
│   │
│   └── pages/
│       └── sitemap-manager.php (MODIFICAR - UI mejorada)
│
├── setup-indexnow.php (NUEVO - script de configuración temporal)
├── {clave-indexnow}.txt (GENERADO - verificación pública)
├── composer.json (MODIFICAR - agregar google/apiclient)
└── .gitignore (MODIFICAR - proteger credenciales)
```

### Flujo de Datos

```
Usuario Admin Panel
       ↓
[Generar Sitemap] → SitemapGenerator.php
       ↓
[Notificar Buscadores] → SearchEngineNotifier.php
       ↓
    ┌──────┴──────┬──────────────┬────────────────┐
    ↓             ↓              ↓                ↓
IndexNow    BingWebmaster  GoogleConsole    (logs)
Provider       Provider       Provider
    ↓             ↓              ↓
  APIs         APIs           APIs
```

---

## 📦 FASE 1: Preparación del Entorno

### 1.1 Actualizar Composer (Google Client Library)

**Archivo:** `composer.json`

```json
{
  "require": {
    "google/apiclient": "^2.15"
  }
}
```

**Ejecutar:**
```bash
composer require google/apiclient
```

**Validar:**
```bash
composer show google/apiclient
```

### 1.2 Actualizar .gitignore

**Archivo:** `.gitignore`

```gitignore
# Credenciales APIs (NO VERSIONAR)
admin/config/bing-api-key.txt
admin/config/indexnow-key.txt
admin/config/service-account-credentials.json

# Archivo verificación IndexNow (público pero autogenerado)
/*.txt
!robots.txt
!sitemap.xml

# Logs específicos
admin/classes/sitemap_generator.log
logs/search-engines/
```

### 1.3 Crear Directorios

**Ejecutar:**
```bash
mkdir -p admin/classes/Providers
mkdir -p logs/search-engines
chmod 755 logs/search-engines
```

---

## 🔧 FASE 2: Implementación de IndexNow (Prioridad 1)

**Tiempo estimado:** 1-2 horas  
**Complejidad:** Baja  
**Beneficio:** Alto (cubre 4+ buscadores sin configuración compleja)

### 2.1 Crear IndexNowProvider.php

**Archivo:** `admin/classes/Providers/IndexNowProvider.php`

```php
<?php
/**
 * IndexNowProvider
 * Notifica URLs a múltiples buscadores simultáneamente
 * Compatible: Bing, Yandex, Naver, Seznam.cz
 */

class IndexNowProvider
{
    private $keyPath;
    private $webRoot;
    private $baseUrl;
    private $logger;
    
    public function __construct($baseUrl, $webRoot = null)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->webRoot = $webRoot ?? $_SERVER['DOCUMENT_ROOT'];
        $this->keyPath = dirname(__DIR__) . '/config/indexnow-key.txt';
        $this->logger = new SearchEngineLogger('indexnow');
    }
    
    /**
     * Notifica lista de URLs a IndexNow
     * @param array $urls Lista de URLs completas
     * @return array Resultado de la operación
     */
    public function notifyUrls($urls)
    {
        try {
            // 1. Verificar que existe la clave
            if (!file_exists($this->keyPath)) {
                throw new Exception('IndexNow key not found. Run setup-indexnow.php first.');
            }
            
            $key = trim(file_get_contents($this->keyPath));
            
            // 2. Verificar archivo de verificación
            $keyFile = $this->webRoot . '/' . $key . '.txt';
            if (!file_exists($keyFile)) {
                throw new Exception('IndexNow verification file not found: ' . $keyFile);
            }
            
            // 3. Preparar datos (máximo 10,000 URLs por llamada)
            $chunks = array_chunk($urls, 10000);
            $results = [];
            
            foreach ($chunks as $index => $chunk) {
                $batchNum = $index + 1;
                $this->logger->log("Sending batch {$batchNum} with " . count($chunk) . " URLs");
                
                $result = $this->sendBatch($chunk, $key);
                $results[] = $result;
                
                // Pausa entre lotes
                if ($batchNum < count($chunks)) {
                    usleep(500000); // 0.5 segundos
                }
            }
            
            // 4. Consolidar resultados
            $successCount = count(array_filter($results, fn($r) => $r['success']));
            
            return [
                'success' => $successCount > 0,
                'provider' => 'IndexNow',
                'batches_sent' => count($results),
                'batches_success' => $successCount,
                'total_urls' => count($urls),
                'message' => $successCount > 0 
                    ? "✓ IndexNow: {$successCount} lotes enviados correctamente"
                    : "✗ IndexNow: Error en todos los lotes",
                'details' => $results
            ];
            
        } catch (Exception $e) {
            $this->logger->error('IndexNow Error: ' . $e->getMessage());
            return [
                'success' => false,
                'provider' => 'IndexNow',
                'message' => '✗ IndexNow: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Envía un lote de URLs a IndexNow API
     */
    private function sendBatch($urls, $key)
    {
        $host = parse_url($this->baseUrl, PHP_URL_HOST);
        
        $postData = json_encode([
            'host' => $host,
            'key' => $key,
            'urlList' => $urls
        ]);
        
        $ch = curl_init('https://api.indexnow.org/indexnow');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=utf-8'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        $this->logger->log("IndexNow Response: HTTP {$httpCode}");
        
        $success = in_array($httpCode, [200, 202]);
        
        return [
            'success' => $success,
            'http_code' => $httpCode,
            'response' => $response,
            'error' => $error
        ];
    }
}
```

### 2.2 Crear Script de Configuración IndexNow

**Archivo:** `setup-indexnow.php` (temporal, en root)

```php
<?php
/**
 * Setup IndexNow - Configuración automática
 * Ejecutar UNA VEZ: https://www.juancarlosmacias.es/setup-indexnow.php
 * Después BORRAR este archivo por seguridad
 */

$webRoot = __DIR__;
$configDir = $webRoot . '/admin/config';

// Generar clave aleatoria de 32 caracteres hexadecimales
$key = bin2hex(random_bytes(16));

echo "<h1>🔧 Configuración IndexNow</h1>";

// 1. Crear archivo de clave en config
$keyPath = $configDir . '/indexnow-key.txt';
if (file_put_contents($keyPath, $key)) {
    echo "<p>✅ Clave generada y guardada en: <code>{$keyPath}</code></p>";
} else {
    die("<p>❌ Error al guardar clave en {$keyPath}</p>");
}

// 2. Crear archivo de verificación público
$verificationFile = $webRoot . '/' . $key . '.txt';
if (file_put_contents($verificationFile, $key)) {
    echo "<p>✅ Archivo de verificación creado: <code>{$key}.txt</code></p>";
} else {
    die("<p>❌ Error al crear archivo de verificación</p>");
}

// 3. Verificar accesibilidad
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") 
           . "://{$_SERVER['HTTP_HOST']}";
$verificationUrl = $baseUrl . '/' . $key . '.txt';

echo "<h2>🧪 Verificación</h2>";
echo "<p>Verificando acceso público a: <a href='{$verificationUrl}' target='_blank'>{$verificationUrl}</a></p>";

$content = @file_get_contents($verificationUrl);
if ($content === $key) {
    echo "<p>✅ Archivo de verificación accesible públicamente</p>";
} else {
    echo "<p>⚠️ No se pudo verificar el acceso público. Comprueba permisos del archivo.</p>";
}

echo "<h2>✅ Configuración Completada</h2>";
echo "<p><strong>IMPORTANTE:</strong> Por seguridad, borra este archivo ahora:</p>";
echo "<pre>rm setup-indexnow.php</pre>";
echo "<p>Clave generada: <code>{$key}</code></p>";
?>
```

**Instrucciones de uso:**
1. Acceder a: `https://www.juancarlosmacias.es/setup-indexnow.php`
2. Verificar que se crean los archivos
3. **Borrar** `setup-indexnow.php`

---

## 🔧 FASE 3: Implementación de Bing Webmaster API (Prioridad 2)

**Tiempo estimado:** 2-3 horas  
**Complejidad:** Media  
**Beneficio:** Medio (tracking oficial de Bing)

### 3.1 Obtener API Key de Bing

**Pasos manuales (15 minutos):**

1. Ir a: https://www.bing.com/webmasters
2. Iniciar sesión con cuenta Microsoft
3. Agregar sitio: `https://www.juancarlosmacias.es`
4. Verificar propiedad (archivo XML o meta tag)
5. Ir a: **Settings** → **API Access**
6. Click en **"Generate API Key"**
7. Copiar la clave

### 3.2 Guardar API Key

**Crear archivo:** `admin/config/bing-api-key.txt`

```
TU_API_KEY_AQUI_SIN_ESPACIOS
```

**Permisos:**
```bash
chmod 600 admin/config/bing-api-key.txt
```

### 3.3 Crear BingWebmasterProvider.php

**Archivo:** `admin/classes/Providers/BingWebmasterProvider.php`

```php
<?php
/**
 * BingWebmasterProvider
 * Envía URLs a Bing Webmaster Tools API
 */

class BingWebmasterProvider
{
    private $apiKeyPath;
    private $baseUrl;
    private $logger;
    
    public function __construct($baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKeyPath = dirname(__DIR__) . '/config/bing-api-key.txt';
        $this->logger = new SearchEngineLogger('bing');
    }
    
    /**
     * Notifica lista de URLs a Bing
     * @param array $urls Lista de URLs completas
     * @return array Resultado de la operación
     */
    public function notifyUrls($urls)
    {
        try {
            // 1. Verificar API Key
            if (!file_exists($this->apiKeyPath)) {
                throw new Exception('Bing API Key not found. Create: admin/config/bing-api-key.txt');
            }
            
            $apiKey = trim(file_get_contents($this->apiKeyPath));
            
            if (empty($apiKey)) {
                throw new Exception('Bing API Key is empty');
            }
            
            // 2. Dividir en lotes (máximo 10,000 URLs)
            $chunks = array_chunk($urls, 10000);
            $results = [];
            
            foreach ($chunks as $index => $chunk) {
                $batchNum = $index + 1;
                $this->logger->log("Sending batch {$batchNum} with " . count($chunk) . " URLs");
                
                $result = $this->sendBatch($chunk, $apiKey);
                $results[] = $result;
                
                // Pausa entre lotes
                if ($batchNum < count($chunks)) {
                    usleep(500000);
                }
            }
            
            // 3. Consolidar resultados
            $successCount = count(array_filter($results, fn($r) => $r['success']));
            
            return [
                'success' => $successCount > 0,
                'provider' => 'Bing Webmaster',
                'batches_sent' => count($results),
                'batches_success' => $successCount,
                'total_urls' => count($urls),
                'message' => $successCount > 0
                    ? "✓ Bing: {$successCount} lotes enviados correctamente"
                    : "✗ Bing: Error en todos los lotes",
                'details' => $results
            ];
            
        } catch (Exception $e) {
            $this->logger->error('Bing Error: ' . $e->getMessage());
            return [
                'success' => false,
                'provider' => 'Bing Webmaster',
                'message' => '✗ Bing: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Envía un lote a Bing API
     */
    private function sendBatch($urls, $apiKey)
    {
        $endpoint = 'https://ssl.bing.com/webmaster/api.svc/json/SubmitUrlbatch?apikey=' 
                    . urlencode($apiKey);
        
        $requestBody = json_encode([
            'siteUrl' => $this->baseUrl,
            'urlList' => $urls
        ]);
        
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $requestBody,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=utf-8'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        $this->logger->log("Bing Response: HTTP {$httpCode} - {$response}");
        
        $success = ($httpCode === 200);
        
        return [
            'success' => $success,
            'http_code' => $httpCode,
            'response' => $response,
            'error' => $error
        ];
    }
}
```

---

## 🔧 FASE 4: Implementación de Google Search Console API (Prioridad 3)

**Tiempo estimado:** 4-5 horas  
**Complejidad:** Alta  
**Beneficio:** Alto (indexación oficial de Google)

### 4.1 Configurar Google Cloud Project

**Pasos manuales (30 minutos):**

#### Paso 1: Crear Proyecto en Google Cloud

1. Ir a: https://console.cloud.google.com
2. Click en **"New Project"**
3. Nombre: `Portfolio JCMS SEO`
4. Click **"Create"**

#### Paso 2: Habilitar Search Console API

1. En el proyecto, ir a **APIs & Services** → **Library**
2. Buscar: `Search Console API`
3. Click en **"Google Search Console API"**
4. Click **"Enable"**

#### Paso 3: Crear Service Account

1. Ir a **APIs & Services** → **Credentials**
2. Click **"Create Credentials"** → **Service Account**
3. Detalles:
   - **Name:** `sitemap-notifier`
   - **ID:** `sitemap-notifier` (auto-generado)
   - **Description:** `Servicio para notificar sitemaps automáticamente`
4. Click **"Create and Continue"**
5. **Grant this service account access:**
   - Role: `Owner` (o `Service Account User`)
6. Click **"Done"**

#### Paso 4: Generar Credenciales JSON

1. En la lista de Service Accounts, click en el email del Service Account creado
2. Ir a la pestaña **"Keys"**
3. Click **"Add Key"** → **"Create new key"**
4. Formato: **JSON**
5. Click **"Create"**
6. Se descargará automáticamente: `portfolio-jcms-seo-xxxxxx.json`

#### Paso 5: Agregar Service Account a Search Console

1. Ir a: https://search.google.com/search-console
2. Click **"Add property"**
3. Seleccionar **"Domain"**
4. Ingresar: `juancarlosmacias.es`
5. Verificar propiedad (DNS TXT record)
6. Una vez verificado, ir a **Settings** → **Users and permissions**
7. Click **"Add user"**
8. Email: `sitemap-notifier@portfolio-jcms-seo.iam.gserviceaccount.com` (del JSON)
9. Permission: **Owner**
10. Click **"Add"**

### 4.2 Instalar Credenciales

**Copiar archivo JSON descargado:**

```bash
# Renombrar a:
cp ~/Downloads/portfolio-jcms-seo-xxxxxx.json admin/config/service-account-credentials.json

# Permisos:
chmod 600 admin/config/service-account-credentials.json
```

### 4.3 Crear GoogleSearchConsoleProvider.php

**Archivo:** `admin/classes/Providers/GoogleSearchConsoleProvider.php`

```php
<?php
/**
 * GoogleSearchConsoleProvider
 * Notifica sitemap a Google Search Console usando Service Account
 */

class GoogleSearchConsoleProvider
{
    private $credentialsPath;
    private $baseUrl;
    private $logger;
    
    public function __construct($baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->credentialsPath = dirname(__DIR__) . '/config/service-account-credentials.json';
        $this->logger = new SearchEngineLogger('google');
    }
    
    /**
     * Notifica sitemap a Google Search Console
     * @param array $urls Opcional - no usado por Google (envía sitemap completo)
     * @return array Resultado de la operación
     */
    public function notifyUrls($urls = [])
    {
        try {
            // 1. Verificar credenciales
            if (!file_exists($this->credentialsPath)) {
                throw new Exception('Google Service Account credentials not found');
            }
            
            // 2. Verificar librería Google Client
            $autoloadPath = dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';
            if (!file_exists($autoloadPath)) {
                throw new Exception('Google Client Library not installed. Run: composer require google/apiclient');
            }
            
            require_once $autoloadPath;
            
            // 3. Crear cliente Google
            $client = new Google\Client();
            $client->setAuthConfig($this->credentialsPath);
            $client->setScopes(['https://www.googleapis.com/auth/webmasters']);
            
            // 4. Autenticar (genera token automáticamente)
            $client->fetchAccessTokenWithAssertion();
            $token = $client->getAccessToken();
            
            if (!$token) {
                throw new Exception('Failed to generate access token');
            }
            
            $this->logger->log('Access token generated successfully');
            
            // 5. Crear servicio Webmasters
            $service = new Google\Service\Webmasters($client);
            
            // 6. Preparar URL del sitio (Domain Property)
            $domain = parse_url($this->baseUrl, PHP_URL_HOST);
            $siteUrl = 'sc-domain:' . $domain;
            $sitemapUrl = $this->baseUrl . '/sitemap.xml';
            
            $this->logger->log("Submitting sitemap: {$sitemapUrl} to {$siteUrl}");
            
            // 7. Enviar sitemap
            $service->sitemaps->submit($siteUrl, $sitemapUrl);
            
            $this->logger->log('Sitemap submitted successfully');
            
            return [
                'success' => true,
                'provider' => 'Google Search Console',
                'message' => '✓ Google: Sitemap enviado correctamente',
                'sitemap_url' => $sitemapUrl,
                'site_url' => $siteUrl
            ];
            
        } catch (Google\Service\Exception $e) {
            $errorMsg = 'Google API Error: ' . $e->getMessage();
            $this->logger->error($errorMsg);
            
            return [
                'success' => false,
                'provider' => 'Google Search Console',
                'message' => '✗ Google: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'http_code' => $e->getCode()
            ];
            
        } catch (Exception $e) {
            $this->logger->error('Google Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'provider' => 'Google Search Console',
                'message' => '✗ Google: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }
}
```

---

## 🎯 FASE 5: Orquestador Central

### 5.1 Crear SearchEngineLogger.php

**Archivo:** `admin/classes/SearchEngineLogger.php`

```php
<?php
/**
 * SearchEngineLogger
 * Logger específico para notificaciones a buscadores
 */

class SearchEngineLogger
{
    private $logFile;
    private $provider;
    
    public function __construct($provider)
    {
        $this->provider = $provider;
        $logDir = dirname(__DIR__) . '/../logs/search-engines';
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $this->logFile = $logDir . '/' . $provider . '_' . date('Y-m-d') . '.log';
    }
    
    public function log($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [{$this->provider}] {$message}" . PHP_EOL;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }
    
    public function error($message)
    {
        $this->log("ERROR: {$message}");
    }
}
```

### 5.2 Crear SearchEngineNotifier.php (Orquestador)

**Archivo:** `admin/classes/SearchEngineNotifier.php`

```php
<?php
/**
 * SearchEngineNotifier
 * Orquestador principal para notificar a todos los motores de búsqueda
 */

require_once __DIR__ . '/SearchEngineLogger.php';
require_once __DIR__ . '/Providers/IndexNowProvider.php';
require_once __DIR__ . '/Providers/BingWebmasterProvider.php';
require_once __DIR__ . '/Providers/GoogleSearchConsoleProvider.php';

class SearchEngineNotifier
{
    private $baseUrl;
    private $webRoot;
    private $logger;
    
    public function __construct($baseUrl, $webRoot = null)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->webRoot = $webRoot ?? $_SERVER['DOCUMENT_ROOT'];
        $this->logger = new SearchEngineLogger('notifier');
    }
    
    /**
     * Notifica a todos los motores de búsqueda disponibles
     * @param array $urls Lista de URLs a notificar
     * @return array Resultados de cada proveedor
     */
    public function notifyAll($urls)
    {
        $this->logger->log('=== STARTING NOTIFICATION PROCESS ===');
        $this->logger->log('Total URLs: ' . count($urls));
        
        $results = [];
        
        // 1. Google Search Console (envía sitemap completo)
        try {
            $google = new GoogleSearchConsoleProvider($this->baseUrl);
            $results['google'] = $google->notifyUrls();
        } catch (Exception $e) {
            $results['google'] = [
                'success' => false,
                'provider' => 'Google Search Console',
                'message' => '✗ Google: ' . $e->getMessage()
            ];
        }
        
        // 2. Bing Webmaster API
        try {
            $bing = new BingWebmasterProvider($this->baseUrl);
            $results['bing'] = $bing->notifyUrls($urls);
        } catch (Exception $e) {
            $results['bing'] = [
                'success' => false,
                'provider' => 'Bing Webmaster',
                'message' => '✗ Bing: ' . $e->getMessage()
            ];
        }
        
        // 3. IndexNow (múltiples buscadores)
        try {
            $indexNow = new IndexNowProvider($this->baseUrl, $this->webRoot);
            $results['indexnow'] = $indexNow->notifyUrls($urls);
        } catch (Exception $e) {
            $results['indexnow'] = [
                'success' => false,
                'provider' => 'IndexNow',
                'message' => '✗ IndexNow: ' . $e->getMessage()
            ];
        }
        
        // Resumen
        $successCount = count(array_filter($results, fn($r) => $r['success']));
        $this->logger->log("Notification complete: {$successCount}/" . count($results) . " providers succeeded");
        
        return [
            'success' => $successCount > 0,
            'providers_total' => count($results),
            'providers_success' => $successCount,
            'results' => $results
        ];
    }
}
```

---

## 🔌 FASE 6: Endpoint API Admin

### 6.1 Crear notify-search-engines.php

**Archivo:** `admin/api/notify-search-engines.php`

```php
<?php
/**
 * API Endpoint: Notificar Sitemap a Buscadores
 * Método: POST
 * Requiere: Autenticación admin
 */

define('ADMIN_ACCESS', true);
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../classes/SearchEngineNotifier.php';
require_once __DIR__ . '/../classes/SitemapGenerator.php';

header('Content-Type: application/json');

// Verificar autenticación
$auth = new AdminAuth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Detectar entorno
    $isLocal = strpos($_SERVER['HTTP_HOST'], 'localhost') !== false 
               || strpos($_SERVER['HTTP_HOST'], 'perfil.in') !== false;
    
    $baseUrl = $isLocal 
        ? 'http://www.perfil.in' 
        : 'https://www.juancarlosmacias.es';
    
    // 1. Obtener URLs del sitemap
    $generator = new SitemapGenerator($baseUrl);
    
    // Intentar leer sitemap existente
    $sitemapPath = $_SERVER['DOCUMENT_ROOT'] . '/sitemap.xml';
    
    if (!file_exists($sitemapPath)) {
        throw new Exception('Sitemap not found. Generate it first.');
    }
    
    // Extraer URLs del sitemap
    $urls = $generator->extractUrlsFromSitemap($sitemapPath);
    
    if (empty($urls)) {
        throw new Exception('No URLs found in sitemap');
    }
    
    // 2. Notificar a buscadores
    $notifier = new SearchEngineNotifier($baseUrl);
    $result = $notifier->notifyAll($urls);
    
    // 3. Respuesta
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
```

---

## 🎨 FASE 7: Actualizar UI del Admin

### 7.1 Extender SitemapGenerator.php

**Agregar método para extraer URLs:**

```php
// En admin/classes/SitemapGenerator.php

/**
 * Extrae todas las URLs de un sitemap existente
 * @param string $sitemapPath Ruta completa al sitemap.xml
 * @return array Lista de URLs
 */
public function extractUrlsFromSitemap($sitemapPath)
{
    $urls = [];
    
    if (!file_exists($sitemapPath)) {
        return $urls;
    }
    
    $xml = @simplexml_load_file($sitemapPath);
    
    if ($xml === false) {
        return $urls;
    }
    
    // Sitemap simple
    if (isset($xml->url)) {
        foreach ($xml->url as $urlEntry) {
            if (isset($urlEntry->loc)) {
                $urls[] = (string)$urlEntry->loc;
            }
        }
    }
    
    return $urls;
}
```

### 7.2 Actualizar JavaScript en sitemap-manager.php

**Agregar al final del archivo, antes de `</body>`:**

```javascript
<script>
// Botón de notificación
document.getElementById('notifyBtn').addEventListener('click', function() {
    if (!confirm('¿Notificar sitemap a Google, Bing y otros buscadores?')) {
        return;
    }
    
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="spinner-border spinner-border-sm me-2"></i>Notificando...';
    
    fetch('../api/notify-search-engines.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-megaphone me-2"></i>Notificar Buscadores';
        
        if (data.success) {
            showNotificationResults(data);
        } else {
            alert('Error: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-megaphone me-2"></i>Notificar Buscadores';
        alert('Error de conexión: ' + error);
    });
});

function showNotificationResults(data) {
    const results = data.results;
    let html = '<div class="alert alert-info"><h5>Resultados de Notificación</h5><ul>';
    
    for (const [key, result] of Object.entries(results)) {
        const icon = result.success ? '✅' : '❌';
        html += `<li>${icon} ${result.message}</li>`;
    }
    
    html += `</ul><p><strong>${data.providers_success}/${data.providers_total}</strong> proveedores notificados correctamente.</p></div>`;
    
    // Mostrar en el container de resultados
    const container = document.querySelector('.result-container');
    if (container) {
        container.innerHTML = html;
        container.style.display = 'block';
    } else {
        alert(html.replace(/<[^>]*>/g, ' '));
    }
}
</script>
```

---

## ✅ FASE 8: Testing y Validación

### 8.1 Checklist de Pruebas

**IndexNow:**
- [ ] Ejecutar `setup-indexnow.php` y verificar archivos creados
- [ ] Verificar acceso público a `{clave}.txt`
- [ ] Probar notificación desde admin panel
- [ ] Verificar logs en `logs/search-engines/indexnow_YYYY-MM-DD.log`

**Bing:**
- [ ] Crear archivo `admin/config/bing-api-key.txt` con API key válida
- [ ] Verificar sitio en Bing Webmaster Tools
- [ ] Probar notificación desde admin panel
- [ ] Verificar logs en `logs/search-engines/bing_YYYY-MM-DD.log`

**Google:**
- [ ] Instalar `composer require google/apiclient`
- [ ] Copiar `service-account-credentials.json` a `admin/config/`
- [ ] Agregar Service Account como Owner en Search Console
- [ ] Probar notificación desde admin panel
- [ ] Verificar logs en `logs/search-engines/google_YYYY-MM-DD.log`

**Integración:**
- [ ] Generar sitemap desde admin panel
- [ ] Click en "Notificar Buscadores"
- [ ] Verificar que aparecen resultados de los 3 proveedores
- [ ] Verificar en Search Console de Google que el sitemap fue recibido
- [ ] Verificar en Bing Webmaster Tools que las URLs fueron recibidas

### 8.2 Scripts de Testing

**Crear:** `admin/test-search-engines.php` (temporal)

```php
<?php
require_once 'classes/SearchEngineNotifier.php';

$baseUrl = 'https://www.juancarlosmacias.es';
$testUrls = [
    $baseUrl,
    $baseUrl . '/about',
    $baseUrl . '/project'
];

echo "<h1>Testing Search Engine Notifier</h1>";

$notifier = new SearchEngineNotifier($baseUrl);
$results = $notifier->notifyAll($testUrls);

echo "<pre>";
print_r($results);
echo "</pre>";
```

---

## 📊 Resumen de Tiempos

| Fase | Tarea | Tiempo Estimado |
|------|-------|----------------|
| 1 | Preparación entorno | 30 min |
| 2 | IndexNow implementación | 1-2 horas |
| 3 | Bing implementación | 2-3 horas |
| 4 | Google implementación | 4-5 horas |
| 5 | Orquestador | 1 hora |
| 6 | Endpoint API | 30 min |
| 7 | UI actualización | 1 hora |
| 8 | Testing | 1-2 horas |
| **TOTAL** | | **10-15 horas** |

---

## 🚦 Orden de Implementación Recomendado

1. **Día 1 (3-4 horas):**
   - ✅ Fase 1: Preparación
   - ✅ Fase 2: IndexNow completo
   - ✅ Testing IndexNow

2. **Día 2 (3-4 horas):**
   - ✅ Fase 3: Bing Webmaster API completo
   - ✅ Fase 5: Logger + Orquestador
   - ✅ Testing Bing

3. **Día 3 (4-5 horas):**
   - ✅ Fase 4: Google Search Console API
   - ✅ Testing Google
   - ✅ Testing integrado

4. **Día 4 (2 horas):**
   - ✅ Fase 6: Endpoint API
   - ✅ Fase 7: UI actualización
   - ✅ Testing final E2E

---

## 📝 Checklist Final

### Archivos Creados
- [ ] `admin/classes/SearchEngineLogger.php`
- [ ] `admin/classes/SearchEngineNotifier.php`
- [ ] `admin/classes/Providers/IndexNowProvider.php`
- [ ] `admin/classes/Providers/BingWebmasterProvider.php`
- [ ] `admin/classes/Providers/GoogleSearchConsoleProvider.php`
- [ ] `admin/api/notify-search-engines.php`
- [ ] `setup-indexnow.php` (temporal)
- [ ] `logs/search-engines/` (directorio)

### Archivos Modificados
- [ ] `admin/classes/SitemapGenerator.php` (agregar `extractUrlsFromSitemap()`)
- [ ] `admin/pages/sitemap-manager.php` (agregar JavaScript)
- [ ] `composer.json` (agregar `google/apiclient`)
- [ ] `.gitignore` (proteger credenciales)

### Archivos de Configuración
- [ ] `admin/config/indexnow-key.txt`
- [ ] `admin/config/bing-api-key.txt`
- [ ] `admin/config/service-account-credentials.json`
- [ ] `{clave}.txt` (root, público)

### Dependencias
- [ ] `composer require google/apiclient`

---

## 🔐 Seguridad

**Variables de entorno alternativas (opcional):**

Si prefieres usar variables de entorno en lugar de archivos:

```php
// En config.local.php
function get_search_engine_config() {
    return [
        'indexnow_key' => getenv('INDEXNOW_KEY') ?: null,
        'bing_api_key' => getenv('BING_API_KEY') ?: null,
        'google_credentials_path' => getenv('GOOGLE_CREDENTIALS_PATH') ?: null
    ];
}
```

---

## 📚 Documentación de Referencia

- **Google Search Console API:** https://developers.google.com/webmaster-tools/v1/api_reference_index
- **Bing Webmaster API:** https://learn.microsoft.com/en-us/bingwebmaster/api/url-submission-api
- **IndexNow Protocol:** https://www.indexnow.org/documentation

---

**Fecha de creación:** 9 de abril de 2026  
**Autor:** Sistema Portfolio JCMS  
**Versión:** 1.0
