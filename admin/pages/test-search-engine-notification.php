<?php
/**
 * ========================================
 * SCRIPT DE PRUEBA: NOTIFICACIONES A BUSCADORES
 * ========================================
 * 
 * Prueba las configuraciones de Google Search Console y Bing Webmaster
 * sin realizar notificaciones reales.
 * 
 * IMPORTANTE: Eliminar o proteger este archivo después de las pruebas
 * 
 * @author Sistema Portfolio JCMS
 * @version 1.0.0
 */

define('ADMIN_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../classes/SearchEngineNotifier.php';
require_once __DIR__ . '/../classes/Providers/GoogleSearchConsoleProvider.php';
require_once __DIR__ . '/../classes/Providers/BingWebmasterProvider.php';
require_once __DIR__ . '/../classes/Providers/IndexNowProvider.php';

// Verificar autenticación
$auth = new AdminAuth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Variables para el layout
$pageTitle = 'Test de Notificaciones';
$currentPage = 'test-search-engine-notification';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Notificaciones - Portfolio Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    
    <!-- Admin Layout CSS -->
    <link href="../assets/css/base.css" rel="stylesheet">
    <link href="../assets/css/components.css" rel="stylesheet">
    <link href="../assets/css/layout.css" rel="stylesheet">
    
    <style>
        .test-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
        }
        .test-result {
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
        }
        .test-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .test-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .test-warning {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
        }
        .test-info {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        .test-step {
            padding: 10px;
            margin: 5px 0;
            background: #f8f9fa;
            border-left: 3px solid #007bff;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            color: #d63384;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
            max-height: 400px;
            overflow-y: auto;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <?php include __DIR__ . '/../includes/components/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-bug me-2"></i>Test de Notificaciones a Buscadores</h2>
                    <a href="sitemap-manager.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Volver a Sitemap Manager
                    </a>
                </div>

                <!-- Advertencia de seguridad -->
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Seguridad:</strong> Este script muestra información sensible de configuración. 
                    Elimínalo o restringe el acceso después de completar las pruebas.
                </div>

                <?php
                // Detectar entorno
                $isLocal = strpos($_SERVER['HTTP_HOST'], 'localhost') !== false 
                           || strpos($_SERVER['HTTP_HOST'], 'perfil.in') !== false;
                
                $baseUrl = $isLocal 
                    ? 'http://www.perfil.in' 
                    : 'https://www.juancarlosmacias.es';
                
                echo '<div class="alert alert-info">';
                echo '<i class="bi bi-info-circle me-2"></i>';
                echo '<strong>Entorno detectado:</strong> ' . ($isLocal ? 'LOCAL' : 'PRODUCCIÓN');
                echo ' - URL Base: <code>' . htmlspecialchars($baseUrl) . '</code>';
                echo '</div>';
                ?>

                <!-- Test Google Search Console -->
                <div class="test-section">
                    <h3><i class="bi bi-google me-2"></i>Google Search Console API</h3>
                    <?php
                    echo '<div class="test-step">Probando configuración de Google...</div>';
                    testGoogleSearchConsole($baseUrl);
                    ?>
                </div>

                <!-- Test Bing Webmaster -->
                <div class="test-section">
                    <h3><i class="bi bi-windows me-2"></i>Bing Webmaster Tools API</h3>
                    <?php
                    echo '<div class="test-step">Probando configuración de Bing...</div>';
                    testBingWebmaster($baseUrl);
                    ?>
                </div>

                <!-- Test IndexNow -->
                <div class="test-section">
                    <h3><i class="bi bi-lightning me-2"></i>IndexNow Protocol</h3>
                    <?php
                    echo '<div class="test-step">Probando configuración de IndexNow...</div>';
                    testIndexNow($baseUrl);
                    ?>
                </div>

                <!-- Resumen final -->
                <div class="test-section" style="background: #f8f9fa;">
                    <h3><i class="bi bi-clipboard-check me-2"></i>Resumen y Recomendaciones</h3>
                    <div id="summary"></div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
/**
 * Prueba configuración de Google Search Console
 */
function testGoogleSearchConsole($baseUrl) {
    $results = [];
    
    // 1. Verificar archivo de credenciales
    $credentialsPath = dirname(__DIR__) . '/config/service-account-credentials.json';
    
    echo '<div class="test-step">1️⃣ Verificando archivo de credenciales...</div>';
    
    if (!file_exists($credentialsPath)) {
        showError('❌ Archivo de credenciales NO encontrado', [
            'Ruta esperada: ' . $credentialsPath,
            'Solución: Descargar credenciales desde Google Cloud Console',
            'Documentación: doc/GUIA-CONFIGURACION-NOTIFICACIONES.md'
        ]);
        return;
    }
    
    showSuccess('✅ Archivo de credenciales encontrado');
    
    // 2. Verificar contenido del JSON
    echo '<div class="test-step">2️⃣ Validando formato del JSON...</div>';
    
    $credentialsContent = file_get_contents($credentialsPath);
    $credentials = json_decode($credentialsContent, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        showError('❌ JSON inválido', [
            'Error: ' . json_last_error_msg(),
            'El archivo no tiene un formato JSON válido'
        ]);
        return;
    }
    
    showSuccess('✅ JSON válido');
    
    // 3. Verificar campos requeridos
    echo '<div class="test-step">3️⃣ Verificando campos requeridos...</div>';
    
    $requiredFields = [
        'type' => 'service_account',
        'project_id' => null,
        'private_key_id' => null,
        'private_key' => null,
        'client_email' => null,
        'client_id' => null,
        'auth_uri' => null,
        'token_uri' => null
    ];
    
    $missingFields = [];
    foreach ($requiredFields as $field => $expectedValue) {
        if (!isset($credentials[$field])) {
            $missingFields[] = $field;
        } elseif ($expectedValue !== null && $credentials[$field] !== $expectedValue) {
            showWarning("⚠️ Campo '{$field}' tiene valor inesperado: {$credentials[$field]} (esperado: {$expectedValue})");
        }
    }
    
    if (!empty($missingFields)) {
        showError('❌ Campos faltantes en el JSON', $missingFields);
        return;
    }
    
    showSuccess('✅ Todos los campos requeridos presentes');
    
    // 4. Mostrar información del Service Account
    echo '<div class="test-step">4️⃣ Información del Service Account:</div>';
    echo '<div class="test-result test-info">';
    echo '<strong>Project ID:</strong> <code>' . htmlspecialchars($credentials['project_id']) . '</code><br>';
    echo '<strong>Client Email:</strong> <code>' . htmlspecialchars($credentials['client_email']) . '</code><br>';
    echo '<strong>Private Key ID:</strong> <code>' . htmlspecialchars(substr($credentials['private_key_id'], 0, 16)) . '...</code>';
    echo '</div>';
    
    // 5. Verificar librería Google Client
    echo '<div class="test-step">5️⃣ Verificando librería Google Client...</div>';
    
    // Intentar múltiples ubicaciones (producción y desarrollo)
    $autoloadPaths = [
        // Producción: vendor fuera de public_html
        dirname(dirname(dirname(dirname(__DIR__)))) . '/vendor/autoload.php',
        // Desarrollo: vendor en raíz del proyecto
        dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php',
        // Alternativa: vendor en directorio padre
        dirname(dirname(dirname(dirname(dirname(__DIR__))))) . '/vendor/autoload.php'
    ];
    
    $autoloadPath = null;
    $searchedPaths = [];
    foreach ($autoloadPaths as $path) {
        $searchedPaths[] = $path;
        if (file_exists($path)) {
            $autoloadPath = $path;
            break;
        }
    }
    
    if (!$autoloadPath) {
        showError('❌ Google Client Library NO instalada', array_merge(
            ['Rutas buscadas:'],
            array_map(function($p) { return '- ' . $p; }, $searchedPaths),
            ['Solución: Ejecutar en terminal: composer require google/apiclient']
        ));
        return;
    }
    
    showSuccess('✅ Librería encontrada en: ' . basename(dirname($autoloadPath)));
    require_once $autoloadPath;
    
    if (!class_exists('Google\Client')) {
        showError('❌ Clase Google\Client no encontrada', [
            'El autoloader existe pero no carga la clase',
            'Solución: Reinstalar con composer require google/apiclient'
        ]);
        return;
    }
    
    showSuccess('✅ Librería Google Client instalada correctamente');
    
    // 6. Intentar crear cliente y obtener token
    echo '<div class="test-step">6️⃣ Probando autenticación con Google...</div>';
    
    try {
        $client = new Google\Client();
        $client->setAuthConfig($credentialsPath);
        $client->setScopes(['https://www.googleapis.com/auth/webmasters']);
        
        showSuccess('✅ Cliente Google creado correctamente');
        
        // Intentar generar token
        $client->fetchAccessTokenWithAssertion();
        $token = $client->getAccessToken();
        
        if (!$token) {
            showError('❌ No se pudo generar Access Token', [
                'El Service Account puede no tener permisos',
                'Verificar que el Service Account esté habilitado en Google Cloud'
            ]);
            return;
        }
        
        showSuccess('✅ Access Token generado correctamente');
        
        echo '<div class="test-result test-info">';
        echo '<strong>Token Type:</strong> ' . ($token['token_type'] ?? 'N/A') . '<br>';
        echo '<strong>Expires In:</strong> ' . ($token['expires_in'] ?? 'N/A') . ' segundos<br>';
        echo '<strong>Token Preview:</strong> <code>' . substr($token['access_token'] ?? '', 0, 20) . '...</code>';
        echo '</div>';
        
        // 7. Probar conexión con Search Console API
        echo '<div class="test-step">7️⃣ Probando conexión con Search Console API...</div>';
        
        try {
            $service = new Google\Service\Webmasters($client);
            showSuccess('✅ Servicio Webmasters creado correctamente');
            
            // Intentar listar sitios (esto requiere permisos)
            echo '<div class="test-step">8️⃣ Probando permisos del Service Account...</div>';
            
            try {
                $domain = parse_url($baseUrl, PHP_URL_HOST);
                $siteUrl = 'sc-domain:' . $domain;
                
                echo '<div class="test-result test-info">';
                echo '<strong>Dominio detectado:</strong> <code>' . $domain . '</code><br>';
                echo '<strong>Site URL (Domain Property):</strong> <code>' . $siteUrl . '</code>';
                echo '</div>';
                
                // Intentar enviar sitemap (sin hacerlo realmente, solo validar)
                showWarning('⚠️ Prueba de envío no implementada (evitar spam)', [
                    'Para probar el envío real, usar el botón "Notificar Buscadores"',
                    'Verificar manualmente que el Service Account sea Owner en Search Console'
                ]);
                
                showInfo('📋 Pasos para completar la configuración:', [
                    '1. Ir a Google Search Console: https://search.google.com/search-console',
                    '2. Seleccionar propiedad: ' . $domain,
                    '3. Menú: Configuración > Usuarios y permisos',
                    '4. Agregar usuario: ' . $credentials['client_email'],
                    '5. Permisos: PROPIETARIO (Owner)',
                    '6. Guardar cambios'
                ]);
                
            } catch (Google\Service\Exception $e) {
                $httpCode = $e->getCode();
                
                if ($httpCode === 403) {
                    showError('❌ Error 403: Acceso Denegado', [
                        'El Service Account NO tiene permisos en Search Console',
                        'Email del Service Account: ' . $credentials['client_email'],
                        'Solución: Agregar este email como OWNER en Search Console',
                        'URL: https://search.google.com/search-console'
                    ]);
                } elseif ($httpCode === 404) {
                    showError('❌ Error 404: Propiedad No Encontrada', [
                        'El sitio no está agregado en Search Console',
                        'O está agregado con formato incorrecto',
                        'Formato requerido: Domain Property (sc-domain:' . $domain . ')',
                        'Verificar en: https://search.google.com/search-console'
                    ]);
                } else {
                    showError('❌ Google API Error: ' . $e->getMessage(), [
                        'HTTP Code: ' . $httpCode,
                        'Revisar documentación de errores de Google API'
                    ]);
                }
            }
            
        } catch (Exception $e) {
            showError('❌ Error creando servicio Webmasters', [
                'Mensaje: ' . $e->getMessage()
            ]);
        }
        
    } catch (Exception $e) {
        showError('❌ Error en autenticación con Google', [
            'Mensaje: ' . $e->getMessage(),
            'Tipo: ' . get_class($e)
        ]);
    }
}

/**
 * Prueba configuración de Bing Webmaster
 */
function testBingWebmaster($baseUrl) {
    // 1. Verificar archivo de API Key
    $apiKeyPath = dirname(__DIR__) . '/config/bing-api-key.txt';
    
    echo '<div class="test-step">1️⃣ Verificando archivo de API Key...</div>';
    
    if (!file_exists($apiKeyPath)) {
        showError('❌ Archivo de API Key NO encontrado', [
            'Ruta esperada: ' . $apiKeyPath,
            'Solución: Crear el archivo y agregar la API Key de Bing Webmaster Tools',
            'Obtener en: https://www.bing.com/webmasters > Settings > API Access'
        ]);
        return;
    }
    
    showSuccess('✅ Archivo de API Key encontrado');
    
    // 2. Verificar contenido
    echo '<div class="test-step">2️⃣ Validando API Key...</div>';
    
    $apiKey = trim(file_get_contents($apiKeyPath));
    
    if (empty($apiKey)) {
        showError('❌ API Key está vacía', [
            'El archivo existe pero no contiene ninguna clave',
            'Agregar la API Key obtenida desde Bing Webmaster Tools'
        ]);
        return;
    }
    
    $keyLength = strlen($apiKey);
    showSuccess('✅ API Key cargada (longitud: ' . $keyLength . ' caracteres)');
    
    echo '<div class="test-result test-info">';
    echo '<strong>API Key Preview:</strong> <code>' . substr($apiKey, 0, 8) . '...' . substr($apiKey, -4) . '</code><br>';
    echo '<strong>Longitud:</strong> ' . $keyLength . ' caracteres';
    echo '</div>';
    
    // 3. Probar endpoint de Bing
    echo '<div class="test-step">3️⃣ Probando conexión con Bing Webmaster API...</div>';
    
    $endpoint = 'https://ssl.bing.com/webmaster/api.svc/json/SubmitUrlbatch?apikey=' . urlencode($apiKey);
    
    // Preparar una petición de prueba con pocas URLs
    $testUrls = [$baseUrl . '/'];
    $requestBody = json_encode([
        'siteUrl' => $baseUrl,
        'urlList' => $testUrls
    ]);
    
    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $requestBody,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=utf-8'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        showError('❌ Error de conexión cURL', [
            'Error: ' . $curlError,
            'Verificar conexión a internet y firewall'
        ]);
        return;
    }
    
    echo '<div class="test-result test-info">';
    echo '<strong>HTTP Status Code:</strong> <code>' . $httpCode . '</code><br>';
    echo '<strong>Response Length:</strong> ' . strlen($response) . ' bytes';
    echo '</div>';
    
    // Analizar respuesta
    if ($httpCode === 200) {
        showSuccess('✅ Conexión exitosa con Bing Webmaster API');
        
        $responseData = json_decode($response, true);
        if ($responseData !== null) {
            echo '<pre><strong>Respuesta de Bing:</strong><br>' . htmlspecialchars(json_encode($responseData, JSON_PRETTY_PRINT)) . '</pre>';
        } else {
            echo '<pre><strong>Respuesta de Bing (raw):</strong><br>' . htmlspecialchars($response) . '</pre>';
        }
        
        showInfo('📋 Verificar en Bing Webmaster Tools:', [
            'URL: https://www.bing.com/webmasters',
            'Sección: URL Submission > Latest Submission',
            'Verificar que aparezca la URL de prueba enviada'
        ]);
        
    } elseif ($httpCode === 401) {
        showError('❌ Error 401: No Autorizado', [
            'La API Key es inválida o ha expirado',
            'Solución: Generar nueva API Key desde Bing Webmaster Tools',
            'URL: https://www.bing.com/webmasters > Settings > API Access'
        ]);
    } elseif ($httpCode === 403) {
        showError('❌ Error 403: Acceso Prohibido', [
            'La API Key no tiene permisos para este sitio',
            'Verificar que el sitio esté agregado en Bing Webmaster Tools'
        ]);
    } elseif ($httpCode === 410) {
        showError('❌ Error 410: Gone', [
            'El endpoint ha sido eliminado o modificado',
            'Posibles causas:',
            '- El sitio no está verificado en Bing Webmaster Tools',
            '- El sitio fue eliminado de Bing Webmaster Tools',
            '- La API Key pertenece a otra cuenta',
            'Solución: Verificar sitio en https://www.bing.com/webmasters'
        ]);
    } else {
        showError('❌ Error HTTP ' . $httpCode, [
            'Respuesta del servidor: ' . $response,
            'Consultar documentación de Bing Webmaster API'
        ]);
    }
}

