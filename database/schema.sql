-- ============================================================================
-- MULTI-USER URL SHORTENER PLATFORM (SLink)
-- Database Schema (MySQL / MariaDB)
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `slink_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `slink_db`;

-- ----------------------------------------------------------------------------
-- 1. Table: users
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('superuser', 'user') NOT NULL DEFAULT 'user',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `last_login_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_email` (`email`),
  KEY `idx_users_role_status` (`role`, `status`),
  KEY `idx_users_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. Table: shortlinks
-- Note: short_code uses utf8mb4_bin collation to enforce strict CASE-SENSITIVE
-- uniqueness and fast index lookups (e.g., 'aB92xK' != 'ab92xk').
-- Supports soft delete via `deleted_at` so analytics history is preserved.
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `shortlinks`;
CREATE TABLE `shortlinks` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `short_code` VARCHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `destination_url` TEXT NOT NULL,
  `title` VARCHAR(255) NULL DEFAULT NULL,
  `description` TEXT NULL DEFAULT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `expires_at` DATETIME NULL DEFAULT NULL,
  `max_clicks` INT UNSIGNED NULL DEFAULT NULL,
  `total_clicks` INT UNSIGNED NOT NULL DEFAULT 0,
  `human_clicks` INT UNSIGNED NOT NULL DEFAULT 0,
  `bot_clicks` INT UNSIGNED NOT NULL DEFAULT 0,
  `deleted_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_shortlinks_short_code` (`short_code`),
  KEY `idx_shortlinks_user_status` (`user_id`, `deleted_at`, `status`),
  KEY `idx_shortlinks_deleted_status` (`deleted_at`, `status`),
  KEY `idx_shortlinks_total_clicks` (`total_clicks`),
  KEY `idx_shortlinks_created_at` (`created_at`),
  CONSTRAINT `fk_shortlinks_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. Table: clicks
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `clicks`;
CREATE TABLE `clicks` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `shortlink_id` BIGINT UNSIGNED NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` VARCHAR(512) NULL DEFAULT NULL,
  `browser` VARCHAR(80) NOT NULL DEFAULT 'Unknown',
  `os` VARCHAR(80) NOT NULL DEFAULT 'Unknown',
  `device_type` VARCHAR(40) NOT NULL DEFAULT 'Desktop',
  `country` VARCHAR(80) NOT NULL DEFAULT 'Unknown',
  `city` VARCHAR(100) NOT NULL DEFAULT 'Unknown',
  `referrer` VARCHAR(512) NULL DEFAULT NULL,
  `referrer_domain` VARCHAR(190) NULL DEFAULT NULL,
  `utm_source` VARCHAR(120) NULL DEFAULT NULL,
  `utm_medium` VARCHAR(120) NULL DEFAULT NULL,
  `utm_campaign` VARCHAR(120) NULL DEFAULT NULL,
  `utm_term` VARCHAR(120) NULL DEFAULT NULL,
  `utm_content` VARCHAR(120) NULL DEFAULT NULL,
  `is_bot` TINYINT(1) NOT NULL DEFAULT 0,
  `clicked_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_clicks_shortlink_date` (`shortlink_id`, `clicked_at`),
  KEY `idx_clicks_shortlink_bot` (`shortlink_id`, `is_bot`),
  KEY `idx_clicks_clicked_at` (`clicked_at`),
  KEY `idx_clicks_is_bot` (`is_bot`),
  KEY `idx_clicks_device_type` (`device_type`),
  KEY `idx_clicks_browser` (`browser`),
  KEY `idx_clicks_os` (`os`),
  KEY `idx_clicks_country` (`country`),
  KEY `idx_clicks_referrer_domain` (`referrer_domain`),
  CONSTRAINT `fk_clicks_shortlink`
    FOREIGN KEY (`shortlink_id`) REFERENCES `shortlinks` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 4. Table: activity_logs
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `description` TEXT NULL DEFAULT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activity_logs_user_date` (`user_id`, `created_at`),
  KEY `idx_activity_logs_action` (`action`),
  KEY `idx_activity_logs_created_at` (`created_at`),
  CONSTRAINT `fk_activity_logs_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 5. Table: settings
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT NULL DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 6. Table: login_attempts (Brute-force & Login Rate Limiting)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE `login_attempts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip_address` VARCHAR(45) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `attempted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_login_attempts_ip_date` (`ip_address`, `attempted_at`),
  KEY `idx_login_attempts_email_date` (`email`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

