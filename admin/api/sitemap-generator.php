<?php
/**
 * ========================================
 * API PARA GENERACIÓN DE SITEMAP
 * ========================================
 * 
 * Endpoint para generar sitemap.xml automáticamente
 * desde el panel de administración.
 * 
 * Métodos soportados:
 * - POST: generate - Genera nuevo sitemap
 * - GET: info - Información del sitemap actual
 * 
 * ========================================
 */

// Configuración de headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Incluir dependencias
require_once '../classes/SitemapGenerator.php';
require_once '../classes/SitemapGenerator.php';

// Verificar autenticación (solo usuarios logueados pueden generar sitemap)
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Acceso no autorizado. Inicia sesión en el panel de administración.'
    ]);
    exit();
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';

    // Configurar URL base según el entorno
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false || strpos($host, 'perfil.in') !== false) {
        // Entorno local - usar URL local o simulación
        $baseUrl = 'http://' . $host;
        $isLocal = true;
    } else {
        // Entorno producción
        $baseUrl = 'https://www.juancarlosmacias.es';
        $isLocal = false;
    }
    
    // Crear instancia del generador
    $generator = new SitemapGenerator($baseUrl, 3); // max depth 3

    switch ($method) {
        case 'POST':
            if ($action === 'generate') {
                handleGenerate($generator, $baseUrl, $isLocal);
            } elseif ($action === 'notify') {
                handleNotifySearchEngines($generator, $baseUrl, $isLocal);
            } else {
                throw new Exception('Acción POST no válida');
            }
            break;

        case 'GET':
            if ($action === 'info') {
                handleInfo($generator);
            } elseif ($action === 'read') {
                handleReadSitemap();
            } else {
                throw new Exception('Acción GET no válida');
            }
            break;

        default:
            throw new Exception('Método HTTP no soportado');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
}

/**
 * Genera un sitemap de ejemplo para entorno local
 * @param string $sitemapPath Ruta donde guardar el sitemap
 * @param string $baseUrl URL base del sitio
 * @return array Resultado de la generación
 */
function generateLocalSitemap($sitemapPath, $baseUrl) 
{
    $startTime = microtime(true);
    
    // URLs de ejemplo para el sitemap local - SOLO URLs REALES
    $exampleUrls = [
        [
            'url' => $baseUrl . '/',
            'lastmod' => date('Y-m-d'),
            'changefreq' => 'weekly',
            'priority' => '1.0'
        ],
        [
            'url' => $baseUrl . '/about',
            'lastmod' => date('Y-m-d'),
            'changefreq' => 'yearly',
            'priority' => '0.8'
        ],
        [
            'url' => $baseUrl . '/project',
            'lastmod' => date('Y-m-d'),
            'changefreq' => 'monthly',
            'priority' => '0.8'
        ],
        [
            'url' => $baseUrl . '/articles',
            'lastmod' => date('Y-m-d'),
            'changefreq' => 'weekly',
            'priority' => '0.7'
        ],
        [
            'url' => $baseUrl . '/resume',
            'lastmod' => date('Y-m-d'),
            'changefreq' => 'yearly',
            'priority' => '0.7'
        ],
        [
            'url' => $baseUrl . '/politics',
            'lastmod' => date('Y-m-d'),
            'changefreq' => 'yearly',
            'priority' => '0.5'
        ]
    ];
    
    // Generar XML
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    $xml .= '  <!-- Sitemap generado en entorno LOCAL para desarrollo -->' . "\n";
    
    foreach ($exampleUrls as $urlData) {
        $xml .= "  <url>\n";
        $xml .= "    <loc>" . htmlspecialchars($urlData['url']) . "</loc>\n";
        $xml .= "    <lastmod>" . $urlData['lastmod'] . "</lastmod>\n";
        $xml .= "    <changefreq>" . $urlData['changefreq'] . "</changefreq>\n";
        $xml .= "    <priority>" . $urlData['priority'] . "</priority>\n";
        $xml .= "  </url>\n";
    }
    
    $xml .= '</urlset>';
    
    // Guardar archivo
    $saved = file_put_contents($sitemapPath, $xml);
    
    $endTime = microtime(true);
    $executionTime = round($endTime - $startTime, 2);
    
    return [
        'success' => $saved !== false,
        'message' => $saved ? 'Sitemap de desarrollo generado exitosamente' : 'Error al guardar sitemap',
        'urls_found' => count($exampleUrls),
        'file_path' => $sitemapPath,
        'file_size' => $saved ? formatBytes($saved) : '0 bytes',
        'execution_time' => $executionTime . ' segundos',
        'urls' => $exampleUrls,
        'note' => 'Sitemap generado para entorno de desarrollo local'
    ];
}

/**
 * Formatea bytes en formato legible
 */
