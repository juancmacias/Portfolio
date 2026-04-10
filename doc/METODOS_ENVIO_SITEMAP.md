# 📡 Métodos de Envío de Sitemap a Buscadores

## 📋 Resumen Ejecutivo

Este documento detalla los **3 métodos implementados** para enviar automáticamente los sitemaps a los principales motores de búsqueda.

**Sistema implementado en:** `web_mejora/_admin/tools/sitemap_helper.php`

**Cobertura total:**
- ✅ **Google** (vía Search Console API)
- ✅ **Bing** (vía Webmaster Tools API)
- ✅ **Yandex** (vía IndexNow)
- ✅ **Otros motores** compatibles con IndexNow (Naver, Seznam, etc.)

---

## 🎯 Flujo de Envío Actual

Cuando se genera un sitemap desde el panel de administración (`_admin/tools/sitemap_generator.php`):

```
1. Generar Sitemap (sitemap.xml + sitemaps por carpeta)
   ↓
2. Click en "Notificar Buscadores"
   ↓
3. Extracción automática de URLs de todos los sitemaps
   ↓
4. Envío paralelo a:
   - Google Search Console API (Service Account)
   - Bing Webmaster Tools API (SubmitUrlbatch)
   - IndexNow API (Bing + Yandex + otros)
```

**Nota:** Se extraen TODAS las URLs de todos los sitemaps y se envían a cada motor de búsqueda.

---

## 1️⃣ Google Search Console (Método Principal)

### 📌 Descripción
Envío mediante **Service Account con OAuth 2.0** usando la librería oficial `google/apiclient`.

### 🔑 Autenticación
**Service Account** (recomendado por Google para automatización):
- Archivo JSON con credenciales
- Generación automática de tokens JWT
- Sin intervención manual

### 📁 Archivos de Configuración

```
web_mejora/
├── _admin/config/
│   └── service-account-credentials.json   ← Credenciales
├── vendor/
│   └── autoload.php                       ← Google Client Library
```

### 🛠️ Implementación Actual

**Ubicación:** `sitemap_helper.php → pingUsingServiceAccount()`

```php
// 1. Cargar Google Client Library
require_once($autoloadPath);

// 2. Crear cliente
$client = new Google\Client();

// 3. Configurar Service Account
$client->setAuthConfig($credentialsPath);
$client->setScopes(['https://www.googleapis.com/auth/webmasters']);

// 4. Generar token automáticamente 
$client->fetchAccessTokenWithAssertion();

// 5. Crear servicio de Webmasters (Search Console)
$service = new Google\Service\Webmasters($client);

// 6. Enviar sitemap
$siteUrl = 'sc-domain:donde-reparar.com';  // Domain Property
$feedpath = 'https://www.donde-reparar.com/sitemap.xml';
$service->sitemaps->submit($siteUrl, $feedpath);
```

### ⚙️ Configuración Requerida

**Antes de usar (solo una vez):**

1. **Crear Service Account en Google Cloud:**
   - Ir a: https://console.cloud.google.com
   - Crear proyecto
   - Activar API de Search Console
   - Crear Service Account
   - Descargar archivo JSON de credenciales

2. **Agregar Service Account a Search Console:**
   - Ir a: https://search.google.com/search-console
   - Agregar sitio como Domain Property: `sc-domain:donde-reparar.com`
   - Agregar el email del Service Account como **Owner**

3. **Copiar archivo de credenciales:**
   ```bash
   # Copiar a:
   web_mejora/_admin/config/service-account-credentials.json
   ```

4. **Instalar Google Client Library:**
   ```bash
   cd web_mejora
   composer require google/apiclient
   ```

**Ver documentación completa:** `web_mejora/GOOGLE_SETUP.md`

### ✅ Respuesta Exitosa

```
HTTP 200
✓ Sitemap enviado correctamente a Google Search Console usando Service Account
```

### ❌ Errores Comunes

| Código | Error | Solución |
|--------|-------|----------|
| 401 | Token inválido o expirado | Verificar credenciales del Service Account |
| 403 | Acceso denegado | Agregar Service Account como Owner en Search Console |
| 404 | Propiedad no encontrada | Verificar que el sitio esté agregado como Domain Property |
| - | Archivo de credenciales no encontrado | Copiar JSON a `_admin/config/service-account-credentials.json` |
| - | Google Client Library no encontrada | Ejecutar: `composer require google/apiclient` |

