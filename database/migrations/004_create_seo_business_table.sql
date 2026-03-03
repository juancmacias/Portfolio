-- Migración: tabla dedicada para configuración SEO de negocio
-- Fecha: 2 de marzo de 2026
-- Versión: 1.0
--
-- Sustituye el almacenamiento de seo_business_json en system_config
-- por una tabla estructurada con columnas tipadas.
-- La tabla tiene una única fila activa (is_active = 1, id = 1).

CREATE TABLE IF NOT EXISTS seo_business (
    id              INT AUTO_INCREMENT PRIMARY KEY,

    -- Identidad
    name            VARCHAR(255)    NOT NULL DEFAULT 'JCMS - Soluciones Full Stack con IA Generativa',
    description     TEXT,
    service_type    VARCHAR(100)    DEFAULT 'Full Stack Development',
    price_range     VARCHAR(20)     DEFAULT '€€',

    -- Contacto
    phone           VARCHAR(50)     DEFAULT '+34618309775',
    email           VARCHAR(255)    DEFAULT 'juancmaciassalvador@gmail.com',

    -- Dirección
    street_address  VARCHAR(255)    DEFAULT 'Calle de Padre Oltra',
    address_locality  VARCHAR(100)  DEFAULT 'Madrid',
    address_region    VARCHAR(100)  DEFAULT 'Comunidad de Madrid',
    postal_code       VARCHAR(20)   DEFAULT '28019',
    address_country   CHAR(2)       DEFAULT 'ES',

    -- Coordenadas geo (GeoCoordinates)
    geo_latitude    DECIMAL(9,6)    DEFAULT 40.386100,
    geo_longitude   DECIMAL(9,6)    DEFAULT -3.716100,

    -- Perfiles externos (JSON array de URLs)
    same_as         JSON,

    -- Control
    is_active       TINYINT(1)      NOT NULL DEFAULT 1,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar fila inicial con los valores actuales del portfolio
-- (solo si la tabla estaba vacía)
INSERT INTO seo_business (
    id, name, description, service_type, price_range,
    phone, email,
    street_address, address_locality, address_region, postal_code, address_country,
    geo_latitude, geo_longitude,
    same_as
)
SELECT
    1,
    'JCMS - Soluciones Full Stack con IA Generativa',
    'Desarrollador Full Stack especializado en React, PHP y soluciones con Inteligencia Artificial Generativa.',
    'Full Stack Development',
    '€€',
    '+34618309775',
    'juancmaciassalvador@gmail.com',
    'Calle de Padre Oltra',
    'Madrid',
    'Comunidad de Madrid',
    '28019',
    'ES',
    40.386100,
    -3.716100,
    JSON_ARRAY(
        'https://www.linkedin.com/in/juancarlosmacias/',
        'https://github.com/juancmacias',
        'https://maps.app.goo.gl/eb43KR6oPFGrNgAn9',
        'https://play.google.com/store/apps/dev?id=7098282899285176966',
        'https://www.instagram.com/jcms_madrid/'
    )
WHERE NOT EXISTS (SELECT 1 FROM seo_business WHERE id = 1);
