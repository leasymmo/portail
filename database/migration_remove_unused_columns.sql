-- ==============================================================================
-- MIGRATION : Suppression des colonnes inutilisées dans 'properties'
-- Portail Voyageurs - Conciergerie Carré d'Hôtes
-- ==============================================================================
-- Cette migration supprime en toute sécurité les colonnes devenues obsolètes :
-- - subtitle
-- - welcome_title
-- - welcome_subtitle
-- - contact_phone
-- - contact_email
-- ==============================================================================

SET NAMES utf8mb4;

-- Vérification / Suppression des colonnes
ALTER TABLE `properties`
    DROP COLUMN IF EXISTS `subtitle`,
    DROP COLUMN IF EXISTS `welcome_title`,
    DROP COLUMN IF EXISTS `welcome_subtitle`,
    DROP COLUMN IF EXISTS `contact_phone`,
    DROP COLUMN IF EXISTS `contact_email`;