### 🧪 Testing

**Archivo de prueba:** `web_mejora/test_sitemap_envio.php`

```bash
# Acceder a:
https://www.donde-reparar.com/test_sitemap_envio.php
```

**Resultado esperado:**
```
✅ Token generado (longitud: XXX caracteres)
✅ Servicio de Google Search Console creado
✅ ¡ÉXITO! Sitemap enviado correctamente a Google Search Console
```

---

## 2️⃣ Bing Webmaster Tools API

### 📌 Descripción
Envío mediante **API Key** usando el endpoint `SubmitUrlbatch` (POST con JSON).

### 🔑 Autenticación
**API Key simple:**
- Una clave alfanumérica
- Sin expiración (mientras no se regenere)
- Fácil de configurar

### 📁 Archivos de Configuración

```
web_mejora/
└── _admin/config/
    └── bing-api-key.txt   ← API Key de Bing
```

### 🛠️ Implementación Actual

**Ubicación:** `sitemap_helper.php → pingBingWebmasterAPI()`

```php
// 1. Leer API Key
$apiKeyPath = $_SERVER['DOCUMENT_ROOT'] . '/_admin/config/bing-api-key.txt';
$apiKey = trim(file_get_contents($apiKeyPath));

// 2. Preparar endpoint con API key en query parameter
$endpoint = 'https://ssl.bing.com/webmaster/api.svc/json/SubmitUrlbatch?apikey=' 
            . urlencode($apiKey);

// 3. Preparar body JSON con lista de URLs
$requestBody = json_encode([
    'siteUrl' => 'https://www.donde-reparar.com',
    'urlList' => $urls  // Array de URLs extraídas del sitemap
]);

// 4. Enviar con cURL (POST con JSON)
$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json; charset=utf-8'
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
```

### ⚙️ Configuración Requerida

**Pasos (5 minutos):**

1. **Acceder a Bing Webmaster Tools:**
   - URL: https://www.bing.com/webmasters
   - Iniciar sesión con cuenta de Microsoft

2. **Agregar sitio (si no está agregado):**
   - Click en "Add a site"
   - URL: `https://www.donde-reparar.com`
   - Verificar propiedad (archivo XML o meta tag)

3. **Generar API Key:**
   - Ir a: **Configuración** → **API Access**
   - Click en **"Generate API Key"**
   - Copiar la clave generada

4. **Guardar API Key en el servidor:**
   ```bash
   # Crear archivo: _admin/config/bing-api-key.txt
   # Pegar solo la API Key (sin espacios ni saltos de línea)
   
   # Ejemplo de contenido:
   ABC123XYZ456DEF789GHI012JKL345MNO678PQR901STU234
   ```

**Ver documentación completa:** `web_mejora/INSTRUCCIONES_BING_API.md`

### ✅ Respuesta Exitosa

```
HTTP 200
{"d": null}

✓ Bing API: Lote enviado correctamente
```

### ❌ Errores Comunes

| Código | Error | Solución |
|--------|-------|----------|
| 400 | API Key inválida | Regenerar API Key en Bing Webmaster Tools |
| 401 | No autorizado | Verificar que la API Key sea correcta |
| 403 | Acceso denegado | Verificar que el sitio esté agregado en Bing |
| 404 | Sitio no encontrado | Agregar `https://www.donde-reparar.com` en Bing |

### 🔢 Limitaciones

- **Máximo por batch:** 10,000 URLs
- **Solución implementada:** División automática en lotes si hay más de 10,000 URLs

```php
// División automática en chunks
$chunks = array_chunk($urlsForBing, 10000);

foreach ($chunks as $index => $chunk) {
    $bingApiResult = $this->pingBingWebmasterAPI($chunk);
    // Pausa de 0.5s entre lotes
    if ($batchNum < $chunkCount) {
        usleep(500000);
    }
}
```

### 🧪 Testing

**Archivo de prueba:** `web_mejora/test_sitemap_envio_bing.php`

```bash
# Acceder a:
https://www.donde-reparar.com/test_sitemap_envio_bing.php
```

**Resultado esperado:**
```
✅ API Key encontrada (longitud: XXX caracteres)
HTTP Code: 200
✅ ¡ÉXITO! Sitemap enviado correctamente a Bing
```

---