function formatBytes($bytes) 
{
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

/**
 * Maneja la generación de sitemap
 * @param SitemapGenerator $generator Instancia del generador
 * @param string $baseUrl URL base del sitio
 * @param bool $isLocal Si estamos en entorno local
 */
function handleGenerate($generator, $baseUrl, $isLocal) 
{
    
    try {
        // Validar si se puede escribir en directorio raíz
        $rootDir = realpath(__DIR__ . '/../../');
        $sitemapPath = $rootDir . DIRECTORY_SEPARATOR . 'sitemap.xml';
        
        if (!is_writable($rootDir)) {
            throw new Exception('No se tienen permisos de escritura en el directorio raíz');
        }

        // Si estamos en local, generar sitemap de ejemplo
        if ($isLocal) {
            $result = generateLocalSitemap($sitemapPath, $baseUrl);
        } else {
            // Generar sitemap real
            $result = $generator->generateSitemap();
        }
        
        // Agregar información adicional
        $result['generated_at'] = date('Y-m-d H:i:s');
        $result['base_url'] = $baseUrl;
        $result['environment'] = $isLocal ? 'local' : 'production';
        
        if ($result['success']) {
            // Log de éxito
            error_log("Sitemap generado exitosamente: {$result['urls_found']} URLs encontradas");
            
            http_response_code(200);
            echo json_encode($result);
        } else {
            http_response_code(500);
            echo json_encode($result);
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error generando sitemap: ' . $e->getMessage()
        ]);
    }
}

/**
 * Maneja la consulta de información del sitemap
 * @param SitemapGenerator $generator Instancia del generador
 */
function handleInfo($generator) 
{
    try {
        $info = $generator->getSitemapInfo();
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $info
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error obteniendo información: ' . $e->getMessage()
        ]);
    }
}

/**
 * Lee y parsea el sitemap.xml actual
 */
function handleReadSitemap() 
{
    try {
        $rootDir = realpath(__DIR__ . '/../../');
        $sitemapPath = $rootDir . DIRECTORY_SEPARATOR . 'sitemap.xml';
        
        if (!file_exists($sitemapPath)) {
            echo json_encode([
                'success' => false,
                'message' => 'No existe sitemap.xml. Genere uno primero.',
                'exists' => false
            ]);
            return;
        }
        
        // Leer y parsear el XML
        $xmlContent = file_get_contents($sitemapPath);
        $xml = simplexml_load_string($xmlContent);
        
        if ($xml === false) {
            throw new Exception('Error parseando el archivo XML');
        }
        
        $urls = [];
        foreach ($xml->url as $url) {
            $urls[] = [
                'loc' => (string)$url->loc,
                'lastmod' => (string)$url->lastmod,
                'changefreq' => (string)$url->changefreq,
                'priority' => (string)$url->priority
            ];
        }
        
        $fileStats = stat($sitemapPath);
        
        echo json_encode([
            'success' => true,
            'exists' => true,
            'urls' => $urls,
            'total_urls' => count($urls),
            'file_size' => formatBytes(filesize($sitemapPath)),
            'last_modified' => date('d M Y, H:i', $fileStats['mtime']),
            'file_path' => $sitemapPath
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error leyendo sitemap: ' . $e->getMessage()
        ]);
    }
}

/**
 * Maneja la notificación a buscadores
 * @param SitemapGenerator $generator Instancia del generador
 * @param string $baseUrl URL base del sitio
 * @param bool $isLocal Si estamos en entorno local
 */
function handleNotifySearchEngines($generator, $baseUrl, $isLocal) 
{
    try {
        if ($isLocal) {
            // En entorno local, simular notificaciones
            echo json_encode([
                'success' => true,
                'message' => 'Simulación de notificaciones en entorno local',
                'total_engines' => 3,
                'successful_notifications' => 3,
                'results' => [
                    'Google' => [
                        'success' => true,
                        'message' => 'Simulado - Notificación enviada correctamente',
                        'status_code' => 200
                    ],
                    'Bing' => [
                        'success' => true,
                        'message' => 'Simulado - Notificación enviada correctamente',
                        'status_code' => 200
                    ],
                    'Yandex' => [
                        'success' => true,
                        'message' => 'Simulado - Notificación enviada correctamente',
                        'status_code' => 200
                    ]
                ],
                'sitemap_url' => $baseUrl . '/sitemap.xml',
                'environment' => 'local',
                'note' => 'Las notificaciones no se envían realmente en entorno de desarrollo'
            ]);
        } else {
            // Entorno producción - enviar notificaciones reales
            $result = $generator->notifySearchEngines();
            
            $result['success'] = $result['successful_notifications'] > 0;
            $result['message'] = $result['successful_notifications'] > 0 
                ? "Notificaciones enviadas: {$result['successful_notifications']} de {$result['total_engines']}"
                : 'No se pudieron enviar las notificaciones';
            $result['environment'] = 'production';
            
            echo json_encode($result);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'environment' => $isLocal ? 'local' : 'production'
        ]);
    }
}
?>