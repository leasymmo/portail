-- ==============================================================================
-- MIGRATION : TABLE DES PARAMÈTRES D'ADMINISTRATION & IA
-- Conciergerie Carré d'Hôtes
-- ==============================================================================

CREATE TABLE IF NOT EXISTS `admin_settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT DEFAULT NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_admin_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Paramètres initiaux par défaut
INSERT INTO `admin_settings` (`setting_key`, `setting_value`)
VALUES 
    ('ai_provider', 'gemini'),
    ('ai_api_key_gemini', NULL),
    ('ai_api_key_openai', NULL),
    ('ai_api_key_claude', NULL),
    ('ai_model_gemini', 'gemini-3.5-flash'),
    ('ai_model_openai', 'gpt-4o-mini'),
    ('ai_model_claude', 'claude-3-5-haiku-latest')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;