/**
 * Prueba configuración de IndexNow
 */
function testIndexNow($baseUrl) {
    // 1. Verificar archivo de clave
    $keyPath = dirname(__DIR__) . '/config/indexnow-key.txt';
    
    echo '<div class="test-step">1️⃣ Verificando archivo de clave...</div>';
    
    if (!file_exists($keyPath)) {
        showWarning('⚠️ IndexNow NO configurado', [
            'Archivo no encontrado: ' . $keyPath,
            'Solución: Ejecutar setup-indexnow.php desde el navegador',
            'URL: ' . $baseUrl . '/setup-indexnow.php'
        ]);
        return;
    }
    
    showSuccess('✅ Archivo de clave encontrado');
    
    // 2. Verificar contenido
    echo '<div class="test-step">2️⃣ Validando clave IndexNow...</div>';
    
    $key = trim(file_get_contents($keyPath));
    
    if (empty($key)) {
        showError('❌ Clave vacía', [
            'El archivo existe pero está vacío',
            'Ejecutar nuevamente setup-indexnow.php'
        ]);
        return;
    }
    
    if (!preg_match('/^[a-f0-9]{32}$/', $key)) {
        showWarning('⚠️ Formato de clave inusual', [
            'Formato esperado: 32 caracteres hexadecimales',
            'Formato actual: ' . strlen($key) . ' caracteres',
            'La clave puede funcionar, pero no es el formato estándar'
        ]);
    }
    
    showSuccess('✅ Clave válida (longitud: ' . strlen($key) . ' caracteres)');
    
    echo '<div class="test-result test-info">';
    echo '<strong>Clave Preview:</strong> <code>' . substr($key, 0, 16) . '...</code>';
    echo '</div>';
    
    // 3. Verificar archivo público de verificación
    echo '<div class="test-step">3️⃣ Verificando archivo público de verificación...</div>';
    
    $publicKeyFile = $_SERVER['DOCUMENT_ROOT'] . '/' . $key . '.txt';
    
    if (!file_exists($publicKeyFile)) {
        showError('❌ Archivo público NO encontrado', [
            'Ruta esperada: ' . $publicKeyFile,
            'Este archivo es requerido para verificar propiedad del sitio',
            'Solución: Ejecutar setup-indexnow.php para crearlo'
        ]);
        return;
    }
    
    showSuccess('✅ Archivo público encontrado');
    
    // Verificar contenido del archivo público
    $publicKeyContent = trim(file_get_contents($publicKeyFile));
    
    if ($publicKeyContent !== $key) {
        showError('❌ El contenido del archivo público NO coincide con la clave', [
            'Clave privada: ' . substr($key, 0, 16) . '...',
            'Archivo público: ' . substr($publicKeyContent, 0, 16) . '...',
            'Solución: Regenerar con setup-indexnow.php'
        ]);
        return;
    }
    
    showSuccess('✅ Archivo público coincide con la clave privada');
    
    // 4. Verificar accesibilidad pública
    echo '<div class="test-step">4️⃣ Verificando accesibilidad pública del archivo...</div>';
    
    $publicUrl = $baseUrl . '/' . $key . '.txt';
    
    $ch = curl_init($publicUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_FOLLOWLOCATION => true
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        showWarning('⚠️ No se pudo verificar acceso público', [
            'Error: ' . $curlError,
            'Esto puede ser normal en entorno local',
            'Verificar manualmente: ' . $publicUrl
        ]);
    } elseif ($httpCode === 200) {
        if (trim($response) === $key) {
            showSuccess('✅ Archivo público accesible y contenido correcto');
            echo '<div class="test-result test-info">';
            echo '<strong>URL pública:</strong> <a href="' . $publicUrl . '" target="_blank">' . $publicUrl . '</a>';
            echo '</div>';
        } else {
            showError('❌ Archivo accesible pero contenido incorrecto', [
                'URL: ' . $publicUrl,
                'Contenido esperado: ' . $key,
                'Contenido obtenido: ' . substr($response, 0, 32)
            ]);
        }
    } else {
        showWarning('⚠️ Archivo no accesible (HTTP ' . $httpCode . ')', [
            'URL: ' . $publicUrl,
            'Puede ser normal en entorno local',
            'En producción, debe ser accesible públicamente'
        ]);
    }
    
    // 5. Probar endpoint de IndexNow
    echo '<div class="test-step">5️⃣ Probando conexión con IndexNow API...</div>';
    
    $host = parse_url($baseUrl, PHP_URL_HOST);
    $testUrls = [$baseUrl . '/'];
    
    $postData = json_encode([
        'host' => $host,
        'key' => $key,
        'urlList' => $testUrls
    ]);
    
    $ch = curl_init('https://api.indexnow.org/indexnow');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=utf-8'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        showError('❌ Error de conexión', ['Error: ' . $curlError]);
        return;
    }
    
    echo '<div class="test-result test-info">';
    echo '<strong>HTTP Status Code:</strong> <code>' . $httpCode . '</code>';
    echo '</div>';
    
    if ($httpCode === 200 || $httpCode === 202) {
        showSuccess('✅ IndexNow aceptó la notificación (HTTP ' . $httpCode . ')');
        showInfo('📋 IndexNow notifica automáticamente a:', [
            '✓ Bing (Microsoft)',
            '✓ Yandex (Rusia)',
            '✓ Naver (Corea del Sur)',
            '✓ Seznam.cz (República Checa)'
        ]);
    } else {
        showError('❌ Error en IndexNow (HTTP ' . $httpCode . ')', [
            'Respuesta: ' . $response,
            'Códigos exitosos: 200 (OK) o 202 (Accepted)'
        ]);
    }
}

