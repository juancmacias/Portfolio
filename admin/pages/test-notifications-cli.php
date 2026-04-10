<?php
/**
 * ========================================
 * SCRIPT CLI: TEST NOTIFICACIONES
 * ========================================
 * 
 * Ejecutar desde línea de comandos para probar configuraciones
 * sin necesidad de navegador.
 * 
 * Uso:
 *   php test-notifications-cli.php [google|bing|indexnow|all]
 * 
 * Ejemplos:
 *   php test-notifications-cli.php google
 *   php test-notifications-cli.php all
 * 
 * @author Sistema Portfolio JCMS
 * @version 1.0.0
 */

// Verificar que se ejecuta desde CLI
if (php_sapi_name() !== 'cli') {
    die("Este script solo puede ejecutarse desde la línea de comandos.\n");
}

define('ADMIN_ACCESS', true);

// Cargar configuración
$configPath = __DIR__ . '/../config/config.local.php';
if (file_exists($configPath)) {
    require_once $configPath;
}

require_once __DIR__ . '/../classes/SearchEngineNotifier.php';
require_once __DIR__ . '/../classes/Providers/GoogleSearchConsoleProvider.php';
require_once __DIR__ . '/../classes/Providers/BingWebmasterProvider.php';
require_once __DIR__ . '/../classes/Providers/IndexNowProvider.php';

// Colores para terminal
class CliColors {
    const RESET = "\033[0m";
    const GREEN = "\033[32m";
    const RED = "\033[31m";
    const YELLOW = "\033[33m";
    const BLUE = "\033[34m";
    const CYAN = "\033[36m";
    const BOLD = "\033[1m";
}

// Detectar entorno
$isLocal = gethostname() === 'localhost' || strpos(gethostname(), 'local') !== false;
$baseUrl = $isLocal ? 'http://www.perfil.in' : 'https://www.juancarlosmacias.es';

// Parsear argumentos
$testType = $argv[1] ?? 'all';

echo CliColors::BOLD . CliColors::CYAN . "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║   TEST DE NOTIFICACIONES A BUSCADORES - PORTFOLIO JCMS    ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo CliColors::RESET . "\n";

echo "Entorno: " . ($isLocal ? CliColors::YELLOW . "LOCAL" : CliColors::GREEN . "PRODUCCIÓN") . CliColors::RESET . "\n";
echo "URL Base: " . CliColors::CYAN . $baseUrl . CliColors::RESET . "\n\n";

// Ejecutar tests
switch (strtolower($testType)) {
    case 'google':
        testGoogle($baseUrl);
        break;
    
    case 'bing':
        testBing($baseUrl);
        break;
    
    case 'indexnow':
        testIndexNow($baseUrl);
        break;
    
    case 'all':
    default:
        testGoogle($baseUrl);
        echo "\n" . str_repeat("─", 60) . "\n\n";
        testBing($baseUrl);
        echo "\n" . str_repeat("─", 60) . "\n\n";
        testIndexNow($baseUrl);
        echo "\n" . str_repeat("─", 60) . "\n\n";
        showSummary();
        break;
}

echo "\n";

/**
 * Test Google Search Console
 */
