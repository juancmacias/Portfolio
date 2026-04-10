-- ================================================
-- Migración: Actualizar tabla ai_logs
-- Fecha: 2026-04-10
-- Descripción: Actualizar estructura de ai_logs para
--              compatibilidad con AIContentGenerator
-- ================================================

-- 1. Primero eliminar restricciones y columnas obsoletas
ALTER TABLE ai_logs DROP FOREIGN KEY ai_logs_ibfk_1;

ALTER TABLE ai_logs DROP COLUMN article_id;

ALTER TABLE ai_logs DROP COLUMN ip_address;

-- 2. Agregar columna ai_provider (verificar si no existe)
ALTER TABLE ai_logs 
ADD COLUMN ai_provider VARCHAR(50) NOT NULL DEFAULT 'unknown' AFTER id;

-- 3. Renombrar prompt a prompt_text
ALTER TABLE ai_logs 
CHANGE COLUMN prompt prompt_text TEXT;

-- 4. Agregar response_text
ALTER TABLE ai_logs 
ADD COLUMN response_text TEXT NULL AFTER prompt_text;

-- 5. Renombrar generation_time a execution_time_ms
ALTER TABLE ai_logs 
CHANGE COLUMN generation_time execution_time_ms INT NULL;

-- 6. Actualizar índices (primero eliminar si existen)
DROP INDEX idx_ai_model ON ai_logs;

DROP INDEX idx_created_at ON ai_logs;

DROP INDEX idx_status ON ai_logs;

-- 7. Crear nuevos índices
CREATE INDEX idx_ai_provider ON ai_logs(ai_provider);

CREATE INDEX idx_ai_model ON ai_logs(ai_model);

CREATE INDEX idx_status ON ai_logs(status);

CREATE INDEX idx_created_at ON ai_logs(created_at);

-- ================================================
-- Estructura final esperada:
-- ================================================
-- id INT PRIMARY KEY AUTO_INCREMENT
-- ai_provider VARCHAR(50) NOT NULL
-- ai_model VARCHAR(50) NOT NULL
-- prompt_text TEXT NOT NULL
-- response_text TEXT NULL
-- tokens_used INT NULL
-- cost_estimated DECIMAL(8,4) NULL
-- execution_time_ms INT NULL
-- status VARCHAR(20) DEFAULT 'success'
-- error_message TEXT NULL
-- created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
-- ================================================
