-- ==============================================================================
-- BASE DE DONNÉES : PORTAIL VOYAGEUR - CARRÉ D'HÔTES
-- Version MySQL : 8.4+ (Compatible MySQL 8.0 & 8.4)
-- ==============================================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;

-- ------------------------------------------------------------------------------
-- Table : properties (Logements)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `properties` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `slug` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Identifiant URL unique (ex: maisonnette-en-jouhet)',
    `name` VARCHAR(255) NOT NULL COMMENT 'Nom public du logement',
    `guide_fr_url` VARCHAR(1000) DEFAULT NULL COMMENT 'Lien Google Drive / PDF vers le livret FR',
    `guide_en_url` VARCHAR(1000) DEFAULT NULL COMMENT 'Lien Google Drive / PDF vers le livret EN',
    `wifi_ssid` VARCHAR(100) DEFAULT NULL COMMENT 'Nom du réseau WiFi (optionnel)',
    `wifi_password` VARCHAR(100) DEFAULT NULL COMMENT 'Clé de sécurité WiFi (optionnel)',
    `address` VARCHAR(255) DEFAULT NULL COMMENT 'Adresse du logement (optionnel)',
    `active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = visible, 0 = masqué',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_properties_slug_active` (`slug`, `active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- Table : videos (Vidéos & Modes d'emploi des équipements)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `videos` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `property_id` INT UNSIGNED NOT NULL COMMENT 'Référence vers la table properties',
    `slug` VARCHAR(100) NOT NULL COMMENT 'Identifiant URL unique de la vidéo (ex: machine-a-laver)',
    `title_fr` VARCHAR(255) NOT NULL COMMENT 'Nom équipement en français (ex: Machine à laver)',
    `title_en` VARCHAR(255) DEFAULT NULL COMMENT 'Nom équipement en anglais (ex: Washing machine)',
    `icon` VARCHAR(50) NOT NULL DEFAULT '💡' COMMENT 'Emoji ou icône représentative (ex: 🫧, 🍳)',
    `icon_bg_color` VARCHAR(50) NOT NULL DEFAULT 'bg-blue-50' COMMENT 'Classe Tailwind pour fond icône',
    `icon_text_color` VARCHAR(50) NOT NULL DEFAULT 'text-blue-500' COMMENT 'Classe Tailwind pour couleur icône',
    `video_url` VARCHAR(1000) NOT NULL COMMENT 'Lien direct vers la vidéo (Google Drive, Cloudflare R2, etc.)',
    `display_order` INT NOT NULL DEFAULT 0 COMMENT 'Ordre d''affichage',
    `active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = actif, 0 = inactif',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_videos_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
    INDEX `idx_videos_property_slug` (`property_id`, `slug`),
    INDEX `idx_videos_property_order` (`property_id`, `display_order`, `active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
-- ======================================================================
-- DONNÉES INITIALES (SEED) - CARRÉ D'HÔTES
-- Import des 9 logements existants et des vidéos explicatives associées
-- ======================================================================
SET NAMES utf8mb4;

-- Nettoyage préalable si ré-exécution
DELETE FROM videos;
DELETE FROM properties;

-- ----------------------------------------------------------------------
-- Logement : Bataille (bataille)
-- ----------------------------------------------------------------------
INSERT INTO properties (slug, name, guide_fr_url, guide_en_url, active) VALUES ('bataille', 'Bataille', 'https://drive.google.com/file/d/1x7yyx4NbnvjP4gigDsvjli4tOENQ98sU/view?usp=sharing', 'https://drive.google.com/file/d/1q5lbxNLdQQI-_rFBWjPHn6r-6IOiTLCI/view?usp=sharing', 1);
SET @last_prop_id = LAST_INSERT_ID();
-- Vidéos pour Bataille
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'machine-a-laver', 'Machine à laver', 'Washing machine', '🫧', 'bg-blue-50', 'text-blue-500', 'https://drive.google.com/file/d/1NMkCkVSiQxLAURf384V_safGuTCZOn8s/view?usp=drive_link', 1, 1);
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'plaque-induction', 'Plaque induction', 'Induction stove', '🍳', 'bg-orange-50', 'text-orange-500', 'https://drive.google.com/file/d/1aY7199w2PtwY6X7Zr0GBgZggL0aDEfds/view?usp=drive_link', 2, 1);
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'micro-ondes', 'Micro-ondes', 'Microwave', '♨️', 'bg-gray-100', 'text-gray-600', 'https://drive.google.com/file/d/13du7fbTv0et0B_NHjOu62a3lsD6Odop3/view?usp=drive_link', 3, 1);
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'bouilloire', 'Bouilloire', 'Kettle', '🫖', 'bg-teal-50', 'text-teal-500', 'https://drive.google.com/file/d/1xuuLjmOh3LzsCEYKIrcTaWZURdMT9FDg/view?usp=drive_link', 4, 1);
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'television', 'Télévision', 'Television', '📺', 'bg-zinc-100', 'text-zinc-600', 'https://drive.google.com/file/d/1LezEzqApQWpYI_53IzFefZuABL_eJdkw/view?usp=drive_link', 5, 1);
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'machine-a-cafe', 'Machine à café', 'Coffee maker', '☕', 'bg-amber-50', 'text-amber-600', 'https://drive.google.com/file/d/1HcMK0c2A7iMrOz8-c6kfS5wTSyY1tuSo/view?usp=drive_link', 6, 1);
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'canape-convertible', 'Canapé convertible', 'Sofa bed', '🛋️', 'bg-rose-50', 'text-rose-500', 'https://drive.google.com/file/d/1asJroLOSOiLJAthgJP_erkG9WxHniUa9/view?usp=drive_link', 7, 1);
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'radiateur', 'Radiateur', 'Radiator', '🌡️', 'bg-red-50', 'text-red-500', 'https://drive.google.com/file/d/1f3HOr5h4sFHwwDOsuh6WrjTtP4ikudx-/view?usp=sharing', 8, 1);
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'seche-serviette', 'Sèche-serviette', 'Heated towel rail', '🛁', 'bg-sky-50', 'text-sky-500', 'https://drive.google.com/file/d/1ImAKg96Utuh8mYU_926uKSwxGApzc3wL/view?usp=sharing', 9, 1);
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'volet-roulant', 'Volet roulant', 'Roller shutter', '🪟', 'bg-indigo-50', 'text-indigo-500', 'https://drive.google.com/file/d/1_0pSD1_heKwU3fuCektSkHboieVeYpLS/view?usp=sharing', 10, 1);
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'arrivee-d-eau', 'Arrivée d''eau', 'Water supply', '💧', 'bg-cyan-50', 'text-cyan-600', 'https://drive.google.com/file/d/1Ku-w2REVGjRhLwHeo8zhtRI6FCxQRMZG/view?usp=sharing', 11, 1);
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'compteur-electrique', 'Compteur électrique', 'Electric meter', '⚡', 'bg-yellow-50', 'text-yellow-500', 'https://drive.google.com/file/d/1OM1T0sdQxfdB6oetBgvhh2-5eriO-Qfm/view?usp=sharing', 12, 1);

-- ----------------------------------------------------------------------
-- Logement : Berthelot Design (berthelot-design)
-- ----------------------------------------------------------------------
INSERT INTO properties (slug, name, guide_fr_url, guide_en_url, active) VALUES ('berthelot-design', 'Berthelot Design', 'https://drive.google.com/file/d/1zAdHHdK939qBsxs6niy96GhEoi5igaWA/view?usp=sharing', 'https://drive.google.com/file/d/19qN3w97vKEcfh-4_oo0SWAob9AoYfTb1/view?usp=sharing', 1);
SET @last_prop_id = LAST_INSERT_ID();

-- ----------------------------------------------------------------------
-- Logement : Chez Denise Studio Cosy (chez-denise-studio-cosy)
-- ----------------------------------------------------------------------
INSERT INTO properties (slug, name, guide_fr_url, guide_en_url, active) VALUES ('chez-denise-studio-cosy', 'Chez Denise Studio Cosy', 'https://drive.google.com/file/d/1jPpSFf6ZJ_pNWGhgeQ6yvA4d7v8GJ2Ks/view?usp=sharing', 'https://drive.google.com/file/d/14imaiZf6qakRhp1L-lkmFngWhca1TA9U/view?usp=sharing', 1);
SET @last_prop_id = LAST_INSERT_ID();

-- ----------------------------------------------------------------------
-- Logement : Cosy et Design (cosy-et-design)
-- ----------------------------------------------------------------------
INSERT INTO properties (slug, name, guide_fr_url, guide_en_url, active) VALUES ('cosy-et-design', 'Cosy et Design', 'https://drive.google.com/file/d/1ASzg5BjfSsfURyAXBcxZHSryOCvwQD8R/view?usp=sharing', 'https://drive.google.com/file/d/1OZG5-21emm2pyTbr1syqkCsM1K08a6pe/view?usp=sharing', 1);
SET @last_prop_id = LAST_INSERT_ID();

-- ----------------------------------------------------------------------
-- Logement : L’Atelier Cosy Félix (latelier-cosy-felix)
-- ----------------------------------------------------------------------
INSERT INTO properties (slug, name, guide_fr_url, guide_en_url, active) VALUES ('latelier-cosy-felix', 'L’Atelier Cosy Félix', 'https://drive.google.com/file/d/1lnFUEqcgk4kqtYBTBP8X41TIczoLGOIM/view?usp=sharing', 'https://drive.google.com/file/d/1GWjlRxHxJ_OAL29Lr79XtaIigAAtwU4H/view?usp=sharing', 1);
SET @last_prop_id = LAST_INSERT_ID();

-- ----------------------------------------------------------------------
-- Logement : Maisonnette en Jouhet (maisonnette-en-jouhet)
-- ----------------------------------------------------------------------
INSERT INTO properties (slug, name, guide_fr_url, guide_en_url, active) VALUES ('maisonnette-en-jouhet', 'Maisonnette en Jouhet', 'https://drive.google.com/file/d/1gr3S2qp1ViC8DQpRMDSct68XuN95yiEV/view?usp=sharing', 'https://drive.google.com/file/d/1nif9dnZykNjFuMi_VnRtaS0ZHudNQGT2/view?usp=sharing', 1);
SET @last_prop_id = LAST_INSERT_ID();
-- Vidéos pour Maisonnette en Jouhet
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'machine-a-laver', 'Machine à laver', 'Washing machine', '🫧', 'bg-blue-50', 'text-blue-500', 'https://drive.google.com/file/d/1AMmACiyOT33mBu6jpC2GqiJ5Di4JzgwY/view?usp=drive_link', 1, 1);
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'plaque-a-induction', 'Plaque à induction', 'Induction hob', '🍳', 'bg-orange-50', 'text-orange-500', 'https://drive.google.com/file/d/1PWxV0_N8eC51SmYEj41ewvemumiQ_ME-/view?usp=drive_link', 2, 1);
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'micro-ondes', 'Micro-ondes', 'Microwave', '♨️', 'bg-gray-100', 'text-gray-600', 'https://drive.google.com/file/d/1tqGn4vEC1fKPCqNxMuMcsmNqfh0MNZH2/view?usp=sharing', 3, 1);
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'bouilloire', 'Bouilloire', 'Kettle', '🫖', 'bg-teal-50', 'text-teal-500', 'https://drive.google.com/file/d/1nefezeosACVoJTYxtEuNKUc8CWZcuAI9/view?usp=drive_link', 4, 1);
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'television', 'Télévision', 'Television', '📺', 'bg-zinc-100', 'text-zinc-600', 'https://drive.google.com/file/d/1n5jEyHAm1rY8hlQcM_VImINSSLC7YPM5/view?usp=drive_link', 5, 1);
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'machine-a-cafe', 'Machine à café', 'Coffee maker', '☕', 'bg-amber-50', 'text-amber-600', 'https://drive.google.com/file/d/10D-k9fEciB5_SB875CsE5UYGRNAmK7ov/view?usp=drive_link', 6, 1);
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'canape-convertible', 'Canapé convertible', 'Sofa bed', '🛋️', 'bg-rose-50', 'text-rose-500', 'https://drive.google.com/file/d/1pFyrtDEFNFt6xHlmir5cgSPIZk5eb6Ym/view?usp=drive_link', 7, 1);
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'radiateur', 'Radiateur', 'Heater / radiator', '🌡️', 'bg-red-50', 'text-red-500', 'https://drive.google.com/file/d/1rUFkmx_L_em1rvh59WjSanP92oi7pDW5/view?usp=drive_link', 8, 1);
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'seche-serviettes', 'Sèche-serviettes', 'Towel radiator', '🛁', 'bg-orange-50', 'text-orange-500', 'https://drive.google.com/file/d/1A522PfPcQBgC_shcxkK8Y58WR1KxPKEL/view?usp=drive_link', 9, 1);
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'hotte-aspirante', 'Hotte aspirante', 'Extractor hood', '💨', 'bg-slate-100', 'text-slate-600', 'https://drive.google.com/file/d/1JuQUDcMUp0Z3UJCw8uLBcKriWn7xm6kV/view?usp=drive_link', 10, 1);
INSERT INTO videos (property_id, slug, title_fr, title_en, icon, icon_bg_color, icon_text_color, video_url, display_order, active) VALUES (@last_prop_id, 'grille-pain', 'Grille-pain', 'Toaster', '🍞', 'bg-yellow-50', 'text-yellow-600', 'https://drive.google.com/file/d/1N37VZamdg3e-niSANO48Ap91GwUtwd_2/view?usp=drive_link', 11, 1);

-- ----------------------------------------------------------------------
-- Logement : Marietton Cosy (marietton-cosy)
-- ----------------------------------------------------------------------
INSERT INTO properties (slug, name, guide_fr_url, guide_en_url, active) VALUES ('marietton-cosy', 'Marietton Cosy', 'https://drive.google.com/file/d/1Rtt8vDKmuxCxvkeqP5Pa_YeVrvIptAKT/view?usp=sharing', 'https://drive.google.com/file/d/13GuGwXIq--6wwWdrfhR17G6_F0lPTIsH/view?usp=sharing', 1);
SET @last_prop_id = LAST_INSERT_ID();

-- ----------------------------------------------------------------------
-- Logement : Studio Cosy de Mel (studio-cosy-mel)
-- ----------------------------------------------------------------------
INSERT INTO properties (slug, name, guide_fr_url, guide_en_url, active) VALUES ('studio-cosy-mel', 'Studio Cosy de Mel', 'https://drive.google.com/file/d/191L29MG_TkTZxRAeu5ONPDtZmSbFvVVy/view?usp=drive_link', 'https://drive.google.com/file/d/1HHUxCYmKHK0DG8sJnDNUhlUhcydHnH13/view?usp=drive_link', 1);
SET @last_prop_id = LAST_INSERT_ID();

-- ----------------------------------------------------------------------
-- Logement : Studio Cosy de Paul (studio-cosy-paul)
-- ----------------------------------------------------------------------
INSERT INTO properties (slug, name, guide_fr_url, guide_en_url, active) VALUES ('studio-cosy-paul', 'Studio Cosy de Paul', 'https://drive.google.com/file/d/1Ux23AYhTgZSg_CebooeSHSsQIQXgwQ6G/view?usp=sharing', 'https://drive.google.com/file/d/1pcsmBsUj7K4v1T93hsbBY2d7bGVnOzUU/view?usp=drive_link', 1);
SET @last_prop_id = LAST_INSERT_ID();