## 3️⃣ IndexNow (Múltiples Motores)

### 📌 Descripción
Protocolo abierto para **notificación instantánea** de URLs a múltiples motores de búsqueda simultáneamente.

**Motores compatibles:**
- ✅ Bing (Microsoft)
- ✅ Yandex (Rusia)
- ✅ Naver (Corea del Sur)
- ✅ Seznam.cz (República Checa)

**Nota:** Google NO es compatible con IndexNow (usa su propia API).

### 🔑 Autenticación
**Clave simple + archivo de verificación:**
- Clave aleatoria de 32 caracteres
- Archivo `{clave}.txt` en el root del sitio
- Sin registro ni cuenta requerida

### 📁 Archivos de Configuración

```
web_mejora/
├── _admin/config/
│   └── indexnow-key.txt              ← Clave privada
└── {clave}.txt                        ← Archivo de verificación público
    (ejemplo: a1b2c3d4e5f6...txt)
```

### 🛠️ Implementación Actual

**Ubicación:** `sitemap_helper.php → enviarIndexNow()`

```php
// 1. Leer clave IndexNow
$keyPath = $_SERVER['DOCUMENT_ROOT'] . '/_admin/config/indexnow-key.txt';
$key = trim(file_get_contents($keyPath));

// 2. Verificar archivo de verificación
$keyFile = $_SERVER['DOCUMENT_ROOT'] . '/' . $key . '.txt';
if (!file_exists($keyFile)) {
    return error...
}

// 3. Preparar datos JSON
$host = 'donde-reparar.com';  // SIN protocolo
$postData = json_encode([
    'host' => $host,
    'key' => $key,
    'urlList' => $urls  // Array de URLs
]);

// 4. Enviar a IndexNow API
$endpoint = 'https://api.indexnow.org/indexnow';

$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json; charset=utf-8'
]);
```

### ⚙️ Configuración Requerida

**Configuración automática (1 minuto):**

```bash
# Ejecutar script de configuración:
https://www.donde-reparar.com/test_indexnow_setup.php
```

Este script automáticamente:
1. ✅ Genera clave aleatoria de 32 caracteres
2. ✅ Crea `_admin/config/indexnow-key.txt`
3. ✅ Crea archivo de verificación `{clave}.txt` en el root

**Ver documentación completa:** `web_mejora/INSTRUCCIONES_INDEXNOW.md`

### ✅ Respuesta Exitosa

```
HTTP 200 o 202
✓ IndexNow: Sitemap enviado correctamente
→ Notificado a: Bing, Yandex, Seznam, Naver
```

### ❌ Errores Comunes

| Código | Error | Solución |
|--------|-------|----------|
| 400 | Solicitud mal formada | Verificar formato JSON |
| 403 | Clave inválida | Verificar que `{clave}.txt` sea accesible |
| 422 | URL no válida | Verificar que el sitemap exista y sea accesible |

### 🔢 Limitaciones

- **Máximo por llamada:** 10,000 URLs
- **Solución implementada:** División automática igual que Bing

```php
$chunks = array_chunk($urlsForBing, 10000);

foreach ($chunks as $index => $chunk) {
    $indexNowResult = $this->enviarIndexNow($chunk);
    usleep(500000);  // Pausa 0.5s entre lotes
}
```

### 🧪 Testing

**Archivo de prueba:** `web_mejora/test_indexnow.php`

```bash
# Acceder a:
https://www.donde-reparar.com/test_indexnow.php
```

**Resultado esperado:**
```
✅ Clave IndexNow encontrada
✅ Archivo de verificación existe: {clave}.txt
HTTP Code: 200
✅ ¡ÉXITO! Sitemap enviado correctamente via IndexNow
```

---

## 📊 Comparación de Métodos

