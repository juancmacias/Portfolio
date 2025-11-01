-- ================================================
-- Tabla de Proyectos para Portfolio
-- Migración de datos_proyectos.json a Base de Datos
-- ================================================

CREATE TABLE IF NOT EXISTS projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT NOT NULL,
    image_path VARCHAR(255),
    github_link VARCHAR(255),
    demo_link VARCHAR(255),
    project_type ENUM('web', 'app', 'other') DEFAULT 'web',
    is_featured BOOLEAN DEFAULT FALSE,
    is_blog BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'archived', 'maintenance') DEFAULT 'active',
    technologies JSON,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices para optimización
    INDEX idx_type (project_type),
    INDEX idx_status (status),
    INDEX idx_featured (is_featured),
    INDEX idx_slug (slug),
    INDEX idx_sort_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- Verificación de la tabla creada
-- ================================================
SELECT 'Table projects created successfully' as status;