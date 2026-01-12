-- Migración: Agregar campos faltantes a enhanced_conversations
-- Fecha: 2026-01-12

USE `i-portfolio`;

-- Verificar si la columna rag_context existe, si no, agregarla
SET @dbname = DATABASE();
SET @tablename = 'enhanced_conversations';
SET @columnname = 'rag_context';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN ", @columnname, " JSON COMMENT 'Contexto RAG usado en la respuesta' AFTER bot_response")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Verificar si la columna tokens_used existe, si no, agregarla
SET @columnname = 'tokens_used';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN ", @columnname, " INT DEFAULT 0 COMMENT 'Tokens consumidos por el LLM' AFTER llm_model")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Agregar índice para mejorar consultas por fecha
ALTER TABLE enhanced_conversations 
ADD INDEX IF NOT EXISTS idx_created_session (created_at, session_id);

-- Mostrar estructura final
DESCRIBE enhanced_conversations;

SELECT 'Migración completada exitosamente' as status;
