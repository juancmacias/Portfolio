<?php
/**
 * ========================================
 * API ENDPOINT: NOTIFICAR SITEMAP A BUSCADORES
 * ========================================
 * 
 * Envía notificaciones de sitemap a:
 * - Google Search Console
 * - Bing Webmaster Tools
 * - IndexNow (Bing, Yandex, Naver, Seznam)
 * 
 * Método: POST
 * Requiere: Autenticación admin
 * Respuesta: JSON
 * 
 * @author Sistema Portfolio JCMS
 * @version 1.0.0
 */

define('ADMIN_ACCESS', true);
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../classes/SearchEngineNotifier.php';
require_once __DIR__ . '/../classes/SitemapGenerator.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS request (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Verificar autenticación
$auth = new AdminAuth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized: Login required'
    ]);
    exit;
}

// Solo POST permitido
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed. Use POST.'
    ]);
    exit;
}

try {
    // Detectar entorno (local vs producción)
    $isLocal = strpos($_SERVER['HTTP_HOST'], 'localhost') !== false 
               || strpos($_SERVER['HTTP_HOST'], 'perfil.in') !== false;
    
    $baseUrl = $isLocal 
        ? 'http://www.perfil.in' 
        : 'https://www.juancarlosmacias.es';
    
    // Log del inicio
    error_log("Search Engine Notification: Starting process for {$baseUrl}");
    
    // 1. Verificar que existe el sitemap
    $sitemapPath = $_SERVER['DOCUMENT_ROOT'] . '/sitemap.xml';
    
    if (!file_exists($sitemapPath)) {
        throw new Exception('Sitemap not found. Generate the sitemap first from the admin panel.');
    }
    
    // 2. Crear instancia de SitemapGenerator para extraer URLs
    $generator = new SitemapGenerator($baseUrl);
    
    // Extraer URLs del sitemap
    $urls = $generator->extractUrlsFromSitemap($sitemapPath);
    
    if (empty($urls)) {
        throw new Exception('No URLs found in sitemap. The sitemap might be empty or corrupted.');
    }
    
    error_log("Search Engine Notification: Found " . count($urls) . " URLs in sitemap");
    
    // 3. Notificar a todos los buscadores
    $notifier = new SearchEngineNotifier($baseUrl);
    $result = $notifier->notifyAll($urls);
    
    // 4. Log del resultado
    if ($result['success']) {
        error_log("Search Engine Notification: Success - {$result['providers_success']}/{$result['providers_total']} providers notified");
    } else {
        error_log("Search Engine Notification: Failed - All providers failed");
    }
    
    // 5. Respuesta JSON
    http_response_code(200);
    echo json_encode($result, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    error_log("Search Engine Notification Error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'providers_total' => 0,
        'providers_success' => 0,
        'results' => []
    ], JSON_PRETTY_PRINT);
}
