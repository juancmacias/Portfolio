-- Migración: Sistema de Formulario de Contacto
-- Fecha: 13 de abril de 2026
-- Versión: 1.0

-- Tabla para almacenar solicitudes de contacto
CREATE TABLE IF NOT EXISTS contact_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Datos del formulario
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL COMMENT 'Teléfono obligatorio',
    subject VARCHAR(255) NULL COMMENT 'Campo eliminado del formulario',
    message TEXT NOT NULL,
    
    -- Estado del mensaje
    status ENUM('new', 'read', 'replied', 'archived') DEFAULT 'new',
    
    -- Metadata del visitante
    user_ip VARCHAR(45),
    user_agent TEXT,
    referrer VARCHAR(500),
    
    -- Gestión admin
    admin_notes TEXT NULL,
    replied_at TIMESTAMP NULL,
    replied_by VARCHAR(100) NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices para búsquedas
    INDEX idx_status (status),
    INDEX idx_email (email),
    INDEX idx_created_at (created_at),
    INDEX idx_status_created (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar configuración del sistema (sin config_description, esa columna no existe)
INSERT IGNORE INTO system_config (config_key, config_value) VALUES
('contact_form_enabled', '1'),
('contact_notification_enabled', '1'),
('contact_rate_limit_minutes', '10'),
('contact_auto_reply_enabled', '0');
