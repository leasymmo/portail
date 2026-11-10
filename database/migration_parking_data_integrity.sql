-- =============================================================================
-- MIGRATION : INTÉGRITÉ, COHÉRENCE ET COMPLÉTION DES DONNÉES DE STATIONNEMENT
-- =============================================================================

-- 1. FUSION DES DOUBLONS DANS LE CATALOGUE TCL P+R
-- Reclassement des associations TCL #29 (Laurent Bonnevay) vers #8
UPDATE `property_parking_associations` 
SET `parking_item_id` = 8 
WHERE `category` = 'tcl_pr' AND `parking_item_id` = 29;

-- Suppression du doublon TCL #29
DELETE FROM `parking_tcl_prs` WHERE `id` = 29;

-- Reclassement des associations TCL #58 (Vaulx-en-Velin La Soie) vers #30
UPDATE `property_parking_associations` 
SET `parking_item_id` = 30 
WHERE `category` = 'tcl_pr' AND `parking_item_id` = 58;

-- Suppression du doublon TCL #58
DELETE FROM `parking_tcl_prs` WHERE `id` = 58;

-- Mise à jour de l'adresse normalisée du P+R La Soie (#30)
UPDATE `parking_tcl_prs` 
SET `address` = '3 rue Maurice-Moissonnier, 69120 Vaulx-en-Velin' 
WHERE `id` = 30;


-- 2. SUPPRESSION DES DONNÉES DE TEST / DUMMY
DELETE FROM `property_parking_associations` 
WHERE (`category` IN ('free_street','free_spot') AND `parking_item_id` = 63)
   OR (`category` = 'private_parking' AND `parking_item_id` = 70);

DELETE FROM `parking_free_spots` WHERE `id` = 63;
DELETE FROM `parking_private_lots` WHERE `id` = 70;


-- 3. COMPLÉTION DES INFORMATIONS DANS PARKING_PRIVATE_LOTS
-- Alfred Vanderpol (Place Alfred Vanderpol, Lyon 9e)
UPDATE `parking_private_lots` SET 
  `operator` = 'Ville de Lyon / EasyPark',
  `max_height` = 'Sans limitation (stationnement en surface)',
  `opening_hours_fr` = 'Ouvert 24h/24 et 7j/7',
  `opening_hours_en` = 'Open 24/7',
  `rates_fr` = 'Tarification voirie Ville de Lyon (Zone Tempo) : 1 € à 3,50 €/h selon motorisation · Gratuit de 19h à 9h, dimanche et jours fériés',
  `rates_en` = 'Lyon street rates (Tempo Zone): €1 to €3.50/h depending on vehicle type · Free from 7pm to 9am, Sundays and public holidays',
  `suitable_vehicles_fr` = 'Tous types de véhicules',
  `suitable_vehicles_en` = 'All types of vehicles',
  `booking_url` = 'https://easypark.fr',
  `notes_fr` = 'Parking public de surface à ciel ouvert, situé à proximité immédiate de la station Valmy et de la gare de Vaise.',
  `notes_en` = 'Open-air public surface car park, located right next to Valmy metro station and Gare de Vaise.'
WHERE `id` = 46;

-- Zenpark Gare de Vaise / Studio Valmy (31 rue Tissot, Lyon 9e)
UPDATE `parking_private_lots` SET 
  `opening_hours_fr` = '24h/24, 7j/7 (sur réservation via l''application Zenpark)',
  `opening_hours_en` = '24/7 (booking required via Zenpark app)',
  `suitable_vehicles_fr` = 'Citadines, berlines et SUV jusqu''à 1,90 m',
  `suitable_vehicles_en` = 'City cars, sedans and SUVs up to 1.90 m',
  `booking_url` = 'https://zenpark.com/parkings/parking-lyon-gare-de-vaise-studio-valmy',
  `notes_fr` = 'Parking souterrain sécurisé et automatisé avec ouverture par smartphone via l''application Zenpark.',
  `notes_en` = 'Secure underground automated car park with smartphone opening via the Zenpark app.'
WHERE `id` = 47;

