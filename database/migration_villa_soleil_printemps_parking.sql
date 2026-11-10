-- =============================================================================
-- MIGRATION : CONFIGURATION STATIONNEMENT VILLA SOLEIL PRINTEMPS (MEYZIEU)
-- =============================================================================

-- 1. MISE À JOUR DE L'ADRESSE OFFICIELLE DANS LA TABLE PROPERTIES
UPDATE `properties` 
SET `address` = '82 avenue du Carreau, 69330 Meyzieu' 
WHERE `id` = 19;


-- 2. CRÉATION DES RÈGLES GÉNÉRALES DANS PROPERTY_PARKINGS
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
  19,
  'Solutions de stationnement pour votre séjour à la Villa Soleil Printemps au 82 avenue du Carreau (Meyzieu).',
  'Parking solutions for your stay at Villa Soleil Printemps at 82 avenue du Carreau (Meyzieu).',
  'La villa dispose de deux places de stationnement privatives et sécurisées à l''intérieur de la propriété (accès par le portail automatique), équipées de deux bornes de recharge pour véhicules électriques. Le stationnement est également entièrement gratuit dans la rue.',
  'The villa features two private and secure on-site parking spaces inside the property (access via automatic gate), equipped with two electric vehicle charging stations. Free on-street parking is also available.',
  'Le stationnement est entièrement gratuit à l''intérieur de la villa comme sur la voie publique (Avenue du Carreau et rues adjacentes, sans limitation de durée).',
  'Parking is completely free both inside the villa premises and on public streets (Avenue du Carreau and surrounding streets, no time limits).',
  'Le stationnement sur voirie à Meyzieu est gratuit et non payant dans tout ce secteur résidentiel.',
  'On-street parking in Meyzieu is free and unmetered throughout this residential area.',
  'Gratuit 24h/24 et 7j/7',
  'Free 24/7',
  'Gratuit (zone non payante)',
  'Free (unmetered zone)',
  NULL,
  'Pour vous rendre facilement au centre de Lyon en Tramway T3 (Part-Dieu en 20 min), le Parc Relais TCL de Meyzieu Gare est gratuit avec un titre de transport TCL aller-retour validé dans la journée.',
  'To easily reach downtown Lyon via Tramway T3 (Part-Dieu in 20 min), the TCL Meyzieu Gare Park & Ride is free with a same-day validated round-trip TCL ticket.'
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


-- 3. AJOUT DES EMPLACEMENTS GRATUITS DANS LE CATALOGUE (PARKING_FREE_SPOTS)
-- 2 places privatives dans la cour avec bornes de recharge électrique
INSERT INTO `parking_free_spots` (`spot_type`, `name_fr`, `name_en`, `address`, `notes_fr`, `notes_en`)
VALUES (
  'spot',
  '2 places privatives dans la propriété (avec 2 bornes de recharge électrique)',
  '2 private on-site parking spaces (with 2 EV charging stations)',
  '82 avenue du Carreau, 69330 Meyzieu',
  'Stationnement sécurisé dans la cour de la villa derrière le portail motorisé. Deux bornes de recharge pour véhicules électriques sont gracieusement mises à votre disposition.',
  'Secure parking inside the villa courtyard behind the automated gate. Two EV charging stations are provided free of charge for your convenience.'
);
SET @spot_villa_id = LAST_INSERT_ID();

-- Rues gratuites à Meyzieu
INSERT INTO `parking_free_spots` (`spot_type`, `name_fr`, `name_en`, `address`, `notes_fr`, `notes_en`)
VALUES (
  'street',
  'Avenue du Carreau',
  'Avenue du Carreau',
  'Avenue du Carreau, 69330 Meyzieu',
  'Gratuit 7j/7 et 24h/24 le long de l''avenue.',
  'Free 24/7 along the avenue.'
);
SET @street_carreau_id = LAST_INSERT_ID();

