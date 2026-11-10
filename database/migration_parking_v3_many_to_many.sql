-- -----------------------------------------------------------------------------
-- MIGRATION PARKING V3 : ARCHITECTURE MANY-TO-MANY (CATALOGUE & ASSOCIATIONS)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `property_parking_associations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `property_id` int unsigned NOT NULL,
  `category` enum('free_street','free_spot','private_parking','tcl_pr') NOT NULL,
  `parking_item_id` int unsigned NOT NULL,
  `walking_time_fr` varchar(100) DEFAULT NULL,
  `walking_time_en` varchar(100) DEFAULT NULL,
  `transit_time_fr` varchar(100) DEFAULT NULL,
  `transit_time_en` varchar(100) DEFAULT NULL,
  `how_to_reach_fr` text DEFAULT NULL,
  `how_to_reach_en` text DEFAULT NULL,
  `notes_fr` text DEFAULT NULL,
  `notes_en` text DEFAULT NULL,
  `display_order` int unsigned NOT NULL DEFAULT 1,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_prop_cat_item` (`property_id`, `category`, `parking_item_id`),
  KEY `idx_prop_cat` (`property_id`, `category`),
  KEY `idx_cat_item` (`category`, `parking_item_id`),
  CONSTRAINT `fk_ppa_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (14, 'free_street', 1, 'Environ 10 min à pied', 'Approx. 10 min walk', 1, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (14, 'free_street', 2, 'Environ 12 min à pied', 'Approx. 12 min walk', 2, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (14, 'free_street', 3, 'Environ 14 min à pied', 'Approx. 14 min walk', 3, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (14, 'free_street', 4, 'Environ 16 min à pied', 'Approx. 16 min walk', 4, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (11, 'free_street', 9, 'Environ 6 min à pied', 'Approx. 6 min walk', 1, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (11, 'free_street', 10, 'Environ 13 min à pied', 'Approx. 13 min walk', 2, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (11, 'free_street', 11, 'Environ 15 min à pied', 'Approx. 15 min walk', 3, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (12, 'free_street', 2, 'Environ 11 min à pied', 'Approx. 11 min walk', 1, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (12, 'free_street', 4, 'Environ 13 min à pied', 'Approx. 13 min walk', 2, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (12, 'free_street', 3, 'Environ 16 min à pied', 'Approx. 16 min walk', 3, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (13, 'free_street', 22, 'Environ 14 min à pied (1 km)', 'Approx. 14 min walk (1 km)', 1, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (13, 'free_street', 23, 'Environ 14 min à pied (1 km)', 'Approx. 14 min walk (1 km)', 2, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (13, 'free_street', 24, 'Environ 18 min à pied (1,3 km)', 'Approx. 18 min walk (1.3 km)', 3, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (13, 'free_street', 25, 'Environ 25 min en transports', 'Approx. 25 min by transit', 4, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (10, 'free_street', 11, 'Au pied direct de l\'immeuble', 'Directly at the foot of the building', 1, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (10, 'free_street', 10, 'Environ 2 min à pied (150 m)', 'Approx. 2 min walk (150 m)', 2, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (10, 'free_street', 34, 'Environ 4 min à pied (280 m)', 'Approx. 4 min walk (280 m)', 3, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (10, 'free_street', 35, 'Environ 5 min à pied (400 m)', 'Approx. 5 min walk (400 m)', 4, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (10, 'free_street', 36, 'Environ 8 min à pied (600 m)', 'Approx. 8 min walk (600 m)', 5, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (10, 'free_street', 37, 'Environ 9 min à pied (650 m)', 'Approx. 9 min walk (650 m)', 6, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (16, 'free_street', 41, 'Environ 8 min à pied', 'Approximately 8-minute walk', 1, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (16, 'free_street', 42, 'Environ 10 min à pied', 'Approx. 10 min walk', 2, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (16, 'free_street', 43, 'Environ 10 min à pied', 'Approx. 10 min walk', 3, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (16, 'free_street', 44, 'Environ 11 min à pied', 'Approx. 11 min walk', 4, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (16, 'free_street', 45, 'Environ 11 min à pied', 'Approx. 11 min walk', 5, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (17, 'free_spot', 50, 'En face du logement (1 min à pied)', 'Opposite the apartment (1 min walk)', 1, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (17, 'free_street', 51, 'Environ 5 min à pied', 'Approx. 5 min walk', 2, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (17, 'free_street', 52, 'Environ 11 min à pied', 'Approx. 11 min walk', 3, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (17, 'free_street', 53, 'Environ 14 min à pied', 'Approx. 14 min walk', 4, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (18, 'free_spot', 50, 'En face du logement (1 min à pied)', 'Opposite the apartment (1 min walk)', 1, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (18, 'free_street', 51, 'Environ 5 min à pied', 'Approx. 5 min walk', 2, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (18, 'free_street', 52, 'Environ 11 min à pied', 'Approx. 11 min walk', 3, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (18, 'free_street', 53, 'Environ 14 min à pied', 'Approx. 14 min walk', 4, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (19, 'free_street', 63, NULL, NULL, 1, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

DELETE FROM `parking_free_spots` WHERE `id` IN (15, 16, 17, 32, 33, 59, 60, 61, 62);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (14, 'private_parking', 5, '10 min à pied (4 min vélo)', '10 min walk (4 min bike)', 1, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (14, 'private_parking', 6, '16 min à pied (11 min en transports)', '16 min walk (11 min by transit)', 2, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (11, 'private_parking', 12, '2 min à pied (1 min vélo)', '2 min walk (1 min bike)', 1, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (11, 'private_parking', 13, '3 min à pied (1 min vélo)', '3 min walk (1 min bike)', 2, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (12, 'private_parking', 18, '8 min à pied (5 min en bus)', '8 min walk (5 min bus)', 1, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (12, 'private_parking', 19, '15 min à pied (9 min en transports)', '15 min walk (9 min by transit)', 2, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (13, 'private_parking', 26, 'Environ 5 à 8 min à pied', 'Approx. 5 to 8 min walk', 1, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (13, 'private_parking', 27, '11 min à pied (800 m)', '11 min walk (800 m)', 2, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (13, 'private_parking', 28, 'Environ 15 min à pied', 'Approx. 15 min walk', 3, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (10, 'private_parking', 38, 'Au pied du logement (1 min à pied)', 'Right outside the accommodation (1 min walk)', 1, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (10, 'private_parking', 39, 'Environ 12 min à pied', 'Approx. 12 min walk', 2, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (16, 'private_parking', 46, 'Environ 6 min à pied', 'Approx. 6 min walk', 1, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (16, 'private_parking', 47, 'Environ 7 min à pied', 'Approx. 7 min walk', 2, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (17, 'private_parking', 54, '18 min à pied (5 min vélo, 11 min métro)', '18 min walk (5 min bike, 11 min metro)', 1, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (17, 'private_parking', 55, 'Environ 5 min à pied (2 min vélo)', 'Approx. 5 min walk (2 min bike)', 2, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (17, 'private_parking', 56, 'Environ 5 min à pied (2 min vélo)', 'Approx. 5 min walk (2 min bike)', 3, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (18, 'private_parking', 54, '18 min à pied (5 min vélo, 11 min métro)', '18 min walk (5 min bike, 11 min metro)', 1, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (18, 'private_parking', 55, 'Environ 5 min à pied (2 min vélo)', 'Approx. 5 min walk (2 min bike)', 2, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (18, 'private_parking', 56, 'Environ 5 min à pied (2 min vélo)', 'Approx. 5 min walk (2 min bike)', 3, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`) 
VALUES (19, 'private_parking', 70, '4', '4', 2, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`);

DELETE FROM `parking_private_lots` WHERE `id` IN (63, 64, 65);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `transit_time_fr`, `transit_time_en`, `how_to_reach_fr`, `how_to_reach_en`, `display_order`, `active`) 
VALUES (14, 'tcl_pr', 7, 'Environ 26 min en TCL', 'Approx. 26 min by transit', 'Prendre le Métro D jusqu\'à Grange Blanche, puis le bus C13 jusqu\'à l\'arrêt Dauphiné-Lacassagne (~26 min).', 'Take Metro D to Grange Blanche, then bus C13 to Dauphiné-Lacassagne stop (~26 min).', 1, 1)
ON DUPLICATE KEY UPDATE `transit_time_fr` = VALUES(`transit_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `transit_time_fr`, `transit_time_en`, `how_to_reach_fr`, `how_to_reach_en`, `display_order`, `active`) 
VALUES (14, 'tcl_pr', 8, 'Environ 26 min en TCL', 'Approx. 26 min by transit', 'Prendre le Métro A vers Vaulx-en-Velin La Soie, puis le Tram T3 jusqu\'à Dauphiné-Lacassagne (~26 min).', 'Take Metro A towards Vaulx-en-Velin La Soie, then Tram T3 to Dauphiné-Lacassagne (~26 min).', 2, 1)
ON DUPLICATE KEY UPDATE `transit_time_fr` = VALUES(`transit_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `transit_time_fr`, `transit_time_en`, `how_to_reach_fr`, `how_to_reach_en`, `display_order`, `active`) 
VALUES (11, 'tcl_pr', 7, 'Environ 15 min en TCL', 'Approx. 15 min by transit', 'Prendre le Bus C15 direction Bachut - Mairie du 8e, puis rejoindre le logement à pied (~15 min).', 'Take Bus C15 towards Bachut - Mairie du 8e, then walk to the apartment (~15 min).', 1, 1)
ON DUPLICATE KEY UPDATE `transit_time_fr` = VALUES(`transit_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `transit_time_fr`, `transit_time_en`, `how_to_reach_fr`, `how_to_reach_en`, `display_order`, `active`) 
VALUES (12, 'tcl_pr', 7, 'Environ 25 min en TCL', 'Approx. 25 min by transit', 'Prendre le Métro D jusqu\'à Grange Blanche, puis le bus C13 jusqu\'à Dauphiné-Lacassagne (~25 min).', 'Take Metro D to Grange Blanche, then bus C13 to Dauphiné-Lacassagne (~25 min).', 1, 1)
ON DUPLICATE KEY UPDATE `transit_time_fr` = VALUES(`transit_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `transit_time_fr`, `transit_time_en`, `how_to_reach_fr`, `how_to_reach_en`, `display_order`, `active`) 
VALUES (12, 'tcl_pr', 8, 'Environ 30 min en TCL', 'Approx. 30 min by transit', 'Prendre le Métro A vers La Soie, puis le Tram T3 jusqu\'à Dauphiné-Lacassagne (à 2 pas du 75 rue du Dauphiné).', 'Take Metro A to La Soie, then Tram T3 to Dauphiné-Lacassagne (steps from 75 rue du Dauphiné).', 2, 1)
ON DUPLICATE KEY UPDATE `transit_time_fr` = VALUES(`transit_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `transit_time_fr`, `transit_time_en`, `how_to_reach_fr`, `how_to_reach_en`, `display_order`, `active`) 
VALUES (13, 'tcl_pr', 29, 'Environ 15-20 min en TCL', 'Approx. 15-20 min by transit', 'Métro A direct jusqu\'à Charpennes - Charles-Hernu, puis 6 à 8 min à pied jusqu\'au logement.', 'Direct Metro A to Charpennes - Charles-Hernu, then 6-8 min walk to the apartment.', 1, 1)
ON DUPLICATE KEY UPDATE `transit_time_fr` = VALUES(`transit_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `transit_time_fr`, `transit_time_en`, `how_to_reach_fr`, `how_to_reach_en`, `display_order`, `active`) 
VALUES (13, 'tcl_pr', 30, 'Environ 20 min en TCL', 'Approx. 20 min by transit', 'Métro A direct jusqu\'à Charpennes, puis 6 à 8 min à pied.', 'Direct Metro A to Charpennes, then 6 to 8 min walk.', 2, 1)
ON DUPLICATE KEY UPDATE `transit_time_fr` = VALUES(`transit_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `transit_time_fr`, `transit_time_en`, `how_to_reach_fr`, `how_to_reach_en`, `display_order`, `active`) 
VALUES (13, 'tcl_pr', 31, 'Environ 20 min en TCL', 'Approx. 20 min by transit', 'Tram T1 ou T4 jusqu\'à Charpennes, puis 6 à 8 min à pied.', 'Tram T1 or T4 to Charpennes, then 6 to 8 min walk.', 3, 1)
ON DUPLICATE KEY UPDATE `transit_time_fr` = VALUES(`transit_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `transit_time_fr`, `transit_time_en`, `how_to_reach_fr`, `how_to_reach_en`, `display_order`, `active`) 
VALUES (10, 'tcl_pr', 7, 'Environ 15 min en TCL (ou 22 min à pied)', 'Approx. 15 min by transit (or 22 min walk)', 'Métro D jusqu\'à Grange-Blanche, puis rejoindre le logement à pied ou en bus (~15 min).', 'Take Metro D to Grange-Blanche, then walk or take bus to apartment (~15 min).', 1, 1)
ON DUPLICATE KEY UPDATE `transit_time_fr` = VALUES(`transit_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `transit_time_fr`, `transit_time_en`, `how_to_reach_fr`, `how_to_reach_en`, `display_order`, `active`) 
VALUES (16, 'tcl_pr', 48, NULL, NULL, 'Accès direct par le Métro D (station Gorge de Loup vers Valmy) ou 15 min de marche.', 'Direct access via Metro D (Gorge de Loup station to Valmy) or 15 min walk.', 1, 1)
ON DUPLICATE KEY UPDATE `transit_time_fr` = VALUES(`transit_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `transit_time_fr`, `transit_time_en`, `how_to_reach_fr`, `how_to_reach_en`, `display_order`, `active`) 
VALUES (16, 'tcl_pr', 49, NULL, NULL, 'Métro D (station Gare de Vaise vers Valmy) ou 15 min de marche.', 'Metro D (Gare de Vaise station to Valmy) or 15 min walk.', 2, 1)
ON DUPLICATE KEY UPDATE `transit_time_fr` = VALUES(`transit_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `transit_time_fr`, `transit_time_en`, `how_to_reach_fr`, `how_to_reach_en`, `display_order`, `active`) 
VALUES (17, 'tcl_pr', 8, 'Environ 10 à 15 min en Métro A', 'Approx. 10 to 15 min by Metro A', 'Prendre le Métro A direction Perrache jusqu\'à la station Flachet - Alain Gilles, puis 2 min à pied jusqu\'au logement.', 'Take Metro A towards Perrache to Flachet - Alain Gilles station, then 2 min walk to apartment.', 1, 1)
ON DUPLICATE KEY UPDATE `transit_time_fr` = VALUES(`transit_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `transit_time_fr`, `transit_time_en`, `how_to_reach_fr`, `how_to_reach_en`, `display_order`, `active`) 
VALUES (17, 'tcl_pr', 58, 'Environ 10 à 15 min en Métro A', 'Approx. 10 to 15 min by Metro A', 'Prendre le Métro A direction Perrache jusqu\'à Flachet - Alain Gilles, puis 2 min à pied.', 'Take Metro A towards Perrache to Flachet - Alain Gilles station, then 2 min walk.', 2, 1)
ON DUPLICATE KEY UPDATE `transit_time_fr` = VALUES(`transit_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `transit_time_fr`, `transit_time_en`, `how_to_reach_fr`, `how_to_reach_en`, `display_order`, `active`) 
VALUES (18, 'tcl_pr', 8, 'Environ 10 à 15 min en Métro A', 'Approx. 10 to 15 min by Metro A', 'Prendre le Métro A direction Perrache jusqu\'à la station Flachet - Alain Gilles, puis 2 min à pied jusqu\'au logement.', 'Take Metro A towards Perrache to Flachet - Alain Gilles station, then 2 min walk to apartment.', 1, 1)
ON DUPLICATE KEY UPDATE `transit_time_fr` = VALUES(`transit_time_fr`);