-- Parking Hôpital Privé Jean-Mermoz (Rue Jacqueline-Auriol, Lyon 8e)
UPDATE `parking_private_lots` SET 
  `operator` = 'Ramsay Santé / Indigo',
  `max_height` = 'Sans limitation (parking de surface à ciel ouvert)',
  `suitable_vehicles_fr` = 'Tous types de véhicules',
  `suitable_vehicles_en` = 'All types of vehicles',
  `booking_url` = 'https://www.ramsaysante.fr/hopital-prive-jean-mermoz-lyon',
  `notes_fr` = 'Parking de surface en accès direct devant l''hôpital. 15 premières minutes gratuites.',
  `notes_en` = 'Open-air surface car park right in front of the hospital. First 15 minutes free.'
WHERE `id` = 38;

-- Parking Zenpark/Yespark 82 rue Feuillat (Lyon 8e)
UPDATE `parking_private_lots` SET 
  `suitable_vehicles_fr` = 'Citadines et berlines jusqu''à 1,90 m',
  `suitable_vehicles_en` = 'City cars and sedans up to 1.90 m'
WHERE `id` = 39;

-- LPA&CO Flachet (30 rue Flachet, Villeurbanne)
UPDATE `parking_private_lots` SET 
  `opening_hours_fr` = '24h/24, 7j/7 (accès sécurisé abonnés)',
  `opening_hours_en` = '24/7 (secure subscriber access)',
  `suitable_vehicles_fr` = 'Citadines et berlines jusqu''à 1,90 m',
  `suitable_vehicles_en` = 'City cars and sedans up to 1.90 m',
  `notes_fr` = 'Parking sécurisé en sous-sol via application LPA&CO. Idéal pour les séjours de moyenne à longue durée.',
  `notes_en` = 'Secure underground car park via LPA&CO app. Ideal for medium to long stays.'
WHERE `id` = 55;

-- Zenpark Cusset (2 rue Victor-Subit, Villeurbanne)
UPDATE `parking_private_lots` SET 
  `suitable_vehicles_fr` = 'Citadines et berlines jusqu''à 1,90 m',
  `suitable_vehicles_en` = 'City cars and sedans up to 1.90 m'
WHERE `id` = 56;


-- 4. COMPLÉTION DES NOTES SUR LE STATIONNEMENT DE VOIRIE GRATUIT
UPDATE `parking_free_spots` SET
  `notes_fr` = 'Gratuit 7j/7 et 24h/24.',
  `notes_en` = 'Free 24/7.'
WHERE `notes_fr` IS NULL OR `notes_fr` = '';


-- 5. COMPLÉTION DES NOTES SUR LES PARCS RELAIS TCL
UPDATE `parking_tcl_prs` SET
  `notes_fr` = 'Réservé aux usagers du réseau TCL munis d''un titre de transport aller-retour validé dans la journée.',
  `notes_en` = 'Reserved for TCL network users holding a validated same-day round-trip transit ticket.'
WHERE `notes_fr` IS NULL OR `notes_fr` = '';


-- 6. COMPLÉTION DES INFORMATIONS D''ACCÈS SPÉCIFIQUES - MARIETTON COSY (ID 16)
UPDATE `property_parking_associations` SET
  `transit_time_fr` = 'Environ 5 min en Métro D (1 station jusqu''à Valmy)',
  `transit_time_en` = 'Approx. 5 min by Metro D (1 stop to Valmy)',
  `walking_time_fr` = 'Environ 15 min à pied',
  `walking_time_en` = 'Approx. 15 min walk',
  `how_to_reach_fr` = 'Accès direct par le Métro D (station Gorge de Loup vers Valmy) ou 15 min de marche.',
  `how_to_reach_en` = 'Direct access via Metro D (Gorge de Loup station to Valmy) or 15 min walk.'
WHERE `property_id` = 16 AND `category` = 'tcl_pr' AND `parking_item_id` = 48;

UPDATE `property_parking_associations` SET
  `transit_time_fr` = 'Environ 5 min en Métro D (1 station jusqu''à Valmy)',
  `transit_time_en` = 'Approx. 5 min by Metro D (1 stop to Valmy)',
  `walking_time_fr` = 'Environ 14 min à pied',
  `walking_time_en` = 'Approx. 14 min walk',
  `how_to_reach_fr` = 'Métro D (station Gare de Vaise vers Valmy) ou 14 min de marche.',
  `how_to_reach_en` = 'Take Metro D (Gare de Vaise station to Valmy) or 14 min walk.'
WHERE `property_id` = 16 AND `category` = 'tcl_pr' AND `parking_item_id` = 49;