INSERT INTO `parking_free_spots` (`spot_type`, `name_fr`, `name_en`, `address`, `notes_fr`, `notes_en`)
VALUES (
  'street',
  'Chemin de Pommier',
  'Chemin de Pommier',
  'Chemin de Pommier, 69330 Meyzieu',
  'Gratuit 7j/7 et 24h/24.',
  'Free 24/7.'
);
SET @street_pommier_id = LAST_INSERT_ID();

INSERT INTO `parking_free_spots` (`spot_type`, `name_fr`, `name_en`, `address`, `notes_fr`, `notes_en`)
VALUES (
  'street',
  'Rue Pierre-Brossolette',
  'Rue Pierre-Brossolette',
  'Rue Pierre-Brossolette, 69330 Meyzieu',
  'Gratuit 7j/7 et 24h/24.',
  'Free 24/7.'
);
SET @street_brossolette_id = LAST_INSERT_ID();


-- 4. AJOUT DU PARC RELAIS TCL DANS LE CATALOGUE (PARKING_TCL_PRS)
INSERT INTO `parking_tcl_prs` (`name_fr`, `name_en`, `address`, `capacity`, `opening_hours_fr`, `opening_hours_en`, `booking_url`, `notes_fr`, `notes_en`)
VALUES (
  'Parking Relais TCL - Meyzieu Gare',
  'TCL Park & Ride - Meyzieu Gare',
  '10 rue de la Gare, 69330 Meyzieu',
  '104 places',
  'Tous les jours de 4h30 à 1h',
  'Every day from 4:30am to 1am',
  'https://www.tcl.fr/se-deplacer/voitures-et-reseau-tcl/parcs-relais-pr-voitures',
  'Réservé aux usagers du réseau TCL munis d''un titre de transport aller-retour validé dans la journée. Connexion directe Tramway T3 vers Part-Dieu.',
  'Reserved for TCL network users holding a validated same-day round-trip transit ticket. Direct Tram T3 to Part-Dieu.'
);
SET @tcl_meyzieu_id = LAST_INSERT_ID();


-- 5. CRÉATION DES ASSOCIATIONS POUR VILLA SOLEIL PRINTEMPS (PROPERTY_PARKING_ASSOCIATIONS)
-- Emplacement privatif avec bornes VE
INSERT INTO `property_parking_associations` (`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`)
VALUES (
  19,
  'free_spot',
  @spot_villa_id,
  'Sur place (dans la propriété)',
  'On site (inside the property)',
  1,
  1
);

-- Rues gratuites
INSERT INTO `property_parking_associations` (`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`)
VALUES (
  19,
  'free_street',
  @street_carreau_id,
  'Au pied de la villa',
  'Right outside the villa',
  2,
  1
);

INSERT INTO `property_parking_associations` (`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`)
VALUES (
  19,
  'free_street',
  @street_pommier_id,
  'Environ 2 min à pied (150 m)',
  'Approx. 2 min walk (150 m)',
  3,
  1
);

INSERT INTO `property_parking_associations` (`property_id`, `category`, `parking_item_id`, `walking_time_fr`, `walking_time_en`, `display_order`, `active`)
VALUES (
  19,
  'free_street',
  @street_brossolette_id,
  'Environ 2 min à pied (150 m)',
  'Approx. 2 min walk (150 m)',
  4,
  1
);

-- Parc Relais TCL
INSERT INTO `property_parking_associations` (`property_id`, `category`, `parking_item_id`, `transit_time_fr`, `transit_time_en`, `walking_time_fr`, `walking_time_en`, `how_to_reach_fr`, `how_to_reach_en`, `display_order`, `active`)
VALUES (
  19,
  'tcl_pr',
  @tcl_meyzieu_id,
  '3 min en bus 85 ou 9 min à pied',
  '3 min by bus 85 or 9 min walk',
  'Environ 9 min à pied (700 m)',
  'Approx. 9 min walk (700 m)',
  'Prendre l''Avenue du Carreau puis la Rue de la Gare jusqu''à la station Tramway T3 Meyzieu Gare.',
  'Follow Avenue du Carreau then Rue de la Gare to Tramway T3 Meyzieu Gare station.',
  1,
  1
);
