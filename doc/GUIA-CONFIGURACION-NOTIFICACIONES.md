# Guía de Configuración: Sistema de Notificaciones a Buscadores

## 📋 Resumen del Problema

El sistema antiguo de "ping" HTTP simple ya no funciona para Google y Bing porque **deprecaron estos endpoints públicos**:
- ❌ Google: `http://www.google.com/ping` → 404
- ❌ Bing: `http://www.bing.com/ping` → 410 (Gone)

## ✅ Solución: Sistema Moderno con APIs Oficiales

Tu proyecto ya tiene implementado un sistema profesional que usa APIs oficiales. Solo necesitas completar la configuración.

---

## 🔧 Configuración Paso a Paso

### 1. **Google Search Console API** (Recomendado - Gratis)

#### Prerrequisitos verificados:
- ✅ Archivo existe: `admin/config/service-account-credentials.json`

#### Pasos para configurar:

1. **Crear proyecto en Google Cloud**
   - Ir a: https://console.cloud.google.com
   - Crear nuevo proyecto: "Portfolio SEO"

2. **Habilitar Search Console API**
   - En el proyecto, ir a "APIs & Services" > "Enable APIs and Services"
   - Buscar "Google Search Console API"
   - Hacer clic en "Enable"

3. **Crear Service Account**
   - Ir a "IAM & Admin" > "Service Accounts"
   - Clic en "Create Service Account"
   - Nombre: `portfolio-sitemap-notifier`
   - Rol: No necesita rol especial
   - Clic en "Create and Continue" > "Done"

4. **Generar credenciales JSON**
   - Hacer clic en el Service Account creado
   - Ir a la pestaña "Keys"
   - Clic en "Add Key" > "Create new key"
   - Seleccionar tipo: **JSON**
   - Descargar archivo (se llamará algo como `proyecto-123456-abcdef.json`)

5. **Reemplazar archivo de credenciales**
   ```bash
   # Copiar el archivo descargado a:
   admin/config/service-account-credentials.json
   ```

6. **Agregar Service Account en Search Console**
   - Ir a: https://search.google.com/search-console
   - Seleccionar tu propiedad: `juancarlosmacias.es`
   - Ir a "Configuración" > "Usuarios y permisos"
   - Clic en "Agregar usuario"
   - Email: `portfolio-sitemap-notifier@tu-proyecto.iam.gserviceaccount.com`
     (Copiar el email del Service Account desde Google Cloud)
   - Permisos: **Propietario** (Owner)
   - Guardar

