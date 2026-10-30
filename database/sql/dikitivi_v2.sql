-- =====================================================================
-- Dikitivi v2 — mise à jour de la base de données (MySQL 8 / MariaDB 10.5+)
-- Équivalent SQL des migrations Laravel 2026_10_29_000001 → 000005.
--
-- • À exécuter sur la base de l'API (la même que celle du back-office).
-- • Rejouable sans erreur : chaque colonne / index / table n'est créé que s'il
--   manque ; les filtres par défaut ne sont insérés que si la table est vide.
-- • Aucune colonne existante n'est modifiée ni supprimée.
-- • Faire une sauvegarde (mysqldump) avant de l'exécuter.
--
-- Exécution : mysql -h <HOST> -u <USER> -p <DB_NAME> < dikitivi_v2.sql
--             (ou onglet "SQL" / "Importer" de phpMyAdmin, base sélectionnée)
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- 1. medias : champs Admin Media (âge, autoplay, téléchargement,
--    saison / épisode, recommandation)
-- ---------------------------------------------------------------------

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'medias' AND COLUMN_NAME = 'age_range') = 0,
    'ALTER TABLE `medias` ADD COLUMN `age_range` VARCHAR(20) NULL DEFAULT NULL AFTER `for_youth`', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'medias' AND COLUMN_NAME = 'is_autoplay_eligible') = 0,
    'ALTER TABLE `medias` ADD COLUMN `is_autoplay_eligible` TINYINT(1) NOT NULL DEFAULT 1 AFTER `age_range`', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'medias' AND COLUMN_NAME = 'is_downloadable') = 0,
    'ALTER TABLE `medias` ADD COLUMN `is_downloadable` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_autoplay_eligible`', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'medias' AND COLUMN_NAME = 'season_number') = 0,
    'ALTER TABLE `medias` ADD COLUMN `season_number` SMALLINT UNSIGNED NULL DEFAULT NULL AFTER `belongs_to`', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'medias' AND COLUMN_NAME = 'episode_number') = 0,
    'ALTER TABLE `medias` ADD COLUMN `episode_number` SMALLINT UNSIGNED NULL DEFAULT NULL AFTER `season_number`', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- valeurs : jesus, bible_stories, prayer, faith_character, worship_music, christian_life, series
SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'medias' AND COLUMN_NAME = 'faith_step') = 0,
    'ALTER TABLE `medias` ADD COLUMN `faith_step` VARCHAR(40) NULL DEFAULT NULL AFTER `episode_number`', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'medias' AND COLUMN_NAME = 'tags') = 0,
    'ALTER TABLE `medias` ADD COLUMN `tags` JSON NULL DEFAULT NULL AFTER `faith_step`', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'medias' AND INDEX_NAME = 'medias_belongs_to_index') = 0,
    'ALTER TABLE `medias` ADD INDEX `medias_belongs_to_index` (`belongs_to`)', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'medias' AND INDEX_NAME = 'medias_is_public_for_youth_index') = 0,
    'ALTER TABLE `medias` ADD INDEX `medias_is_public_for_youth_index` (`is_public`, `for_youth`)', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------
-- 2. users : réglages des profils enfants gérés par le parent
-- ---------------------------------------------------------------------

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'autoplay_enabled') = 0,
    'ALTER TABLE `users` ADD COLUMN `autoplay_enabled` TINYINT(1) NOT NULL DEFAULT 1 AFTER `parental_code`', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ex. ["bible_stories","jesus","prayer"]
SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'preferred_themes') = 0,
    'ALTER TABLE `users` ADD COLUMN `preferred_themes` JSON NULL DEFAULT NULL AFTER `autoplay_enabled`', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'users_belongs_to_index') = 0,
    'ALTER TABLE `users` ADD INDEX `users_belongs_to_index` (`belongs_to`)', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------
