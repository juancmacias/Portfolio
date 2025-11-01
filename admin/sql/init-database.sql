-- Script de inicialización de la base de datos
-- Portfolio System - Juan Carlos Macías
-- Versión: 1.0.0

-- Crear tabla de artículos
CREATE TABLE IF NOT EXISTS articles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    content LONGTEXT NOT NULL,
    excerpt TEXT,
    meta_description VARCHAR(160),
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    author VARCHAR(100) DEFAULT 'Juan Carlos Macías',
    featured_image VARCHAR(500),
    tags TEXT,
    reading_time INT DEFAULT 1,
    views INT DEFAULT 0,
    ai_generated BOOLEAN DEFAULT FALSE,
    ai_model VARCHAR(50),
    ai_prompt TEXT,
    ai_tokens_used INT DEFAULT 0,
    ai_cost_estimated DECIMAL(8,4) DEFAULT 0.0000,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    published_at TIMESTAMP NULL,
    INDEX idx_status (status),
    INDEX idx_slug (slug),
    INDEX idx_created_at (created_at),
    INDEX idx_ai_generated (ai_generated)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla de logs de IA
CREATE TABLE IF NOT EXISTS ai_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    article_id INT,
    ai_provider ENUM('groq', 'huggingface', 'openai') NOT NULL,
    ai_model VARCHAR(100) NOT NULL,
    prompt_text TEXT NOT NULL,
    response_text LONGTEXT,
    tokens_used INT DEFAULT 0,
    cost_estimated DECIMAL(8,4) DEFAULT 0.0000,
    execution_time_ms INT DEFAULT 0,
    status ENUM('success', 'error', 'timeout') DEFAULT 'success',
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE SET NULL,
    INDEX idx_provider (ai_provider),
    INDEX idx_created_at (created_at),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla de configuración del sistema
CREATE TABLE IF NOT EXISTS system_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value TEXT,
    config_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (config_key),
    INDEX idx_public (is_public)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla de auditoría/logs
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_action (action),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar configuraciones por defecto
INSERT IGNORE INTO system_config (config_key, config_value, config_type, description, is_public) VALUES
('site_name', 'Portfolio Juan Carlos Macías', 'string', 'Nombre del sitio web', true),
('site_description', 'Blog personal sobre tecnología y desarrollo web', 'string', 'Descripción del sitio', true),
('articles_per_page', '10', 'integer', 'Artículos por página en listados', false),
('ai_default_provider', 'groq', 'string', 'Proveedor de IA por defecto', false),
('ai_max_tokens', '2000', 'integer', 'Máximo de tokens por generación de IA', false),
('maintenance_mode', 'false', 'boolean', 'Modo mantenimiento activado', false),
('allow_comments', 'false', 'boolean', 'Permitir comentarios en artículos', true),
('google_analytics_id', '', 'string', 'ID de Google Analytics', false);

-- Insertar algunos artículos de ejemplo si no existen
INSERT IGNORE INTO articles (title, slug, content, excerpt, meta_description, status, author, tags, reading_time, ai_generated) VALUES
('Bienvenido al Blog de Juan Carlos Macías', 'bienvenido-blog-juan-carlos-macias', 
'# Bienvenido a mi blog personal

Este es el primer artículo de mi blog personal sobre tecnología y desarrollo web. Aquí compartiré mis experiencias, conocimientos y proyectos relacionados con el desarrollo web moderno.

## ¿Qué encontrarás aquí?

- Artículos sobre React y desarrollo frontend
- Tutoriales de PHP y backend
- Experiencias con inteligencia artificial
- Proyectos personales y profesionales
- Tips y trucos de desarrollo

¡Espero que disfrutes del contenido!', 
'Primer artículo del blog personal sobre tecnología y desarrollo web de Juan Carlos Macías.',
'Blog personal de Juan Carlos Macías sobre tecnología, desarrollo web, React, PHP e inteligencia artificial.',
'published', 'Juan Carlos Macías', 'bienvenida, blog, desarrollo web, react, php', 2, false),

('Mi Experiencia con React y el Desarrollo Frontend', 'experiencia-react-desarrollo-frontend',
'# Mi experiencia con React

React ha sido una de las tecnologías que más ha impactado mi carrera como desarrollador frontend. En este artículo quiero compartir algunas reflexiones y experiencias.

## Por qué elegí React

React me llamó la atención por su arquitectura basada en componentes y la forma en que maneja el estado de las aplicaciones.

## Ventajas que he encontrado

- Reutilización de componentes
- Ecosistema muy activo
- Comunidad grande y helpful
- Excelente documentación

## Proyectos destacados

He trabajado en varios proyectos usando React, desde aplicaciones simples hasta dashboards complejos.',
'Reflexiones y experiencias sobre el desarrollo con React y el ecosistema frontend moderno.',
'Experiencias de desarrollo con React, ventajas del framework y proyectos realizados por Juan Carlos Macías.',
'published', 'Juan Carlos Macías', 'react, frontend, javascript, desarrollo web, componentes', 3, false);

-- Mostrar resumen
SELECT 'Tablas creadas exitosamente' as status;
SELECT 
    TABLE_NAME as tabla,
    TABLE_ROWS as filas_estimadas
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME IN ('articles', 'ai_logs', 'system_config', 'audit_logs', 'admin_users')
ORDER BY TABLE_NAME;