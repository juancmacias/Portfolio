<?php
/**
 * Endpoint dedicado de configuración SEO de negocio
 * Devuelve únicamente los datos de Business SEO (ProfessionalService JSON-LD)
 * Optimizado para ser ultraligero y altamente cacheable
 *
 * URL: /api/portfolio/seo-config.php
 * Cache: 1 hora en CDN/proxy, stale-while-revalidate 24h
 */

require_once __DIR__ . '/config.php';

// Cache agresivo: este endpoint cambia raramente
header('Cache-Control: public, max-age=3600, s-maxage=3600, stale-while-revalidate=86400');
header('Vary: Accept-Encoding');

/**
 * Datos por defecto (fallback si la BD no está disponible)
 * Estos datos se deben mantener sincronizados con BUSINESS_SEO en seoBusiness.js
 */
function get_default_seo_business(): array
{
    return [
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
        'geo'             => [
            'latitude'  => 40.3861,
            'longitude' => -3.7161
        ],
        'sameAs' => [
            'https://www.linkedin.com/in/juancarlosmacias/',
            'https://github.com/juancmacias',
            'https://maps.app.goo.gl/eb43KR6oPFGrNgAn9',
            'https://play.google.com/store/apps/dev?id=7098282899285176966',
            'https://www.instagram.com/jcms_madrid/'
        ]
    ];
}

/**
 * Sanea y normaliza los datos de negocio
 */
function sanitize_seo_business(array $data): array
{
    $defaults = get_default_seo_business();
    $result   = array_merge($defaults, $data);

    // Sanitizar campos de texto
    $textFields = ['name', 'phone', 'email', 'description', 'serviceType', 'priceRange',
                   'streetAddress', 'addressLocality', 'addressRegion', 'postalCode', 'addressCountry'];
    foreach ($textFields as $field) {
        if (isset($result[$field])) {
            $result[$field] = trim(strip_tags((string) $result[$field]));
        }
    }

    // Asegurarse de que el país sea 2 caracteres en mayúsculas
    $result['addressCountry'] = strtoupper(substr($result['addressCountry'] ?? 'ES', 0, 2));

    // Sanitizar sameAs: solo URLs válidas, sin duplicados
    $sameAs = is_array($result['sameAs']) ? $result['sameAs'] : $defaults['sameAs'];
    $result['sameAs'] = array_values(array_unique(
        array_filter($sameAs, fn($url) => is_string($url) && filter_var(trim($url), FILTER_VALIDATE_URL))
    ));

    // Sanitizar geo
    if (isset($result['geo']) && is_array($result['geo'])) {
        $result['geo'] = [
            'latitude'  => (float) ($result['geo']['latitude']  ?? $defaults['geo']['latitude']),
            'longitude' => (float) ($result['geo']['longitude'] ?? $defaults['geo']['longitude'])
        ];
    } else {
        $result['geo'] = $defaults['geo'];
    }

    return $result;
}

/**
 * Convierte una fila de la tabla seo_business al formato de la API
 */
function row_to_seo_config(array $row): array
{
    // same_as viene como JSON string desde MySQL
    $sameAs = [];
    if (!empty($row['same_as'])) {
        $decoded = json_decode($row['same_as'], true);
        if (is_array($decoded)) {
            $sameAs = $decoded;
        }
    }

    return sanitize_seo_business([
        'name'            => $row['name']            ?? '',
        'description'     => $row['description']     ?? '',
        'serviceType'     => $row['service_type']    ?? '',
        'priceRange'      => $row['price_range']     ?? '',
        'phone'           => $row['phone']           ?? '',
        'email'           => $row['email']           ?? '',
        'streetAddress'   => $row['street_address']  ?? '',
        'addressLocality' => $row['address_locality'] ?? '',
        'addressRegion'   => $row['address_region']  ?? '',
        'postalCode'      => $row['postal_code']     ?? '',
        'addressCountry'  => $row['address_country'] ?? 'ES',
        'geo'             => [
            'latitude'  => (float)($row['geo_latitude']  ?? 40.3861),
            'longitude' => (float)($row['geo_longitude'] ?? -3.7161)
        ],
        'sameAs' => $sameAs
    ]);
}

try {
    $config = get_default_seo_business();

    // Leer de la tabla dedicada seo_business
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
            $config = row_to_seo_config($row);
        }
    } catch (Exception $dbEx) {
        // BD no disponible — silenciosamente se usa el fallback
        error_log('seo-config.php: DB unavailable, using default — ' . $dbEx->getMessage());
    }

    api_response(true, $config, 'Configuración SEO obtenida correctamente');

} catch (Exception $e) {
    error_log('seo-config.php error: ' . $e->getMessage());
    // Incluso ante un error crítico, devolvemos el fallback para no romper el JSON-LD
    api_response(true, get_default_seo_business(), 'Configuración SEO (fallback)');
}
