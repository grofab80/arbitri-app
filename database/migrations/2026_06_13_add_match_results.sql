-- Add match result fields, including walkover wins.

START TRANSACTION;

ALTER TABLE matches
    ADD COLUMN home_goals tinyint unsigned DEFAULT NULL AFTER match_time,
    ADD COLUMN away_goals tinyint unsigned DEFAULT NULL AFTER home_goals,
    ADD COLUMN result_type enum('played','walkover_home','walkover_away') NOT NULL DEFAULT 'played' AFTER status,
    ADD COLUMN walkover_reason varchar(255) DEFAULT NULL AFTER result_type;

COMMIT;
