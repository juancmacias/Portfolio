-- Google Calendar (OAuth) - system_config keys
--
-- Script autónomo: crea la tabla system_config si no existe y asegura
-- las claves necesarias para Google Calendar.
--
-- NOTA (MVP): google_oauth_client_secret se guarda en texto plano.

CREATE TABLE IF NOT EXISTS system_config (
  id INT AUTO_INCREMENT PRIMARY KEY,
  config_key VARCHAR(191) NOT NULL,
  config_value LONGTEXT NULL,
  config_type VARCHAR(50) NOT NULL DEFAULT 'string',
  description VARCHAR(255) NULL,
  is_public TINYINT(1) NOT NULL DEFAULT 0,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_system_config_key (config_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO system_config (config_key, config_value, config_type, description, is_public)
VALUES
  ('google_calendar_enabled', 'false', 'boolean', 'Activar integración con Google Calendar', 0),
  ('google_oauth_client_id', '', 'string', 'Google OAuth Client ID', 0),
  ('google_oauth_client_secret', '', 'string', 'Google OAuth Client Secret (MVP en texto plano)', 0),
  ('google_oauth_redirect_uri', '', 'string', 'Google OAuth Redirect URI', 0),
  ('google_calendar_id', 'primary', 'string', 'Calendar ID (primary o ID compartido)', 0)
ON DUPLICATE KEY UPDATE
  config_type = VALUES(config_type),
  description = VALUES(description),
  is_public = VALUES(is_public);
