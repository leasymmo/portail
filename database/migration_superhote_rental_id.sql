-- ==============================================================================
-- MIGRATION : AJOUT DU CHAMP RENTAL_ID SUPERHOTE SUR LA TABLE PROPERTIES
-- Conciergerie Carré d'Hôtes - Portail Voyageurs
-- ==============================================================================

ALTER TABLE `properties` 
    ADD COLUMN `rental_id` VARCHAR(50) DEFAULT NULL COMMENT 'ID du logement sur SuperHote' AFTER `name`;

ALTER TABLE `properties` 
    ADD INDEX `idx_properties_rental_id` (`rental_id`);

-- Initialisation des identifiants SuperHote pour les logements existants
UPDATE `properties` SET `rental_id` = '11419685' WHERE `slug` = 'marietton-cosy';
UPDATE `properties` SET `rental_id` = '11419684' WHERE `slug` = 'chez-denise-studio-cosy';
UPDATE `properties` SET `rental_id` = '11419688' WHERE `slug` = 'cosy-et-design';
UPDATE `properties` SET `rental_id` = '11419690' WHERE `slug` = 'studio-cosy-de-mel';
UPDATE `properties` SET `rental_id` = '11419691' WHERE `slug` = 'studio-cosy-de-paul';
UPDATE `properties` SET `rental_id` = '11419689' WHERE `slug` IN ('atelier-cosy-felix', 'l-atelier-cosy-felix');
UPDATE `properties` SET `rental_id` = '11419687' WHERE `slug` = 'berthelot-design';
UPDATE `properties` SET `rental_id` = '11452648' WHERE `slug` = 'bataille';
UPDATE `properties` SET `rental_id` = '11453225' WHERE `slug` = 'maisonnette-en-jouhet';
