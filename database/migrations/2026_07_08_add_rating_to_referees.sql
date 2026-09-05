-- Add manual rating to referees.

START TRANSACTION;

ALTER TABLE referees
    ADD COLUMN rating tinyint unsigned NOT NULL DEFAULT 3 AFTER name;

COMMIT;