-- 7. CONFIGURATION COMPLÈTE DE LA MAISONNETTE EN JOUHET (ID 15)
-- Règles générales et voirie
INSERT INTO `property_parkings` (
  `property_id`,
  `intro_fr`,
  `intro_en`,
  `important_rule_fr`,
  `important_rule_en`,
  `free_parking_notes_fr`,
  `free_parking_notes_en`,
  `street_parking_info_fr`,
  `street_parking_info_en`,
  `street_parking_schedule_fr`,
  `street_parking_schedule_en`,
  `street_parking_rates_fr`,
  `street_parking_rates_en`,
  `street_parking_app`,
  `tcl_conditions_fr`,
  `tcl_conditions_en`
) VALUES (
  15,
  'Solutions complètes de stationnement autour du 6 bis impasse Jouhet (Lyon 8ᵉ).',
  'Complete parking solutions around 6 bis impasse Jouhet (Lyon 8th arr.).',
  'Le stationnement dans l''impasse est réservé aux riverains. De nombreuses places gratuites sont disponibles dans les rues directement adjacentes (rue Maryse-Bastié, rue Bataille).',
  'Parking in the dead-end street is strictly for local residents. Plentiful free spaces are available in adjacent streets (rue Maryse-Bastié, rue Bataille).',
  'Le stationnement est entièrement gratuit dans la plupart des rues entourant le logement. Utilisez l''application EasyPark pour vérifier la gratuité (zones sans tarification).',
  'Street parking is completely free in most surrounding streets. Use EasyPark mobile app to confirm free status (unmetered zones).',
  'Certaines grandes artères et secteurs voisins sont payants. Paiement via l''application EasyPark.',
  'Some main avenues and neighboring sectors have paid parking. EasyPark mobile app accepted.',
  'Lun-Sam : 9h-19h (Gratuit la nuit, le dimanche et les jours fériés)',
  'Mon-Sat: 9am-7pm (Free at night, on Sundays and public holidays)',
  '• Citadines & Électriques : 1 €/h · 35 €/10h\n• Standards : 2 €/h · 55 €/10h\n• Gros modèles & SUV : 3,50 €/h · 80 €/10h',
  '• City cars & EV: €1/h · €35/10h\n• Standard: €2/h · €55/10h\n• Large & SUV: €3.50/h · €80/10h',
  'EasyPark',
  'Gratuit avec aller-retour TCL validé le jour même (présentation obligatoire à la sortie). Sans validation : forfait 20 €. Pas de stationnement nocturne.',
  'Free with a same-day validated TCL round-trip ticket (scan ticket at exit). €20 if not validated. Overnight parking forbidden.'
) ON DUPLICATE KEY UPDATE
  `intro_fr` = VALUES(`intro_fr`),
  `intro_en` = VALUES(`intro_en`),
  `important_rule_fr` = VALUES(`important_rule_fr`),
  `important_rule_en` = VALUES(`important_rule_en`),
  `free_parking_notes_fr` = VALUES(`free_parking_notes_fr`),
  `free_parking_notes_en` = VALUES(`free_parking_notes_en`),
  `street_parking_info_fr` = VALUES(`street_parking_info_fr`),
  `street_parking_info_en` = VALUES(`street_parking_info_en`),
  `street_parking_schedule_fr` = VALUES(`street_parking_schedule_fr`),
  `street_parking_schedule_en` = VALUES(`street_parking_schedule_en`),
  `street_parking_rates_fr` = VALUES(`street_parking_rates_fr`),
  `street_parking_rates_en` = VALUES(`street_parking_rates_en`),
  `street_parking_app` = VALUES(`street_parking_app`),
  `tcl_conditions_fr` = VALUES(`tcl_conditions_fr`),
  `tcl_conditions_en` = VALUES(`tcl_conditions_en`);

-- Associations stationnement pour Maisonnette en Jouhet (ID 15)
-- Voirie gratuite
INSERT INTO `property_parking_associations` (`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`)
VALUES (15, 'free_street', 10, 'Environ 1 min à pied (100 m)', 'Approx. 1 min walk (100 m)', 1, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`), `walking_time_en` = VALUES(`walking_time_en`), `display_order` = VALUES(`display_order`);

INSERT INTO `property_parking_associations` (`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`)
VALUES (15, 'free_street', 11, 'Environ 2 min à pied (150 m)', 'Approx. 2 min walk (150 m)', 2, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`), `walking_time_en` = VALUES(`walking_time_en`), `display_order` = VALUES(`display_order`);