function testGoogle($baseUrl) {
    global $testResults;
    
    printHeader('GOOGLE SEARCH CONSOLE API');
    
    $credentialsPath = dirname(__DIR__) . '/config/service-account-credentials.json';
    
    // 1. Verificar archivo
    printStep('Verificando credenciales...');
    
    if (!file_exists($credentialsPath)) {
        printError('Archivo de credenciales NO encontrado');
        printInfo('Ubicación esperada: ' . $credentialsPath);
        $testResults['google'] = false;
        return;
    }
    
    printSuccess('Archivo encontrado');
    
    // 2. Validar JSON
    printStep('Validando JSON...');
    
    $credentialsContent = file_get_contents($credentialsPath);
    $credentials = json_decode($credentialsContent, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        printError('JSON inválido: ' . json_last_error_msg());
        $testResults['google'] = false;
        return;
    }
    
    printSuccess('JSON válido');
    
    // 3. Verificar campos
    printStep('Verificando campos requeridos...');
    
    $requiredFields = ['type', 'project_id', 'private_key', 'client_email'];
    $missing = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($credentials[$field])) {
            $missing[] = $field;
        }
    }
    
    if (!empty($missing)) {
        printError('Campos faltantes: ' . implode(', ', $missing));
        $testResults['google'] = false;
        return;
    }
    
    printSuccess('Todos los campos presentes');
    printInfo('Project: ' . $credentials['project_id']);
    printInfo('Email: ' . $credentials['client_email']);
    
    // 4. Verificar librería
    printStep('Verificando Google Client Library...');
    
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
    foreach ($autoloadPaths as $path) {
        if (file_exists($path)) {
            $autoloadPath = $path;
            break;
        }
    }
    
    if (!$autoloadPath) {
        printError('Google Client Library NO instalada');
        printInfo('Solución: composer require google/apiclient');
        $testResults['google'] = false;
        return;
    }
    
    printInfo('Encontrado en: ' . basename(dirname($autoloadPath)));
    require_once $autoloadPath;
    
    if (!class_exists('Google\Client')) {
        printError('Clase Google\Client no encontrada');
        $testResults['google'] = false;
        return;
    }
    
    printSuccess('Librería instalada');
    
    // 5. Probar autenticación
    printStep('Probando autenticación...');
    
    try {
        $client = new Google\Client();
        $client->setAuthConfig($credentialsPath);
        $client->setScopes(['https://www.googleapis.com/auth/webmasters']);
        
        $client->fetchAccessTokenWithAssertion();
        $token = $client->getAccessToken();
        
        if (!$token) {
            printError('No se pudo generar Access Token');
            $testResults['google'] = false;
            return;
        }
        
        printSuccess('Access Token generado');
        printInfo('Expires in: ' . $token['expires_in'] . 's');
        
        $testResults['google'] = true;
        
        // 6. Información adicional
        printStep('Configuración en Search Console...');
        
        $domain = parse_url($baseUrl, PHP_URL_HOST);
        printWarning('Verificar manualmente:');
        printInfo('1. Ir a: https://search.google.com/search-console');
        printInfo('2. Agregar propiedad: sc-domain:' . $domain);
        printInfo('3. Agregar usuario: ' . $credentials['client_email']);
        printInfo('4. Permisos: PROPIETARIO (Owner)');
        
    } catch (Exception $e) {
        printError('Error: ' . $e->getMessage());
        $testResults['google'] = false;
    }
}

/**
 * Test Bing Webmaster
 */
function testBing($baseUrl) {
    global $testResults;
    
    printHeader('BING WEBMASTER TOOLS API');
    
    $apiKeyPath = dirname(__DIR__) . '/config/bing-api-key.txt';
    
    // 1. Verificar archivo
    printStep('Verificando API Key...');
    
    if (!file_exists($apiKeyPath)) {
        printError('Archivo de API Key NO encontrado');
        printInfo('Ubicación esperada: ' . $apiKeyPath);
        printInfo('Obtener en: https://www.bing.com/webmasters');
        $testResults['bing'] = false;
        return;
    }
    
    $apiKey = trim(file_get_contents($apiKeyPath));
    
    if (empty($apiKey)) {
        printError('API Key vacía');
        $testResults['bing'] = false;
        return;
    }
    
    printSuccess('API Key cargada (' . strlen($apiKey) . ' caracteres)');
    printInfo('Preview: ' . substr($apiKey, 0, 8) . '...' . substr($apiKey, -4));
    
    // 2. Probar endpoint
    printStep('Probando conexión con Bing API...');
    
    $endpoint = 'https://ssl.bing.com/webmaster/api.svc/json/SubmitUrlbatch?apikey=' . urlencode($apiKey);
    
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
        printError('Error cURL: ' . $curlError);
        $testResults['bing'] = false;
        return;
    }
    
    printInfo('HTTP Status: ' . $httpCode);
    
    if ($httpCode === 200) {
        printSuccess('Conexión exitosa');
        printInfo('Respuesta: ' . $response);
        $testResults['bing'] = true;
    } elseif ($httpCode === 401) {
        printError('Error 401: API Key inválida o expirada');
        printInfo('Solución: Regenerar API Key en Bing Webmaster Tools');
        $testResults['bing'] = false;
    } elseif ($httpCode === 410) {
        printError('Error 410: Gone');
        printWarning('Posibles causas:');
        printInfo('- Sitio no verificado en Bing Webmaster Tools');
        printInfo('- Sitio eliminado de Bing Webmaster');
        printInfo('- API Key de otra cuenta');
        $testResults['bing'] = false;
    } else {
        printError('Error HTTP ' . $httpCode);
        printInfo('Respuesta: ' . $response);
        $testResults['bing'] = false;
    }
}

