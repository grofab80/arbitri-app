-- Add penalty points to competition standings.
-- Negative values represent penalties, for example -3 points.

START TRANSACTION;

ALTER TABLE competition_standings
    ADD COLUMN penalty_points smallint NOT NULL DEFAULT 0 AFTER goals_against;

COMMIT;