7. **Verificar formato de propiedad**
   - En Search Console, tu sitio debe estar agregado como **Domain Property**
   - Formato: `sc-domain:juancarlosmacias.es`
   - Si solo tienes URL Property (https://), agregar también Domain Property

---

### 2. **Bing Webmaster Tools API** (Opcional)

#### Prerrequisitos verificados:
- ✅ Archivo existe: `admin/config/bing-api-key.txt`

#### Pasos para obtener API Key válida:

1. **Acceder a Bing Webmaster**
   - Ir a: https://www.bing.com/webmasters
   - Iniciar sesión con cuenta Microsoft

2. **Agregar o verificar sitio**
   - Si no está agregado: "Add a Site"
   - Ingresar: `https://www.juancarlosmacias.es`
   - Verificar propiedad (XML file, meta tag, o DNS)

3. **Generar API Key**
   - Una vez verificado el sitio, ir a "Settings" > "API Access"
   - Clic en "Get API Key" o "Generate New Key"
   - **IMPORTANTE**: Copiar la API Key completa

4. **Guardar API Key**
   - Abrir: `admin/config/bing-api-key.txt`
   - Pegar la API Key (solo la clave, sin espacios)
   - Guardar archivo

   ```text
   # Contenido del archivo (ejemplo):
   A1B2C3D4E5F6G7H8I9J0K1L2M3N4O5P6
   ```

5. **Verificar endpoint correcto**
   - El sistema usa: `https://ssl.bing.com/webmaster/api.svc/json/SubmitUrlbatch`
   - Este endpoint requiere que el sitio esté **verificado y agregado** en Bing Webmaster

---

### 3. **IndexNow** (Recomendado - Gratis y Fácil)

IndexNow notifica automáticamente a **Bing, Yandex, Naver y Seznam** simultáneamente.

#### Pasos para configurar:

1. **Ejecutar script de setup**
   - Ir en navegador a: `https://www.juancarlosmacias.es/setup-indexnow.php`
   - Esto generará automáticamente:
     - `admin/config/indexnow-key.txt` (clave privada)
     - `{clave}.txt` en la raíz (archivo de verificación pública)

2. **Verificar archivos creados**
   - Verificar que existe: `admin/config/indexnow-key.txt`
   - Verificar que existe archivo público: `https://www.juancarlosmacias.es/{clave}.txt`
     (donde `{clave}` es un código hexadecimal de 32 caracteres)

3. **Eliminar script de setup**
   ```bash
   # Por seguridad, eliminar después de ejecutar:
   rm setup-indexnow.php
   ```

4. **Probar IndexNow**
   - IndexNow funciona de inmediato, sin registro previo
   - Notifica a Bing, Yandex, Naver, Seznam automáticamente

---

## 🚀 Uso del Nuevo Sistema

### Opción 1: Desde Admin Panel (Interfaz gráfica)

1. Ir a: `https://www.juancarlosmacias.es/admin/pages/sitemap-manager.php`
2. Hacer clic en botón: **"Generar Sitemap"**
3. Hacer clic en botón: **"Notificar Buscadores"**

Esto usará el nuevo sistema con APIs oficiales.

### Opción 2: Desde código PHP

```php
require_once 'admin/classes/SearchEngineNotifier.php';

$baseUrl = 'https://www.juancarlosmacias.es';
$notifier = new SearchEngineNotifier($baseUrl);

// Extraer URLs del sitemap
$generator = new SitemapGenerator($baseUrl);
$urls = $generator->extractUrlsFromSitemap($_SERVER['DOCUMENT_ROOT'] . '/sitemap.xml');

// Notificar a TODOS los proveedores
$result = $notifier->notifyAll($urls);

echo "Éxito: {$result['providers_success']}/{$result['providers_total']} proveedores\n";
```

### Opción 3: Desde API REST

```bash
curl -X POST https://www.juancarlosmacias.es/admin/api/notify-search-engines.php \
  -H "Content-Type: application/json" \
  --cookie "sesion-admin-cookie"
```

---

## 🔍 Verificar Configuración

### Comprobar Google Search Console:

1. Verificar que el archivo JSON tiene formato correcto:
   ```bash
   cat admin/config/service-account-credentials.json | jq .
   ```

2. Verificar que contiene campos requeridos:
   - `type: "service_account"`
   - `project_id`
   - `private_key`
   - `client_email`

3. Probar desde código:
   ```php
   $google = new GoogleSearchConsoleProvider('https://www.juancarlosmacias.es');
   $result = $google->notifyUrls();
   var_dump($result);
   ```

### Comprobar Bing Webmaster:

1. Verificar API Key válida:
   ```bash
   cat admin/config/bing-api-key.txt
   ```

2. Verificar que el sitio está verificado en Bing Webmaster Tools

3. Probar desde código:
   ```php
   $bing = new BingWebmasterProvider('https://www.juancarlosmacias.es');
   $result = $bing->notifyUrls(['https://www.juancarlosmacias.es/']);
   var_dump($result);
   ```

### Comprobar IndexNow:

1. Verificar archivos existen:
   ```bash
   ls admin/config/indexnow-key.txt
   ls {clave}.txt  # En raíz del sitio
   ```

2. Verificar archivo público accesible:
   ```bash
   curl https://www.juancarlosmacias.es/{clave}.txt
   ```

3. Probar desde código:
   ```php
   $indexnow = new IndexNowProvider('https://www.juancarlosmacias.es');
   $result = $indexnow->notifyUrls(['https://www.juancarlosmacias.es/']);
   var_dump($result);
   ```

---

## 📊 Diferencias entre Sistemas

| Característica | Sistema Antiguo (Ping) | Sistema Nuevo (APIs) |
|---|---|---|
| **Google** | ❌ Deprecado (404) | ✅ Search Console API |
| **Bing** | ❌ Deprecado (410) | ✅ Webmaster API |
| **Yandex** | ⚠️ Ping funciona | ✅ IndexNow API |
| **IndexNow** | ❌ No disponible | ✅ API oficial |
| **Autenticación** | No requerida | API Keys/Service Account |
| **Límites** | No documentados | 10,000 URLs/batch |
| **Confiabilidad** | Baja (deprecado) | Alta (oficial) |
| **Trazabilidad** | Mínima | Logs detallados |

---

## 🔐 Seguridad

**Archivos sensibles que NO deben estar en Git:**

```gitignore
admin/config/service-account-credentials.json
admin/config/bing-api-key.txt
admin/config/indexnow-key.txt
admin/config/config.local.php
*.txt  # Archivos de verificación IndexNow en raíz
```

**Verificar `.gitignore`:**
```bash
grep -E "(credentials\.json|bing-api-key\.txt|indexnow-key\.txt)" .gitignore
```

---

## 📝 Logs para Debugging

Los logs se guardan en: `logs/search-engines/`

Estructura de archivos:
```
logs/search-engines/
├── notifier_YYYYMMDD.log       # Log general del orquestador
├── google_YYYYMMDD.log         # Log específico de Google
├── bing_YYYYMMDD.log           # Log específico de Bing
└── indexnow_YYYYMMDD.log       # Log específico de IndexNow
```

Ver logs recientes:
```bash
ls -lh logs/search-engines/
tail -f logs/search-engines/notifier_$(date +%Y%m%d).log
```

---

## 🎯 Resumen de Acciones Inmediatas

1. ✅ **IndexNow** (más fácil):
   - Ejecutar: `https://www.juancarlosmacias.es/setup-indexnow.php`
   - Eliminar script después
   - **Listo** - funciona de inmediato

2. 🔧 **Google Search Console** (recomendado):
   - Crear Service Account en Google Cloud
   - Descargar JSON y guardar en `admin/config/service-account-credentials.json`
   - Agregar email del Service Account como Owner en Search Console

3. 🔧 **Bing Webmaster** (opcional):
   - Generar API Key desde Bing Webmaster Tools
   - Guardar en `admin/config/bing-api-key.txt`
   - Verificar que el sitio esté agregado en Bing Webmaster

---

## 🆘 Troubleshooting

### Error: "Google Service Account credentials not found"
- Verificar que existe: `admin/config/service-account-credentials.json`
- Verificar permisos de lectura: `chmod 644 admin/config/service-account-credentials.json`

### Error: "Google API Error: 403 Forbidden"
- El Service Account no tiene permisos en Search Console
- Agregar email del Service Account como **Owner** (no Viewer)

### Error: "Google API Error: 404 Not Found"
- El sitio no está agregado en Search Console
- O está agregado como URL Property en lugar de Domain Property
- Agregar como: `sc-domain:juancarlosmacias.es`

### Error: "Bing API Error: Unauthorized"
- API Key incorrecta o expirada
- Regenerar API Key desde Bing Webmaster Tools
- Verificar que el archivo no tenga espacios ni saltos de línea extra

### Error: "IndexNow key not found"
- Ejecutar: `https://www.juancarlosmacias.es/setup-indexnow.php`
- O crear manualmente: `admin/config/indexnow-key.txt` con 32 caracteres hex

### Error: "IndexNow verification file not found"
- Verificar que existe archivo público: `{clave}.txt` en raíz
- Contenido debe ser igual a la clave en `admin/config/indexnow-key.txt`

---

## 📚 Referencias Oficiales

- **Google Search Console API**: https://developers.google.com/webmaster-tools/v1/how-tos/authorizing
- **Bing Webmaster API**: https://docs.microsoft.com/en-us/bingwebmaster/
- **IndexNow Protocol**: https://www.indexnow.org/documentation
- **Pingomatic** (sistema antiguo): https://pingomatic.com/

---

**Última actualización**: 2026-04-10  
**Versión del sistema**: 1.0.0