-- 3. watch_progress : historique de lecture par profil (reprise, terminé, fréquence)
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `watch_progress` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT NOT NULL,
    `media_id` BIGINT NOT NULL,
    `position_seconds` INT UNSIGNED NOT NULL DEFAULT 0,
    `duration_seconds` INT UNSIGNED NOT NULL DEFAULT 0,
    `completed` TINYINT(1) NOT NULL DEFAULT 0,
    `play_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `last_watched_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `watch_progress_user_id_media_id_unique` (`user_id`, `media_id`),
    KEY `watch_progress_user_id_last_watched_at_index` (`user_id`, `last_watched_at`),
    KEY `watch_progress_media_id_last_watched_at_index` (`media_id`, `last_watched_at`),
    CONSTRAINT `watch_progress_media_id_foreign` FOREIGN KEY (`media_id`) REFERENCES `medias` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `watch_progress_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4. playback_events : logs de lecture et de performance envoyés par les apps
--    (pas de clé étrangère : un log survit à la suppression d'un média / compte)
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `playback_events` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `event` VARCHAR(40) NOT NULL,
    `user_id` BIGINT NULL DEFAULT NULL,
    `media_id` BIGINT NULL DEFAULT NULL,
    `value_ms` INT UNSIGNED NULL DEFAULT NULL,
    `platform` VARCHAR(20) NULL DEFAULT NULL,
    `device_class` VARCHAR(10) NULL DEFAULT NULL,
    `app_version` VARCHAR(20) NULL DEFAULT NULL,
    `device_id` VARCHAR(64) NULL DEFAULT NULL,
    `props` JSON NULL DEFAULT NULL,
    `occurred_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `playback_events_event_occurred_at_index` (`event`, `occurred_at`),
    KEY `playback_events_media_id_event_index` (`media_id`, `event`),
    KEY `playback_events_user_id_index` (`user_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 5. content_filters : filtres "Jeunesse" administrables depuis le back-office
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `content_filters` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `filter_key` VARCHAR(40) NOT NULL,
    `filter_name` JSON NOT NULL,
    `type_ids` JSON NULL DEFAULT NULL,
    `category_ids` JSON NULL DEFAULT NULL,
    `position` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `content_filters_filter_key_unique` (`filter_key`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Filtres par défaut (= filtres actuels de l'app), insérés uniquement si la table est vide.
-- Un média correspond à un filtre s'il a l'un des types OU l'une des catégories listés.
INSERT INTO `content_filters` (`filter_key`, `filter_name`, `type_ids`, `category_ids`, `position`, `is_active`, `created_at`, `updated_at`)
SELECT d.`filter_key`, d.`filter_name`, d.`type_ids`, d.`category_ids`, d.`position`, 1, NOW(), NOW()
FROM (
    SELECT 'cartoons' AS `filter_key`, '{"fr":"Dessins animés","en":"Cartoons","ln":"Bililingi ya bana"}' AS `filter_name`, '[19]' AS `type_ids`, '[44,6]' AS `category_ids`, 1 AS `position`
    UNION ALL SELECT 'bible', '{"fr":"Histoires bibliques","en":"Bible stories","ln":"Masolo ya Biblia"}', '[]', '[40]', 2
    UNION ALL SELECT 'series', '{"fr":"Séries","en":"Series","ln":"Ba série"}', '[4,5]', '[]', 3
    UNION ALL SELECT 'films', '{"fr":"Films","en":"Movies","ln":"Ba film"}', '[3]', '[]', 4
    UNION ALL SELECT 'music', '{"fr":"Musique & Louange","en":"Music & Worship","ln":"Nzembo & Lokumu"}', '[8,7]', '[42,21]', 5
    UNION ALL SELECT 'teachings', '{"fr":"Enseignements","en":"Teachings","ln":"Mateya"}', '[]', '[14]', 6
    UNION ALL SELECT 'shows', '{"fr":"Émissions","en":"Shows","ln":"Ba émission"}', '[6]', '[13]', 7
    UNION ALL SELECT 'teens', '{"fr":"Adolescents","en":"Teens","ln":"Bilenge"}', '[]', '[50]', 8
) AS d
WHERE NOT EXISTS (SELECT 1 FROM `content_filters`);

-- =====================================================================
-- Fin. Vérification rapide :
--   SHOW COLUMNS FROM medias LIKE 'is_downloadable';
--   SELECT filter_key FROM content_filters ORDER BY position;   -- 8 lignes
-- =====================================================================