/**
 * Test IndexNow
 */
function testIndexNow($baseUrl) {
    global $testResults;
    
    printHeader('INDEXNOW PROTOCOL');
    
    $keyPath = dirname(__DIR__) . '/config/indexnow-key.txt';
    
    // 1. Verificar archivo
    printStep('Verificando clave IndexNow...');
    
    if (!file_exists($keyPath)) {
        printWarning('IndexNow NO configurado');
        printInfo('Ejecutar: ' . $baseUrl . '/setup-indexnow.php');
        $testResults['indexnow'] = false;
        return;
    }
    
    $key = trim(file_get_contents($keyPath));
    
    if (empty($key)) {
        printError('Clave vacía');
        $testResults['indexnow'] = false;
        return;
    }
    
    printSuccess('Clave cargada (' . strlen($key) . ' caracteres)');
    printInfo('Preview: ' . substr($key, 0, 16) . '...');
    
    // 2. Verificar archivo público
    printStep('Verificando archivo público...');
    
    $publicKeyFile = $_SERVER['DOCUMENT_ROOT'] . '/' . $key . '.txt';
    
    if (!file_exists($publicKeyFile)) {
        printError('Archivo público NO encontrado');
        printInfo('Ubicación: ' . $publicKeyFile);
        $testResults['indexnow'] = false;
        return;
    }
    
    $publicContent = trim(file_get_contents($publicKeyFile));
    
    if ($publicContent !== $key) {
        printError('Contenido del archivo público no coincide');
        $testResults['indexnow'] = false;
        return;
    }
    
    printSuccess('Archivo público válido');
    
    // 3. Probar API
    printStep('Probando IndexNow API...');
    
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
        printError('Error cURL: ' . $curlError);
        $testResults['indexnow'] = false;
        return;
    }
    
    printInfo('HTTP Status: ' . $httpCode);
    
    if ($httpCode === 200 || $httpCode === 202) {
        printSuccess('IndexNow aceptó la notificación');
        printInfo('Notifica a: Bing, Yandex, Naver, Seznam');
        $testResults['indexnow'] = true;
    } else {
        printError('Error HTTP ' . $httpCode);
        printInfo('Respuesta: ' . $response);
        $testResults['indexnow'] = false;
    }
}

/**
 * Resumen final
 */
function showSummary() {
    global $testResults;
    
    printHeader('RESUMEN DE PRUEBAS');
    
    $total = count($testResults);
    $passed = count(array_filter($testResults));
    
    echo CliColors::BOLD;
    echo "Total: {$total} | Exitosos: {$passed} | Fallidos: " . ($total - $passed) . "\n";
    echo CliColors::RESET . "\n";
    
    foreach ($testResults as $provider => $result) {
        $icon = $result ? '✓' : '✗';
        $color = $result ? CliColors::GREEN : CliColors::RED;
        echo $color . $icon . ' ' . strtoupper($provider) . CliColors::RESET . "\n";
    }
    
    echo "\n";
    
    if ($passed === $total) {
        printSuccess('Todas las pruebas pasaron correctamente');
    } elseif ($passed > 0) {
        printWarning('Algunas pruebas fallaron - revisar configuración');
    } else {
        printError('Todas las pruebas fallaron - revisar documentación');
    }
    
    echo "\n";
    printInfo('Documentación: doc/GUIA-CONFIGURACION-NOTIFICACIONES.md');
}

/**
 * Funciones de output
 */
function printHeader($text) {
    echo "\n" . CliColors::BOLD . CliColors::BLUE . "═══ {$text} ═══" . CliColors::RESET . "\n\n";
}

function printStep($text) {
    echo CliColors::CYAN . "→ {$text}" . CliColors::RESET . "\n";
}

function printSuccess($text) {
    echo CliColors::GREEN . "  ✓ {$text}" . CliColors::RESET . "\n";
}

function printError($text) {
    echo CliColors::RED . "  ✗ {$text}" . CliColors::RESET . "\n";
}

function printWarning($text) {
    echo CliColors::YELLOW . "  ⚠ {$text}" . CliColors::RESET . "\n";
}

function printInfo($text) {
    echo CliColors::RESET . "    {$text}" . CliColors::RESET . "\n";
}

// Variable global para resultados
$testResults = [];