INSERT INTO `property_parking_associations` 
(`property_id`, `category`, `parking_item_id`, `transit_time_fr`, `transit_time_en`, `how_to_reach_fr`, `how_to_reach_en`, `display_order`, `active`) 
VALUES (18, 'tcl_pr', 58, 'Environ 10 à 15 min en Métro A', 'Approx. 10 to 15 min by Metro A', 'Prendre le Métro A direction Perrache jusqu\'à Flachet - Alain Gilles, puis 2 min à pied.', 'Take Metro A towards Perrache to Flachet - Alain Gilles station, then 2 min walk.', 2, 1)
ON DUPLICATE KEY UPDATE `transit_time_fr` = VALUES(`transit_time_fr`);

DELETE FROM `parking_tcl_prs` WHERE `id` IN (14, 20, 21, 40, 57, 66, 67);


-- Suppression des colonnes devenues spécifiques à l'association
ALTER TABLE `parking_free_spots` 
  DROP INDEX `idx_pfs_property_order`,
  DROP COLUMN `property_id`,
  DROP COLUMN `walking_time_fr`,
  DROP COLUMN `walking_time_en`,
  DROP COLUMN `display_order`,
  DROP COLUMN `active`;

ALTER TABLE `parking_private_lots` 
  DROP INDEX `idx_ppl_property_order`,
  DROP COLUMN `property_id`,
  DROP COLUMN `walking_time_fr`,
  DROP COLUMN `walking_time_en`,
  DROP COLUMN `display_order`,
  DROP COLUMN `active`;

ALTER TABLE `parking_tcl_prs` 
  DROP INDEX `idx_ptp_property_order`,
  DROP COLUMN `property_id`,
  DROP COLUMN `transit_time_fr`,
  DROP COLUMN `transit_time_en`,
  DROP COLUMN `how_to_reach_fr`,
  DROP COLUMN `how_to_reach_en`,
  DROP COLUMN `display_order`,
  DROP COLUMN `active`;
