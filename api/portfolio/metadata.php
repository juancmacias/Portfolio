<?php
/**
 * Endpoint de Metadatos - API Portfolio
 * Obtiene información general del portfolio y metadatos
 * URL: /api/portfolio/metadata.php
 */

// Cargar configuración de la API
require_once __DIR__ . '/config.php';

try {
    // Leer archivo de metadatos si existe
    $metadata_file = __DIR__ . '/metadata.json';
    $metadata = [];
    
    if (file_exists($metadata_file)) {
        $metadata_content = file_get_contents($metadata_file);
        if ($metadata_content !== false) {
            $metadata = json_decode($metadata_content, true) ?? [];
        }
    }
    
    // Obtener información del sistema
    $system_config = get_system_config();
    $api_info = get_api_info();
    
    // Metadatos por defecto del portfolio
    $default_metadata = [
        'title' => 'Portfolio JCMS',
        'description' => 'Portfolio personal de Juan Carlos Macías',
        'author' => 'Juan Carlos Macías',
        'keywords' => ['portfolio', 'desarrollo web', 'react', 'php', 'javascript'],
        'lang' => 'es',
        'theme_color' => '#0070f3',
        'background_color' => '#ffffff',
        'social' => [
            'github' => 'https://github.com/juancmacias',
            'linkedin' => 'https://linkedin.com/in/juancmacias',
            'email' => 'contacto@juancmacias.com'
        ],
        'technologies' => [
            'frontend' => ['React', 'JavaScript', 'CSS3', 'HTML5'],
            'backend' => ['PHP', 'MySQL', 'Apache'],
            'tools' => ['Git', 'VS Code', 'Figma']
        ],
        'seo' => [
            'canonical_url' => 'https://portfolio.juancmacias.com',
            'og_image' => '/assets/og-image.jpg',
            'robots' => 'index, follow'
        ]
    ];
    
    // Combinar metadatos por defecto con los del archivo
    $final_metadata = array_merge($default_metadata, $metadata);
    
    // Añadir información dinámica
    $final_metadata['version'] = $system_config['app_version'];
    $final_metadata['last_updated'] = date('Y-m-d H:i:s');
    $final_metadata['api_info'] = $api_info;
    
    api_response(true, $final_metadata, 'Metadatos obtenidos correctamente');
    
} catch (Exception $e) {
    api_error('Error interno del servidor', 500, $e->getMessage());
}
?>
