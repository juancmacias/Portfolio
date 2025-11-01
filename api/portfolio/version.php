<?php
/**
 * Endpoint de información de versión - API Portfolio
 * URL: /api/portfolio/version.php
 */

// Cargar configuración de la API
require_once __DIR__ . '/config.php';

try {
    // Obtener información del sistema y versión
    $system_config = get_system_config();
    $version_info = get_version_info();
    $api_info = get_api_info();
    
    // Respuesta de la API
    api_response(true, [
        'api' => $api_info,
        'system' => [
            'name' => $system_config['app_name'],
            'version' => $system_config['app_version'],
            'timezone' => $system_config['timezone'],
            'environment' => is_development() ? 'development' : 'production'
        ],
        'version_details' => $version_info
    ], 'Información de versión obtenida correctamente');
    
} catch (Exception $e) {
    api_error('Error interno del servidor', 500, $e->getMessage());
}
?>