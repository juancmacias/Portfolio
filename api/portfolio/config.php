<?php
/**
 * Configuración de API - Portfolio Public API
 * Configuración específica para los endpoints públicos
 * 
 * @package Portfolio API
 * @author Juan Carlos Macías
 * @version 1.0.5
 */

// Debug: Log de inicio de config
error_log("=== CONFIG API START ===");

// Headers comunes para la API - CORS simplificado y directo
header('Content-Type: application/json; charset=utf-8');

// CORS directo - sin complicaciones
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 3600');

// Debug: Log de headers enviados
error_log("CORS headers sent");

// Manejo de preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Cargar configuración del admin (para acceso a base de datos)
define('ADMIN_ACCESS', true);

try {
    $config_path = __DIR__ . '/../../admin/config/config.local.php';
    $database_path = __DIR__ . '/../../admin/config/database.php';
    
    error_log("Loading config from: " . $config_path);
    error_log("Loading database from: " . $database_path);
    
    if (!file_exists($config_path)) {
        throw new Exception("Config file not found: " . $config_path);
    }
    if (!file_exists($database_path)) {
        throw new Exception("Database file not found: " . $database_path);
    }
    
    require_once $config_path;
    require_once $database_path;
    
    error_log("Config loaded successfully");
    
} catch (Exception $e) {
    error_log("ERROR loading config: " . $e->getMessage());
    // Enviar error pero con headers CORS
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Configuration error: ' . $e->getMessage()
    ]);
    exit();
}

/**
 * Respuesta JSON estándar para la API
 */
function api_response($success = true, $data = null, $message = '', $code = 200) {
    http_response_code($code);
    
    $response = [
        'success' => $success,
        'timestamp' => date('Y-m-d H:i:s'),
        'data' => $data
    ];
    
    if ($message) {
        $response['message'] = $message;
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Manejo de errores para la API
 */
function api_error($message, $code = 400, $details = null) {
    $data = $details ? ['error_details' => $details] : null;
    api_response(false, $data, $message, $code);
}

/**
 * Validar parámetros requeridos
 */
function validate_required_params($params, $required) {
    $missing = [];
    foreach ($required as $param) {
        if (!isset($params[$param]) || empty($params[$param])) {
            $missing[] = $param;
        }
    }
    
    if (!empty($missing)) {
        api_error('Parámetros requeridos faltantes: ' . implode(', ', $missing), 400);
    }
}

/**
 * Obtener configuración del sistema para la API
 */
function get_api_info() {
    $system_config = get_system_config();
    return [
        'name' => $system_config['app_name'],
        'version' => $system_config['app_version'],
        'environment' => is_development() ? 'development' : 'production'
    ];
}
?>