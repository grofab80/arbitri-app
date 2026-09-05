-- Normalize team/competition relations.
-- A team can participate in multiple competitions, so competition_teams is the
-- canonical relation table. The teams.competition_id column is kept for now as
-- legacy data to avoid breaking existing local data or future admin screens.

START TRANSACTION;

-- Backfill the relation table from the legacy teams.competition_id column.
-- INSERT IGNORE keeps the migration safe when relations already exist.
INSERT IGNORE INTO competition_teams (competition_id, team_id)
SELECT competition_id, id
FROM teams
WHERE competition_id IS NOT NULL;

COMMIT;

-- Verification query:
-- SELECT t.id, t.name, GROUP_CONCAT(c.name ORDER BY c.name SEPARATOR ', ') AS competitions
-- FROM teams t
-- LEFT JOIN competition_teams ct ON ct.team_id = t.id
-- LEFT JOIN competitions c ON c.id = ct.competition_id
-- GROUP BY t.id, t.name
-- ORDER BY t.name;

-- Future cleanup, only after all code ignores teams.competition_id:
-- ALTER TABLE teams DROP FOREIGN KEY fk_team_competition;
-- ALTER TABLE teams DROP INDEX idx_team_competition;
-- ALTER TABLE teams DROP COLUMN competition_id;
