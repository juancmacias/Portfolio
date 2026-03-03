<?php
/**
 * Endpoint de Metadatos - API Portfolio
 * Obtiene información general del portfolio y metadatos
 * URL: /api/portfolio/metadata.php
 */

// Cargar configuración de la API
require_once __DIR__ . '/config.php';

try {
    header('Cache-Control: public, max-age=600, s-maxage=600, stale-while-revalidate=600');

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

    $default_seo_business = [
        'name'            => 'JCMS - Soluciones Full Stack con IA Generativa',
        'phone'           => '+34618309775',
        'email'           => 'juancmaciassalvador@gmail.com',
        'description'     => 'Desarrollador Full Stack especializado en React, PHP y soluciones con Inteligencia Artificial Generativa.',
        'serviceType'     => 'Full Stack Development',
        'priceRange'      => '€€',
        'streetAddress'   => 'Calle de Padre Oltra',
        'addressLocality' => 'Madrid',
        'addressRegion'   => 'Comunidad de Madrid',
        'postalCode'      => '28019',
        'addressCountry'  => 'ES',
        'geo'             => ['latitude' => 40.3861, 'longitude' => -3.7161],
        'sameAs' => [
            'https://www.linkedin.com/in/juancarlosmacias/',
            'https://github.com/juancmacias',
            'https://maps.app.goo.gl/eb43KR6oPFGrNgAn9',
            'https://play.google.com/store/apps/dev?id=7098282899285176966',
            'https://www.instagram.com/jcms_madrid/'
        ]
    ];

    $seo_business = $default_seo_business;

    try {
        $db  = Database::getInstance();
        $row = $db->fetchOne(
            "SELECT name, description, service_type, price_range,
                    phone, email,
                    street_address, address_locality, address_region, postal_code, address_country,
                    geo_latitude, geo_longitude, same_as
             FROM seo_business
             WHERE is_active = 1
             ORDER BY id ASC
             LIMIT 1"
        );

        if ($row && !empty($row['name'])) {
            $same_as_raw = [];
            if (!empty($row['same_as'])) {
                $decoded_sa = json_decode($row['same_as'], true);
                if (is_array($decoded_sa)) {
                    $same_as_raw = $decoded_sa;
                }
            }

            $seo_business = array_merge($default_seo_business, [
                'name'            => $row['name'],
                'description'     => $row['description']      ?? '',
                'serviceType'     => $row['service_type']     ?? '',
                'priceRange'      => $row['price_range']      ?? '',
                'phone'           => $row['phone'],
                'email'           => $row['email'],
                'streetAddress'   => $row['street_address']   ?? '',
                'addressLocality' => $row['address_locality'] ?? '',
                'addressRegion'   => $row['address_region']   ?? '',
                'postalCode'      => $row['postal_code']      ?? '',
                'addressCountry'  => $row['address_country']  ?? 'ES',
                'geo'             => [
                    'latitude'  => (float)($row['geo_latitude']  ?? 40.3861),
                    'longitude' => (float)($row['geo_longitude'] ?? -3.7161)
                ],
                'sameAs' => $same_as_raw ?: $default_seo_business['sameAs']
            ]);
        }
    } catch (Exception $e) {
        // Tabla no disponible — fallback silencioso
        error_log('metadata.php: seo_business table unavailable — ' . $e->getMessage());
    }

    // Sanitizar sameAs
    $same_as = [];
    if (isset($seo_business['sameAs']) && is_array($seo_business['sameAs'])) {
        foreach ($seo_business['sameAs'] as $url) {
            if (is_string($url) && filter_var($url, FILTER_VALIDATE_URL)) {
                $same_as[] = $url;
            }
        }
    }
    $seo_business['sameAs'] = array_values(array_unique($same_as));
    
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
    $final_metadata['seo_business'] = $seo_business;
    
    api_response(true, $final_metadata, 'Metadatos obtenidos correctamente');
    
} catch (Exception $e) {
    api_error('Error interno del servidor', 500, $e->getMessage());
}
?>
