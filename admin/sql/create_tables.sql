-- ================================================
-- Script de Creación de Tablas para Sistema de Artículos con IA
-- Proyecto: Portfolio - Sistema Integrado
-- Fecha: 28 de octubre de 2025
-- ================================================

-- Usar la base de datos existente del portfolio
-- (Este script se ejecuta en la BD ya configurada)

-- ================================================
-- Tabla principal de artículos
-- ================================================
CREATE TABLE IF NOT EXISTS articles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    content LONGTEXT NOT NULL,
    excerpt TEXT,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    author VARCHAR(100) DEFAULT 'Juan Carlos Macías',
    ai_generated BOOLEAN DEFAULT FALSE,
    ai_model VARCHAR(50),
    ai_prompt TEXT,
    tags VARCHAR(500),
    meta_description VARCHAR(160),
    featured_image VARCHAR(255),
    reading_time INT,
    views_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    published_at TIMESTAMP NULL,
    
    -- Índices para optimización
    INDEX idx_status (status),
    INDEX idx_published_at (published_at),
    INDEX idx_slug (slug),
    INDEX idx_created_at (created_at),
    FULLTEXT idx_search (title, content, excerpt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- Tabla de usuarios administradores
-- ================================================
CREATE TABLE IF NOT EXISTS admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    active BOOLEAN DEFAULT TRUE,
    failed_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices para optimización
    INDEX idx_username (username),
    INDEX idx_active (active),
    INDEX idx_locked_until (locked_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- Tabla de logs de IA (opcional, para métricas)
-- ================================================
CREATE TABLE IF NOT EXISTS ai_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    article_id INT NULL,
    prompt TEXT NOT NULL,
    ai_model VARCHAR(50) NOT NULL,
    tokens_used INT,
    cost_estimated DECIMAL(8,4),
    generation_time FLOAT,
    status VARCHAR(20) DEFAULT 'success',
    error_message TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Índices para optimización
    INDEX idx_created_at (created_at),
    INDEX idx_status (status),
    INDEX idx_ai_model (ai_model),
    
    -- Relación opcional con artículos
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- Insertar usuario administrador inicial
-- ================================================
-- Contraseña por defecto: admin123
-- (CAMBIAR INMEDIATAMENTE EN PRODUCCIÓN)
INSERT IGNORE INTO admin_users (username, password_hash, email, active) 
VALUES (
    'admin', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
    'admin@portfolio.local',
    TRUE
);

-- ================================================
-- Datos de ejemplo para artículos (opcional)
-- ================================================
INSERT IGNORE INTO articles (title, slug, content, excerpt, status, author, published_at) 
VALUES 
(
    'Bienvenido al Blog del Portfolio',
    'bienvenido-blog-portfolio',
    '<p>Este es el primer artículo del sistema de blog integrado con IA. Aquí podrás compartir tus conocimientos, experiencias y proyectos de desarrollo.</p><p>El sistema permite crear contenido de manera tradicional o utilizando inteligencia artificial para generar artículos de calidad.</p>',
    'Primer artículo del sistema de blog integrado con capacidades de IA.',
    'published',
    'Juan Carlos Macías',
    NOW()
),
(
    'Introducción a la Inteligencia Artificial en el Desarrollo',
    'introduccion-ia-desarrollo',
    '<p>La inteligencia artificial está revolucionando la forma en que desarrollamos software. En este artículo exploramos las principales aplicaciones de IA en el desarrollo moderno.</p><p>Desde la generación de código hasta la optimización de bases de datos, la IA se está convirtiendo en una herramienta esencial para los desarrolladores.</p>',
    'Exploramos cómo la IA está transformando el desarrollo de software moderno.',
    'draft',
    'Juan Carlos Macías',
    NULL
);

-- ================================================
-- Verificación de la instalación
-- ================================================
-- Mostrar estadísticas de las tablas creadas
SELECT 
    'articles' as tabla,
    COUNT(*) as registros
FROM articles

UNION ALL

SELECT 
    'admin_users' as tabla,
    COUNT(*) as registros
FROM admin_users

UNION ALL

SELECT 
    'ai_logs' as tabla,
    COUNT(*) as registros
FROM ai_logs;