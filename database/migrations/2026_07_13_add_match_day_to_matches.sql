-- Add match day/round to matches.
-- The value is mandatory for every competition type.

START TRANSACTION;

ALTER TABLE matches
    ADD COLUMN match_day smallint unsigned DEFAULT NULL AFTER competition_id,
    ADD KEY idx_matches_match_day (competition_id, match_day);

UPDATE matches
SET match_day = 1
WHERE match_day IS NULL;

ALTER TABLE matches
    MODIFY match_day smallint unsigned NOT NULL;

COMMIT;

-- Verification query:
-- SELECT id, competition_id, match_day, home_team_id, away_team_id, match_date
-- FROM matches
-- ORDER BY competition_id, match_day, match_date;
