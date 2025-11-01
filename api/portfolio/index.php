<?php
/**
 * Endpoint principal - API Portfolio
 * Lista todos los endpoints disponibles
 * URL: /api/portfolio/index.php o /api/portfolio/
 */

// Cargar configuración de la API
require_once __DIR__ . '/config.php';

try {
    $api_info = get_api_info();
    
    // Lista de endpoints disponibles
    $endpoints = [
        [
            'endpoint' => '/api/portfolio/',
            'method' => 'GET',
            'description' => 'Información general de la API',
            'example' => '/api/portfolio/'
        ],
        [
            'endpoint' => '/api/portfolio/version.php',
            'method' => 'GET', 
            'description' => 'Información de versión del sistema',
            'example' => '/api/portfolio/version.php'
        ],
        [
            'endpoint' => '/api/portfolio/metadata.php',
            'method' => 'GET',
            'description' => 'Metadatos del portfolio',
            'example' => '/api/portfolio/metadata.php'
        ],
        [
            'endpoint' => '/api/portfolio/projects.php',
            'method' => 'GET',
            'description' => 'Lista de proyectos del portfolio',
            'example' => '/api/portfolio/projects.php'
        ],
        [
            'endpoint' => '/api/portfolio/articles.php',
            'method' => 'GET',
            'description' => 'Lista de artículos (próximamente)',
            'example' => '/api/portfolio/articles.php'
        ]
    ];
    
    api_response(true, [
        'api' => $api_info,
        'description' => 'API pública del Portfolio para consumo del frontend React',
        'base_url' => '/api/portfolio/',
        'endpoints' => $endpoints,
        'usage' => [
            'content_type' => 'application/json',
            'cors' => 'habilitado',
            'rate_limit' => 'no implementado'
        ]
    ], 'API Portfolio - Información general');
    
} catch (Exception $e) {
    api_error('Error interno del servidor', 500, $e->getMessage());
}
?>