-- ==============================================================================
-- MIGRATION BACK-OFFICE & AUTHENTIFICATION - CARRÉ D'HÔTES
-- Version MySQL : 8.0 & 8.4+
-- 
-- Ce script :
-- 1. Crée les tables admin_users, admin_sessions, admin_activity_logs
-- 2. Crée le compte superadmin initial : admin@carredhotes.fr
-- ==============================================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;

-- ------------------------------------------------------------------------------
-- 1. Table : admin_users
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(191) NOT NULL UNIQUE COMMENT 'Identifiant unique de connexion',
    `password_hash` VARCHAR(255) NOT NULL COMMENT 'Hachage sécurisé (bcrypt/Argon2id)',
    `name` VARCHAR(100) NOT NULL COMMENT 'Nom et prénom',
    `role` ENUM('superadmin', 'admin', 'editor') NOT NULL DEFAULT 'admin' COMMENT 'Rôles de privilèges',
    `avatar_url` VARCHAR(500) DEFAULT NULL,
    `failed_login_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `locked_until` DATETIME DEFAULT NULL,
    `last_login_at` DATETIME DEFAULT NULL,
    `last_login_ip` VARCHAR(45) DEFAULT NULL,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_admin_users_email_active` (`email`, `active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 2. Table : admin_sessions
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_sessions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `session_token` VARCHAR(128) NOT NULL UNIQUE COMMENT 'Jeton de session cryptographique aléatoire',
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `expires_at` DATETIME NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_admin_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE,
    INDEX `idx_admin_sessions_token` (`session_token`),
    INDEX `idx_admin_sessions_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 3. Table : admin_activity_logs
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_activity_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `action` ENUM('login', 'logout', 'create', 'update', 'delete', 'toggle') NOT NULL,
    `entity_type` VARCHAR(50) NOT NULL COMMENT 'property, video, parking, location, user',
    `entity_id` INT UNSIGNED DEFAULT NULL,
    `entity_label` VARCHAR(255) DEFAULT NULL,
    `details` JSON DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_admin_logs_user` FOREIGN KEY (`user_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL,
    INDEX `idx_admin_logs_user_date` (`user_id`, `created_at`),
    INDEX `idx_admin_logs_entity` (`entity_type`, `entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;

-- ------------------------------------------------------------------------------
-- 4. Compte Superadmin Initial (si inexistant)
-- Mot de passe temporaire : CarreDhotes2026!Admin
-- ------------------------------------------------------------------------------
INSERT INTO `admin_users` (`email`, `password_hash`, `name`, `role`, `active`)
VALUES (
    'admin@carredhotes.fr',
    '$2y$12$9Ez4.NRo2swrilvYXlC9g.x/KObSyToWQcMe8xo/NXwp150kZjIhq',
    'Administrateur Carré d\'Hôtes',
    'superadmin',
    1
)
ON DUPLICATE KEY UPDATE `email` = `email`;