| Característica | Google Search Console | Bing Webmaster Tools | IndexNow |
|----------------|----------------------|---------------------|----------|
| **Autenticación** | Service Account (OAuth 2.0) | API Key simple | Clave + archivo |
| **Complejidad configuración** | ⭐⭐⭐⭐⭐ Alta | ⭐⭐⭐ Media | ⭐ Muy baja |
| **Tiempo configuración** | ~30 minutos | ~15 minutos | ~1 minuto |
| **Requiere cuenta** | ✅ Google Cloud | ✅ Microsoft | ❌ No |
| **Requiere librería externa** | ✅ google/apiclient | ❌ cURL nativo | ❌ cURL nativo |
| **Método HTTP** | PUT (API oficial) | POST JSON | POST JSON |
| **Archivo de credenciales** | `service-account-credentials.json` | `bing-api-key.txt` | `indexnow-key.txt` + `{key}.txt` |
| **Motores cubiertos** | Solo Google | Solo Bing | Bing + Yandex + otros |
| **Respuesta exitosa** | HTTP 200 | HTTP 200 + JSON | HTTP 200/202 |
| **Límite de URLs** | Sin límite | 10,000/batch | 10,000/llamada |
| **Caducidad** | Token: 1 hora (auto-renovado) | Sin caducidad | Sin caducidad |
| **Uso recomendado** | Sitemap completo | Sitemap completo | URLs individuales* |

**\*Nota sobre IndexNow:** Aunque está diseñado para URLs individuales que cambian, lo usamos también para enviar sitemaps completos ya que cubre múltiples motores simultáneamente.

---

## 🗂️ Archivos de Configuración (Resumen)

### Ubicación de Archivos

```
web_mejora/
├── _admin/
│   └── config/
│       ├── service-account-credentials.json   ← Google (Service Account)
│       ├── bing-api-key.txt                   ← Bing (API Key)
│       └── indexnow-key.txt                   ← IndexNow (clave privada)
│
├── {clave-indexnow}.txt                       ← IndexNow (verificación pública)
│
├── vendor/
│   └── autoload.php                           ← Google Client Library
│
└── test_sitemap_envio.php                     ← Test Google
    test_sitemap_envio_bing.php                ← Test Bing
    test_indexnow_setup.php                    ← Configurar IndexNow
    test_indexnow.php                          ← Test IndexNow
```

### 🔐 Seguridad

**Archivos a proteger en `.gitignore`:**

```gitignore
# Credenciales sensibles
_admin/config/service-account-credentials.json
_admin/config/bing-api-key.txt
_admin/config/indexnow-key.txt

# Archivo de verificación IndexNow
/*.txt
!robots.txt  # Excepción para robots.txt
```

**Permisos recomendados:**

```bash
# Archivos de configuración (solo lectura servidor)
chmod 600 _admin/config/service-account-credentials.json
chmod 600 _admin/config/bing-api-key.txt
chmod 600 _admin/config/indexnow-key.txt

# Archivo de verificación IndexNow (lectura pública requerida)
chmod 644 {clave}.txt
```

---

## 🚀 Implementación en Código

### Método Principal de Notificación

**Ubicación:** `sitemap_helper.php → pingSearchEngines()`

```php
public function pingSearchEngines() {
    $this->addLog('Notificando a buscadores sobre sitemap...');
    
    $sitemapUrl = $this->baseUrl . '/sitemap.xml';
    $results = [];
    
    // 1. Google Search Console API
    $googleApiResult = $this->pingGoogleSearchConsoleAPI();
    if ($googleApiResult['success']) {
        $results['Google API'] = true;
        $this->addLog('✓ Google Search Console API: Sitemap enviado correctamente');
    } else {
        $results['Google API'] = false;
        $this->addLog('⚠ Google Search Console API: ' . $googleApiResult['message']);
    }
    
    // 2. Extraer URLs de todos los sitemaps
    $this->addLog('🔑 Bing Webmaster Tools API: Extrayendo URLs...');
    $urlsForBing = $this->extractUrlsFromSitemaps();
    
    if (!empty($urlsForBing)) {
        $totalUrls = count($urlsForBing);
        $this->addLog("📋 Encontradas {$totalUrls} URLs");
        
        // 3. Enviar a Bing API (con división en lotes si >10k)
        $chunks = array_chunk($urlsForBing, 10000);
        foreach ($chunks as $index => $chunk) {
            $bingApiResult = $this->pingBingWebmasterAPI($chunk);
            if ($bingApiResult['success']) {
                $results['Bing API'] = true;
                $this->addLog("✓ Bing API: Lote enviado correctamente");
            }
            usleep(500000);  // Pausa 0.5s entre lotes
        }
        
        // 4. Enviar a IndexNow (con división en lotes si >10k)
        $this->addLog('📡 IndexNow: Enviando URLs...');
        foreach ($chunks as $index => $chunk) {
            $indexNowResult = $this->enviarIndexNow($chunk);
            if ($indexNowResult['success']) {
                $results['IndexNow'] = true;
                $this->addLog("✓ IndexNow: Lote enviado correctamente");
            }
            usleep(500000);
        }
    }
    
    return $results;
}
```

