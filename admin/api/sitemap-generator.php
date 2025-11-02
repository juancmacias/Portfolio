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
require_once '../includes/config.php';
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

    // Configurar URL base
    $baseUrl = 'https://www.juancarlosmacias.es';
    
    // Crear instancia del generador
    $generator = new SitemapGenerator($baseUrl, 3); // max depth 3

    switch ($method) {
        case 'POST':
            if ($action === 'generate') {
                handleGenerate($generator);
            } else {
                throw new Exception('Acción POST no válida');
            }
            break;

        case 'GET':
            if ($action === 'info') {
                handleInfo($generator);
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
 * Maneja la generación de sitemap
 * @param SitemapGenerator $generator Instancia del generador
 */
function handleGenerate($generator) 
{
    try {
        // Validar si se puede escribir en directorio raíz
        $rootDir = realpath(__DIR__ . '/../../');
        $sitemapPath = $rootDir . DIRECTORY_SEPARATOR . 'sitemap.xml';
        
        if (!is_writable($rootDir)) {
            throw new Exception('No se tienen permisos de escritura en el directorio raíz');
        }

        // Generar sitemap
        $result = $generator->generateSitemap();
        
        // Agregar información adicional
        $result['generated_at'] = date('Y-m-d H:i:s');
        $result['base_url'] = 'https://www.juancarlosmacias.es';
        
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
?>