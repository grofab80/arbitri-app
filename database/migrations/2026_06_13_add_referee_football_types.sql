-- Add football type availability to referees.

START TRANSACTION;

ALTER TABLE referees
    ADD COLUMN can_referee_11 tinyint(1) NOT NULL DEFAULT 1 AFTER name,
    ADD COLUMN can_referee_7 tinyint(1) NOT NULL DEFAULT 1 AFTER can_referee_11,
    ADD COLUMN can_referee_5 tinyint(1) NOT NULL DEFAULT 1 AFTER can_referee_7;

COMMIT;