### Extracción de URLs de Sitemaps

**Ubicación:** `sitemap_helper.php → extractUrlsFromSitemaps()`

```php
private function extractUrlsFromSitemaps() {
    $urls = [];
    $sitemapPath = $this->webRoot . '/sitemap.xml';
    
    // Leer sitemap principal
    $xml = simplexml_load_file($sitemapPath);
    
    // Detectar tipo: sitemap index o sitemap normal
    if (isset($xml->sitemap)) {
        // Es sitemap index → leer cada sitemap individual
        foreach ($xml->sitemap as $sitemap) {
            $sitemapUrl = (string)$sitemap->loc;
            $sitemapFile = str_replace($this->baseUrl . '/', 
                                      $this->webRoot . '/', 
                                      $sitemapUrl);
            
            if (file_exists($sitemapFile)) {
                $childUrls = $this->extractUrlsFromSingleSitemap($sitemapFile);
                $urls = array_merge($urls, $childUrls);
            }
        }
    } else {
        // Es sitemap normal → extraer URLs directamente
        $urls = $this->extractUrlsFromSingleSitemap($sitemapPath);
    }
    
    // Eliminar duplicados
    return array_unique($urls);
}

private function extractUrlsFromSingleSitemap($sitemapFile) {
    $urls = [];
    $xml = simplexml_load_file($sitemapFile);
    
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

---

## 🎮 Uso desde Panel de Administración

### Interfaz Web

**URL:** `https://www.donde-reparar.com/_admin/tools/sitemap_generator.php`

**Flujo de trabajo:**

1. **Iniciar sesión** como administrador
2. **Ir a:** Herramientas → Generador de Sitemaps
3. **Click en:** "Generar Todo" (genera sitemap.xml + sitemaps por carpeta)
4. **Esperar:** ~30-60 segundos
5. **Verificar log:** Todas las líneas con ✓ = éxito
6. **Click en:** "Notificar Buscadores"
7. **Resultado:**
   ```
   ✓ Google Search Console API: Sitemap enviado correctamente
   📋 Bing API: 1,234 URLs encontradas
   ✓ Bing API: Lote 1 enviado correctamente
   ✓ IndexNow: Lote 1 enviado correctamente
     → Notificado a: Bing, Yandex, Seznam, Naver
   ```

### Automatización (Opcional)

Para enviar sitemaps automáticamente sin intervención manual:

**Opción 1: Cron Job (Linux/cPanel)**

```bash
# Generar y enviar sitemaps cada semana
0 3 * * 0 curl -s https://www.donde-reparar.com/_admin/tools/sitemap_cron.php
```

**Opción 2: Task Scheduler (Windows)**

```powershell
# Ejecutar cada domingo a las 3:00 AM
schtasks /create /tn "Sitemap Semanal" /tr "curl https://www.donde-reparar.com/_admin/tools/sitemap_cron.php" /sc weekly /d SUN /st 03:00
```

---

## 🧪 Testing y Validación

### Tests Disponibles

| Archivo | Propósito | URL |
|---------|-----------|-----|
| `test_sitemap_envio.php` | Probar envío a Google | `/test_sitemap_envio.php` |
| `test_sitemap_envio_bing.php` | Probar envío a Bing | `/test_sitemap_envio_bing.php` |
| `test_indexnow_setup.php` | Configurar IndexNow | `/test_indexnow_setup.php` |
| `test_indexnow.php` | Probar envío IndexNow | `/test_indexnow.php` |

### Checklist de Validación

**Antes del primer envío:**

- [ ] **Google:**
  - [ ] Service Account creado en Google Cloud
  - [ ] API de Search Console activada
  - [ ] Archivo `service-account-credentials.json` copiado
  - [ ] Service Account agregado como Owner en Search Console
  - [ ] `composer require google/apiclient` ejecutado
  - [ ] `test_sitemap_envio.php` → resultado ✅

- [ ] **Bing:**
  - [ ] Sitio agregado en Bing Webmaster Tools
  - [ ] API Key generada
  - [ ] Archivo `bing-api-key.txt` creado
  - [ ] `test_sitemap_envio_bing.php` → resultado ✅