/**
 * Helpers para mostrar resultados
 */
function showSuccess($message) {
    echo '<div class="test-result test-success"><i class="bi bi-check-circle me-2"></i>' . $message . '</div>';
}

function showError($message, $details = []) {
    echo '<div class="test-result test-error">';
    echo '<i class="bi bi-x-circle me-2"></i><strong>' . $message . '</strong>';
    if (!empty($details)) {
        echo '<ul class="mt-2 mb-0">';
        foreach ($details as $detail) {
            echo '<li>' . $detail . '</li>';
        }
        echo '</ul>';
    }
    echo '</div>';
}

function showWarning($message, $details = []) {
    echo '<div class="test-result test-warning">';
    echo '<i class="bi bi-exclamation-triangle me-2"></i><strong>' . $message . '</strong>';
    if (!empty($details)) {
        echo '<ul class="mt-2 mb-0">';
        foreach ($details as $detail) {
            echo '<li>' . $detail . '</li>';
        }
        echo '</ul>';
    }
    echo '</div>';
}

function showInfo($message, $details = []) {
    echo '<div class="test-result test-info">';
    echo '<i class="bi bi-info-circle me-2"></i><strong>' . $message . '</strong>';
    if (!empty($details)) {
        echo '<ul class="mt-2 mb-0">';
        foreach ($details as $detail) {
            echo '<li>' . $detail . '</li>';
        }
        echo '</ul>';
    }
    echo '</div>';
}
?>
