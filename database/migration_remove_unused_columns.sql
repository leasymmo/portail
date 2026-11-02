-- ==============================================================================
-- MIGRATION : Suppression des colonnes inutilisées dans 'properties'
-- Portail Voyageurs - Conciergerie Carré d'Hôtes
-- ==============================================================================
-- Compatible MySQL 5.7, 8.0, 8.4+ et MariaDB
-- ==============================================================================

SET NAMES utf8mb4;

-- Suppression directe des colonnes (si elles existent encore dans votre table) :
ALTER TABLE `properties`
    DROP COLUMN `subtitle`,
    DROP COLUMN `welcome_title`,
    DROP COLUMN `welcome_subtitle`,
    DROP COLUMN `contact_phone`,
    DROP COLUMN `contact_email`;