- [ ] **IndexNow:**
  - [ ] `test_indexnow_setup.php` ejecutado
  - [ ] Archivo `indexnow-key.txt` creado
  - [ ] Archivo `{clave}.txt` accesible en root
  - [ ] `test_indexnow.php` → resultado ✅

**Después de generar sitemap:**

- [ ] Verificar que `/sitemap.xml` existe y es válido
- [ ] Log del generador muestra ✓ en todas las operaciones
- [ ] Click en "Notificar Buscadores" ejecuta sin errores
- [ ] Los 3 métodos reportan éxito (Google, Bing, IndexNow)

### Validadores Online

**Sintaxis XML:**
- https://www.xml-sitemaps.com/validate-xml-sitemap.html

**Google Search Console:**
- https://search.google.com/search-console
- Sitemaps → Verificar estado del sitemap

**Bing Webmaster Tools:**
- https://www.bing.com/webmasters
- Sitemaps → Verificar fecha de último envío

---

## 🆘 Solución de Problemas (Troubleshooting)

### Google Search Console

#### Error: "Archivo de credenciales no encontrado"

**Causa:** No existe `service-account-credentials.json`

**Solución:**
```bash
# 1. Descargar JSON de Google Cloud Console
# 2. Copiar a: _admin/config/service-account-credentials.json
# 3. Verificar permisos: chmod 600
```

#### Error 403: "Acceso denegado"

**Causa:** Service Account no tiene permisos

**Solución:**
1. Ir a: https://search.google.com/search-console
2. Propiedad → Configuración → Usuarios y permisos
3. Agregar email del Service Account como **Owner**
4. Email formato: `nombre@proyecto-id.iam.gserviceaccount.com`

#### Error: "Google Client Library no encontrada"

**Causa:** Composer no instalado o `vendor/` vacío

**Solución:**
```bash
cd web_mejora
php composer.phar install
# O si Composer está instalado globalmente:
composer require google/apiclient
```

### Bing Webmaster Tools

#### Error 400: "API Key inválida"

**Causa:** API Key incorrecta o formato incorrecto

**Solución:**
1. Ir a: https://www.bing.com/webmasters
2. Configuración → API Access
3. Click en **"Regenerate API Key"**
4. Copiar EXACTAMENTE la nueva clave (sin espacios)
5. Pegar en `bing-api-key.txt` (solo primera línea)
6. Guardar y reintentar

#### Error 403: "Sitio no encontrado"

**Causa:** El sitio no está agregado en Bing Webmaster Tools

**Solución:**
1. Ir a: https://www.bing.com/webmasters
2. "Add a site" → `https://www.donde-reparar.com`
3. Verificar propiedad (XML o meta tag)
4. Esperar verificación
5. Reintentar envío

### IndexNow

#### Error 403: "Clave inválida"

**Causa:** Archivo de verificación `{clave}.txt` no existe o no es accesible

**Solución:**
```bash
# 1. Ejecutar configuración nuevamente
https://www.donde-reparar.com/test_indexnow_setup.php

# 2. Verificar que el archivo sea accesible:
https://www.donde-reparar.com/{tu-clave}.txt

# Debe mostrar la clave sin errores
```

#### Error 422: "URL no válida"

**Causa:** El sitemap no existe o no es accesible públicamente

**Solución:**
1. Verificar: `https://www.donde-reparar.com/sitemap.xml`
2. Debe devolver XML válido (no error 404)
3. Generar sitemap si no existe
4. Reintentar envío

### Problemas Generales

#### "cURL no está disponible"

**Causa:** Extensión cURL no instalada en PHP

**Solución (cPanel/Plesk):**
1. Panel → PHP Extensions
2. Activar `curl`
3. Reiniciar Apache/Nginx

**Solución (línea de comandos):**
```bash
# Ubuntu/Debian
sudo apt-get install php-curl

# CentOS/RHEL
sudo yum install php-curl

# Reiniciar servidor web
sudo service apache2 restart
```

#### "Muy lento / Timeout"

**Causa:** Demasiadas URLs para procesar

**Solución:**
- El sistema divide automáticamente en lotes de 10,000 URLs
- Si persiste, aumentar `max_execution_time` en `php.ini`:
  ```ini
  max_execution_time = 300  ; 5 minutos
  ```

---