INSERT INTO `property_parking_associations` (`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`)
VALUES (15, 'free_street', 36, 'Environ 4 min à pied (300 m)', 'Approx. 4 min walk (300 m)', 3, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`), `walking_time_en` = VALUES(`walking_time_en`), `display_order` = VALUES(`display_order`);

INSERT INTO `property_parking_associations` (`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`)
VALUES (15, 'free_street', 34, 'Environ 4 min à pied (300 m)', 'Approx. 4 min walk (300 m)', 4, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`), `walking_time_en` = VALUES(`walking_time_en`), `display_order` = VALUES(`display_order`);

INSERT INTO `property_parking_associations` (`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`)
VALUES (15, 'free_street', 37, 'Environ 5 min à pied (400 m)', 'Approx. 5 min walk (400 m)', 5, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`), `walking_time_en` = VALUES(`walking_time_en`), `display_order` = VALUES(`display_order`);

-- Parkings privés
INSERT INTO `property_parking_associations` (`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`)
VALUES (15, 'private_parking', 38, 'Environ 4 min à pied (300 m)', 'Approx. 4 min walk (300 m)', 1, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`), `walking_time_en` = VALUES(`walking_time_en`), `display_order` = VALUES(`display_order`);

INSERT INTO `property_parking_associations` (`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`)
VALUES (15, 'private_parking', 39, 'Environ 10 min à pied (800 m)', 'Approx. 10 min walk (800 m)', 2, 1)
ON DUPLICATE KEY UPDATE `walking_time_fr` = VALUES(`walking_time_fr`), `walking_time_en` = VALUES(`walking_time_en`), `display_order` = VALUES(`display_order`);

-- Parc Relais TCL
INSERT INTO `property_parking_associations` (`property_id`, `category`, `parking_item_id`, `transit_time_fr`, `transit_time_en`, `walking_time_fr`, `walking_time_en`, `how_to_reach_fr`, `how_to_reach_en`, `display_order`, `active`)
VALUES (15, 'tcl_pr', 7, 'Environ 10 min en Tram T6 direct (ou 18 min à pied)', 'Approx. 10 min by direct Tram T6 (or 18 min walk)', 'Environ 18 min à pied', 'Approx. 18 min walk', 'Prendre le Tram T6 à l''arrêt Petite Guille (3 min à pied) jusqu''au terminus Mermoz-Pinel.', 'Take Tram T6 from Petite Guille stop (3 min walk) to Mermoz-Pinel terminus.', 1, 1)
ON DUPLICATE KEY UPDATE `transit_time_fr` = VALUES(`transit_time_fr`), `transit_time_en` = VALUES(`transit_time_en`), `walking_time_fr` = VALUES(`walking_time_fr`), `walking_time_en` = VALUES(`walking_time_en`), `how_to_reach_fr` = VALUES(`how_to_reach_fr`), `how_to_reach_en` = VALUES(`how_to_reach_en`), `display_order` = VALUES(`display_order`);


-- 8. HARMONISATION DES ADRESSES DE TOUS LES LOGEMENTS DANS LA TABLE PROPERTIES
UPDATE `properties` SET `address` = '91 rue Bataille, 69008 Lyon' WHERE `id` = 10;
UPDATE `properties` SET `address` = '313 avenue Berthelot, 69008 Lyon' WHERE `id` = 11;
UPDATE `properties` SET `address` = '53 rue de la Cité, 69003 Lyon' WHERE `id` = 12;
UPDATE `properties` SET `address` = '28 rue du Général-Dayan, 69100 Villeurbanne' WHERE `id` = 13;
UPDATE `properties` SET `address` = '178 avenue Félix Faure, 69003 Lyon' WHERE `id` = 14;
UPDATE `properties` SET `address` = '6 bis impasse Jouhet, 69008 Lyon' WHERE `id` = 15;
UPDATE `properties` SET `address` = '16 rue Marietton, 69009 Lyon' WHERE `id` = 16;
UPDATE `properties` SET `address` = '311 cours Émile-Zola, 69100 Villeurbanne' WHERE `id` = 17;
UPDATE `properties` SET `address` = '311 cours Émile-Zola, 69100 Villeurbanne' WHERE `id` = 18;
