-- Arbitri App database baseline.
-- Structure only: no users, credentials or operational data are included.
-- Generated from the verified local schema on 2026-09-05.

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `balance_closures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `balance_closures` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `season_id` int(11) NOT NULL,
  `income` decimal(10,2) NOT NULL DEFAULT 0.00,
  `expenses` decimal(10,2) NOT NULL DEFAULT 0.00,
  `profit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_movements` int(11) NOT NULL DEFAULT 0,
  `snapshot_json` longtext DEFAULT NULL,
  `approval_status` enum('draft','approved') NOT NULL DEFAULT 'draft',
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `movements_deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_movements_count` int(11) NOT NULL DEFAULT 0,
  `snapshot_version` smallint(5) unsigned NOT NULL DEFAULT 1,
  `snapshot_hash` char(64) DEFAULT NULL,
  `closed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_balance_closures_season` (`season_id`),
  KEY `idx_balance_closures_approval_status` (`approval_status`),
  KEY `idx_balance_closures_approved_by` (`approved_by`),
  CONSTRAINT `fk_balance_closure_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_balance_closure_season` FOREIGN KEY (`season_id`) REFERENCES `seasons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `level` tinyint(4) NOT NULL CHECK (`level` between 1 and 4),
  `parent_id` int(11) DEFAULT NULL,
  `allow_competition` tinyint(1) DEFAULT 0,
  `allow_team` tinyint(1) DEFAULT 0,
  `allow_referee` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_level` (`level`),
  KEY `idx_parent` (`parent_id`),
  CONSTRAINT `fk_category_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `competition_standings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `competition_standings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `competition_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `rank_position` smallint(5) unsigned DEFAULT NULL,
  `played` smallint(5) unsigned NOT NULL DEFAULT 0,
  `won` smallint(5) unsigned NOT NULL DEFAULT 0,
  `drawn` smallint(5) unsigned NOT NULL DEFAULT 0,
  `lost` smallint(5) unsigned NOT NULL DEFAULT 0,
  `goals_for` smallint(5) unsigned NOT NULL DEFAULT 0,
  `goals_against` smallint(5) unsigned NOT NULL DEFAULT 0,
  `penalty_points` smallint(6) NOT NULL DEFAULT 0,
  `points` smallint(6) NOT NULL DEFAULT 0,
  `notes` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_competition_standings_team` (`competition_id`,`team_id`),
  KEY `idx_competition_standings_competition` (`competition_id`),
  KEY `idx_competition_standings_team` (`team_id`),
  KEY `idx_competition_standings_order` (`competition_id`,`points`,`goals_for`),
  CONSTRAINT `fk_standing_competition` FOREIGN KEY (`competition_id`) REFERENCES `competitions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_standing_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_standing_played` CHECK (`played` >= 0),
  CONSTRAINT `chk_standing_won` CHECK (`won` >= 0),
  CONSTRAINT `chk_standing_drawn` CHECK (`drawn` >= 0),
  CONSTRAINT `chk_standing_lost` CHECK (`lost` >= 0),
  CONSTRAINT `chk_standing_goals_for` CHECK (`goals_for` >= 0),
  CONSTRAINT `chk_standing_goals_against` CHECK (`goals_against` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `competition_teams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `competition_teams` (
  `competition_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  PRIMARY KEY (`competition_id`,`team_id`),
  KEY `team_id` (`team_id`),
  CONSTRAINT `competition_teams_ibfk_1` FOREIGN KEY (`competition_id`) REFERENCES `competitions` (`id`),
  CONSTRAINT `competition_teams_ibfk_2` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `competitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `competitions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `season_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('campionato','torneo') NOT NULL,
  `football_type` enum('11','7','5') NOT NULL,
  `season` varchar(9) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_competitions_season` (`season_id`),
  CONSTRAINT `fk_competition_season` FOREIGN KEY (`season_id`) REFERENCES `seasons` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `designations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `designations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `match_id` int(11) NOT NULL,
  `referee_id` int(11) DEFAULT NULL,
  `status` enum('proposta','confermata','modificata') NOT NULL DEFAULT 'proposta',
  `assignment_type` enum('auto','manual') NOT NULL DEFAULT 'auto',
  `score` decimal(6,2) DEFAULT NULL,
  `score_details_json` longtext DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_designations_match` (`match_id`),
  KEY `idx_designations_referee` (`referee_id`),
  KEY `idx_designations_status` (`status`),
  KEY `idx_designations_created_by` (`created_by`),
  KEY `idx_designations_updated_by` (`updated_by`),
  CONSTRAINT `fk_designation_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_designation_match` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_designation_referee` FOREIGN KEY (`referee_id`) REFERENCES `referees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_designation_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `external_mappings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `external_mappings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `source_id` int(11) NOT NULL,
  `entity_type` varchar(30) NOT NULL,
  `external_id` varchar(100) NOT NULL,
  `local_id` int(11) NOT NULL,
  `external_hash` char(64) DEFAULT NULL,
  `last_seen_at` timestamp NULL DEFAULT NULL,
  `last_synced_at` timestamp NULL DEFAULT NULL,
  `deleted_at_source` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_external_mapping` (`source_id`,`entity_type`,`external_id`),
  KEY `idx_external_mapping_local` (`entity_type`,`local_id`),
  KEY `idx_external_mapping_deleted` (`deleted_at_source`),
  CONSTRAINT `fk_external_mapping_source` FOREIGN KEY (`source_id`) REFERENCES `external_sources` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `external_sources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `external_sources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `base_url` varchar(255) NOT NULL,
  `api_key_encrypted` text DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 0,
  `verify_ssl` tinyint(1) NOT NULL DEFAULT 1,
  `request_timeout` smallint(5) unsigned NOT NULL DEFAULT 20,
  `connection_status` enum('never','success','failed') NOT NULL DEFAULT 'never',
  `last_connection_at` timestamp NULL DEFAULT NULL,
  `last_connection_message` varchar(500) DEFAULT NULL,
  `last_sync_cursor` varchar(40) DEFAULT NULL,
  `last_sync_at` timestamp NULL DEFAULT NULL,
  `last_status` enum('never','success','partial','failed') NOT NULL DEFAULT 'never',
  `last_message` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_external_sources_code` (`code`),
  KEY `idx_external_sources_enabled` (`enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `external_sync_overrides`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `external_sync_overrides` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `source_id` int(11) NOT NULL,
  `entity_type` varchar(30) NOT NULL,
  `external_id` varchar(100) NOT NULL,
  `external_name` varchar(255) DEFAULT NULL,
  `football_type` enum('5','7','11') DEFAULT NULL,
  `season_local_id` int(11) DEFAULT NULL,
  `ignored` tinyint(1) NOT NULL DEFAULT 0,
  `source_context_json` longtext DEFAULT NULL,
  `issue_message` varchar(500) DEFAULT NULL,
  `last_error_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_external_sync_override` (`source_id`,`entity_type`,`external_id`),
  KEY `idx_external_sync_override_issue` (`source_id`,`issue_message`(100)),
  KEY `idx_external_sync_override_season` (`season_local_id`),
  CONSTRAINT `fk_external_sync_override_season` FOREIGN KEY (`season_local_id`) REFERENCES `seasons` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_external_sync_override_source` FOREIGN KEY (`source_id`) REFERENCES `external_sources` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `address` varchar(150) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(50) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'Italia',
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `geocoded_at` timestamp NULL DEFAULT NULL,
  `can_host_11` tinyint(1) NOT NULL DEFAULT 1,
  `can_host_7` tinyint(1) NOT NULL DEFAULT 1,
  `can_host_5` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_fields_name` (`name`),
  KEY `idx_fields_active` (`is_active`),
  KEY `idx_fields_province` (`province`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `matches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `matches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `season_id` int(11) NOT NULL,
  `competition_id` int(11) NOT NULL,
  `match_day` smallint(5) unsigned NOT NULL,
  `difficulty_rating` tinyint(3) unsigned NOT NULL DEFAULT 3,
  `home_team_id` int(11) NOT NULL,
  `away_team_id` int(11) NOT NULL,
  `referee_id` int(11) DEFAULT NULL,
  `field_id` int(11) DEFAULT NULL,
  `match_date` date NOT NULL,
  `match_time` time DEFAULT NULL,
  `home_goals` tinyint(3) unsigned DEFAULT NULL,
  `away_goals` tinyint(3) unsigned DEFAULT NULL,
  `status` enum('scheduled','played','cancelled') NOT NULL DEFAULT 'scheduled',
  `result_type` enum('played','walkover_home','walkover_away') NOT NULL DEFAULT 'played',
  `walkover_reason` varchar(255) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_matches_season` (`season_id`),
  KEY `idx_matches_competition` (`competition_id`),
  KEY `idx_matches_home_team` (`home_team_id`),
  KEY `idx_matches_away_team` (`away_team_id`),
  KEY `idx_matches_date` (`match_date`),
  KEY `idx_matches_referee` (`referee_id`),
  KEY `idx_matches_field` (`field_id`),
  KEY `idx_matches_match_day` (`competition_id`,`match_day`),
  CONSTRAINT `fk_match_away_team` FOREIGN KEY (`away_team_id`) REFERENCES `teams` (`id`),
  CONSTRAINT `fk_match_competition` FOREIGN KEY (`competition_id`) REFERENCES `competitions` (`id`),
  CONSTRAINT `fk_match_field` FOREIGN KEY (`field_id`) REFERENCES `fields` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_match_home_team` FOREIGN KEY (`home_team_id`) REFERENCES `teams` (`id`),
  CONSTRAINT `fk_match_referee` FOREIGN KEY (`referee_id`) REFERENCES `referees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_match_season` FOREIGN KEY (`season_id`) REFERENCES `seasons` (`id`),
  CONSTRAINT `chk_match_different_teams` CHECK (`home_team_id` <> `away_team_id`),
  CONSTRAINT `chk_match_difficulty_rating` CHECK (`difficulty_rating` between 1 and 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `movements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `season_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `movement_date` date NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `competition_id` int(11) DEFAULT NULL,
  `team_id` int(11) DEFAULT NULL,
  `referee_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category_id`),
  KEY `idx_date` (`movement_date`),
  KEY `idx_competition` (`competition_id`),
  KEY `idx_team` (`team_id`),
  KEY `idx_referee` (`referee_id`),
  KEY `idx_movements_season` (`season_id`),
  CONSTRAINT `fk_movement_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `fk_movement_competition` FOREIGN KEY (`competition_id`) REFERENCES `competitions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_movement_referee` FOREIGN KEY (`referee_id`) REFERENCES `referees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_movement_season` FOREIGN KEY (`season_id`) REFERENCES `seasons` (`id`),
  CONSTRAINT `fk_movement_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `operational_alert_snapshots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `operational_alert_snapshots` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `alert_code` varchar(100) NOT NULL,
  `count_value` int(11) NOT NULL DEFAULT 0,
  `last_checked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_operational_alert_snapshots_code` (`alert_code`),
  CONSTRAINT `chk_operational_alert_snapshot_count` CHECK (`count_value` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `operational_notification_reads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `operational_notification_reads` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `dismissed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`notification_id`,`user_id`),
  KEY `idx_operational_notification_reads_user` (`user_id`),
  CONSTRAINT `fk_operational_notification_read_notification` FOREIGN KEY (`notification_id`) REFERENCES `operational_notifications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_operational_notification_read_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `operational_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `operational_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `alert_code` varchar(100) NOT NULL,
  `title` varchar(150) NOT NULL,
  `message` varchar(255) NOT NULL,
  `delta` int(11) NOT NULL DEFAULT 1,
  `count_value` int(11) NOT NULL DEFAULT 0,
  `href` varchar(255) DEFAULT NULL,
  `required_permissions_json` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_operational_notifications_alert` (`alert_code`),
  KEY `idx_operational_notifications_created_at` (`created_at`),
  CONSTRAINT `chk_operational_notification_delta` CHECK (`delta` > 0),
  CONSTRAINT `chk_operational_notification_count` CHECK (`count_value` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(100) NOT NULL,
  `description` varchar(255) NOT NULL,
  `scope` enum('page','action','system') NOT NULL DEFAULT 'action',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_permissions_code` (`code`),
  KEY `idx_permissions_scope` (`scope`),
  KEY `idx_permissions_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `profile_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `profile_permissions` (
  `profile_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`profile_id`,`permission_id`),
  KEY `idx_profile_permissions_permission` (`permission_id`),
  CONSTRAINT `fk_profile_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_profile_permissions_profile` FOREIGN KEY (`profile_id`) REFERENCES `profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_profiles_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `referee_availabilities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `referee_availabilities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `referee_id` int(11) NOT NULL,
  `type` enum('recurring','specific') NOT NULL,
  `weekday` tinyint(3) unsigned DEFAULT NULL,
  `available_date` date DEFAULT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_referee_availabilities_referee` (`referee_id`),
  KEY `idx_referee_availabilities_recurring` (`referee_id`,`weekday`,`start_time`,`end_time`),
  KEY `idx_referee_availabilities_specific` (`referee_id`,`available_date`,`start_time`,`end_time`),
  CONSTRAINT `fk_referee_availability_referee` FOREIGN KEY (`referee_id`) REFERENCES `referees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_referee_availability_weekday` CHECK (`weekday` is null or `weekday` between 1 and 7),
  CONSTRAINT `chk_referee_availability_time_range` CHECK (`start_time` < `end_time`),
  CONSTRAINT `chk_referee_availability_type_fields` CHECK (`type` = 'recurring' and `weekday` is not null and `available_date` is null or `type` = 'specific' and `weekday` is null and `available_date` is not null)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `referee_team_blacklist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `referee_team_blacklist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `referee_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_referee_team_blacklist` (`referee_id`,`team_id`),
  KEY `idx_referee_team_blacklist_referee` (`referee_id`),
  KEY `idx_referee_team_blacklist_team` (`team_id`),
  KEY `idx_referee_team_blacklist_active` (`active`),
  CONSTRAINT `fk_referee_team_blacklist_referee` FOREIGN KEY (`referee_id`) REFERENCES `referees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_referee_team_blacklist_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `referees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `referees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `rating` tinyint(3) unsigned NOT NULL DEFAULT 3,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(50) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'Italia',
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `geocoded_at` timestamp NULL DEFAULT NULL,
  `can_referee_11` tinyint(1) NOT NULL DEFAULT 1,
  `can_referee_7` tinyint(1) NOT NULL DEFAULT 1,
  `can_referee_5` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_referees_city` (`city`),
  KEY `idx_referees_province` (`province`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `seasons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `seasons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(9) NOT NULL,
  `starts_on` date NOT NULL,
  `ends_on` date NOT NULL,
  `status` enum('nuovo','in_corso','chiuso') NOT NULL DEFAULT 'nuovo',
  `is_current` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_seasons_name` (`name`),
  KEY `idx_seasons_current` (`is_current`),
  KEY `idx_seasons_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `schema_migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `schema_migrations` (
  `version` varchar(100) NOT NULL,
  `description` varchar(255) NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sync_run_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sync_run_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sync_run_id` bigint(20) unsigned NOT NULL,
  `entity_type` varchar(30) NOT NULL,
  `external_id` varchar(100) NOT NULL,
  `local_id` int(11) DEFAULT NULL,
  `action` varchar(30) NOT NULL,
  `message` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sync_run_items_run` (`sync_run_id`),
  KEY `idx_sync_run_items_entity` (`entity_type`,`external_id`),
  KEY `idx_sync_run_items_action` (`action`),
  CONSTRAINT `fk_sync_run_item_run` FOREIGN KEY (`sync_run_id`) REFERENCES `sync_runs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sync_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sync_runs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `source_id` int(11) NOT NULL,
  `sync_mode` enum('full','incremental') NOT NULL DEFAULT 'incremental',
  `trigger_type` enum('manual','scheduled') NOT NULL DEFAULT 'manual',
  `status` enum('running','success','partial','failed') NOT NULL DEFAULT 'running',
  `cursor_from` varchar(40) DEFAULT NULL,
  `cursor_to` varchar(40) DEFAULT NULL,
  `total_count` int(10) unsigned NOT NULL DEFAULT 0,
  `processed_count` int(10) unsigned NOT NULL DEFAULT 0,
  `current_entity` varchar(30) DEFAULT NULL,
  `heartbeat_at` timestamp NULL DEFAULT NULL,
  `created_count` int(10) unsigned NOT NULL DEFAULT 0,
  `updated_count` int(10) unsigned NOT NULL DEFAULT 0,
  `skipped_count` int(10) unsigned NOT NULL DEFAULT 0,
  `ignored_count` int(10) unsigned NOT NULL DEFAULT 0,
  `failed_count` int(10) unsigned NOT NULL DEFAULT 0,
  `deleted_count` int(10) unsigned NOT NULL DEFAULT 0,
  `message` varchar(500) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `finished_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sync_runs_source_started` (`source_id`,`started_at`),
  KEY `idx_sync_runs_status` (`status`),
  KEY `fk_sync_run_user` (`created_by`),
  CONSTRAINT `fk_sync_run_source` FOREIGN KEY (`source_id`) REFERENCES `external_sources` (`id`),
  CONSTRAINT `fk_sync_run_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `teams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `teams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `field_id` int(11) DEFAULT NULL,
  `competition_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_team_competition` (`competition_id`),
  KEY `idx_teams_field` (`field_id`),
  CONSTRAINT `fk_team_competition` FOREIGN KEY (`competition_id`) REFERENCES `competitions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_team_field` FOREIGN KEY (`field_id`) REFERENCES `fields` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `profile_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_users_profile` (`profile_id`),
  CONSTRAINT `fk_user_profile` FOREIGN KEY (`profile_id`) REFERENCES `profiles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