## 📚 Referencias y Documentación

### Documentación Oficial

**Google Search Console API:**
- API Reference: https://developers.google.com/webmaster-tools/v1/api_reference_index
- Service Accounts: https://developers.google.com/identity/protocols/oauth2/service-account
- PHP Client Library: https://github.com/googleapis/google-api-php-client

**Bing Webmaster Tools API:**
- API Documentation: https://docs.microsoft.com/en-us/bingwebmaster/api/
- SubmitUrlbatch: https://learn.microsoft.com/en-us/bingwebmaster/api-reference

**IndexNow:**
- Documentación oficial: https://www.indexnow.org/
- Especificación: https://www.indexnow.org/documentation
- Blog Bing: https://blogs.bing.com/webmaster/october-2021/IndexNow

### Documentación Interna del Proyecto

- `web_mejora/GOOGLE_SETUP.md` - Configuración detallada de Google
- `web_mejora/INSTRUCCIONES_BING_API.md` - Configuración de Bing
- `web_mejora/INSTRUCCIONES_INDEXNOW.md` - Configuración de IndexNow
- `web_mejora/INSTALAR_COMPOSER.md` - Instalación de Composer
- `doc/RESUMEN_SITEMAP_IMPLEMENTADO.md` - Sistema de generación de sitemaps
- `doc/ESTRATEGIA_INDEXACION.md` - Estrategia general de indexación

### Archivos de Código Relevantes

- `web_mejora/_admin/tools/sitemap_helper.php` - Clase con toda la lógica
- `web_mejora/_admin/tools/sitemap_generator.php` - Interfaz web
- `web_mejora/test_sitemap_envio.php` - Test Google
- `web_mejora/test_sitemap_envio_bing.php` - Test Bing
- `web_mejora/test_indexnow_setup.php` - Configuración IndexNow
- `web_mejora/test_indexnow.php` - Test IndexNow

---

## 📝 Registro de Cambios

### Versión Actual (2026-04-09)

**Características:**
- ✅ Envío a Google Search Console mediante Service Account
- ✅ Envío a Bing mediante API SubmitUrlbatch
- ✅ Envío a múltiples motores vía IndexNow
- ✅ Extracción automática de URLs de todos los sitemaps
- ✅ División automática en lotes (máx. 10,000 URLs/batch)
- ✅ Interfaz web integrada en panel de administración
- ✅ Tests individuales para cada método
- ✅ Manejo robusto de errores
- ✅ Logging detallado de operaciones

---

## 🎯 Resumen de Configuración Rápida

### Primera Vez (Configuración Inicial)

**Tiempo total estimado: ~45 minutos**

1. **Google Search Console** (~30 min):
   ```bash
   # Ver: web_mejora/GOOGLE_SETUP.md
   # - Crear Service Account en Google Cloud
   # - Descargar JSON de credenciales
   # - Copiar a _admin/config/service-account-credentials.json
   # - Agregar Service Account a Search Console
   # - Instalar: composer require google/apiclient
   # Probar: /test_sitemap_envio.php
   ```

2. **Bing Webmaster Tools** (~10 min):
   ```bash
   # Ver: web_mejora/INSTRUCCIONES_BING_API.md
   # - Acceder a https://www.bing.com/webmasters
   # - Generar API Key en Configuración → API Access
   # - Crear archivo: _admin/config/bing-api-key.txt
   # Probar: /test_sitemap_envio_bing.php
   ```

3. **IndexNow** (~1 min):
   ```bash
   # Ver: web_mejora/INSTRUCCIONES_INDEXNOW.md
   # Ejecutar: /test_indexnow_setup.php (genera todo automáticamente)
   # Probar: /test_indexnow.php
   ```

### Uso Diario (Generar + Enviar)

**Tiempo estimado: ~2 minutos**

1. Ir a: `https://www.donde-reparar.com/_admin/tools/sitemap_generator.php`
2. Click: **"Generar Todo"**
3. Esperar: ~30-60 segundos
4. Click: **"Notificar Buscadores"**
5. Verificar log: ✅ en Google, Bing, IndexNow

✅ **¡Listo!** Sitemap enviado a todos los buscadores.

---

**Documento creado:** 2026-04-09  
**Última actualización:** 2026-04-09  
**Versión:** 1.0  
**Autor:** Sistema de Documentación - Donde-reparar.com
