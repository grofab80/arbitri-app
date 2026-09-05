-- Seed sample matches for current season 2025/2026.
-- Includes played and scheduled matches across leagues and tournaments.
--
-- Run after:
-- - 2026_07_13_rebuild_financial_categories.sql
-- - 2026_07_13_seed_2025_2026_competitions_teams.sql
-- - 2026_07_13_add_match_day_to_matches.sql
--
-- The script is idempotent by season/competition/match_day/home/away/date.

START TRANSACTION;

SET @season_id := (
    SELECT id
    FROM seasons
    WHERE name = '2025/2026'
      AND status = 'in_corso'
    LIMIT 1
);

-- Force a readable failure if the current season script was not run.
INSERT INTO matches (season_id, competition_id, match_day, home_team_id, away_team_id, match_date, status)
SELECT 0, 0, 1, 0, 0, '2025-09-01', 'scheduled'
WHERE @season_id IS NULL;

CREATE TEMPORARY TABLE tmp_seed_matches (
    competition_name varchar(100) NOT NULL,
    match_day smallint unsigned NOT NULL,
    home_team_name varchar(100) NOT NULL,
    away_team_name varchar(100) NOT NULL,
    referee_name varchar(100) DEFAULT NULL,
    match_date date NOT NULL,
    match_time time DEFAULT NULL,
    status enum('scheduled','played','cancelled') NOT NULL DEFAULT 'scheduled',
    home_goals tinyint unsigned DEFAULT NULL,
    away_goals tinyint unsigned DEFAULT NULL,
    result_type enum('played','walkover_home','walkover_away') NOT NULL DEFAULT 'played',
    walkover_reason varchar(255) DEFAULT NULL,
    notes varchar(255) DEFAULT NULL
) ENGINE=Memory;

INSERT INTO tmp_seed_matches
    (
        competition_name,
        match_day,
        home_team_name,
        away_team_name,
        referee_name,
        match_date,
        match_time,
        status,
        home_goals,
        away_goals,
        result_type,
        walkover_reason,
        notes
    )
VALUES
-- Super League Oro C11
('Super League Oro C11', 1, 'FC Bellavista', 'FC Pavone', 'Mario Rossi', '2025-09-15', '21:00:00', 'played', 2, 1, 'played', NULL, 'Prima giornata'),
('Super League Oro C11', 1, 'FC Biella Calcio', 'Kanavesana19', 'Luca Bianchi', '2025-09-16', '21:00:00', 'played', 1, 1, 'played', NULL, 'Prima giornata'),
('Super League Oro C11', 2, 'HDemia F.B.', 'ASD Piverone Calcio', 'Mario Rossi', '2025-09-22', '21:00:00', 'played', 3, 0, 'walkover_home', 'Squadra ospite assente', 'Vittoria a tavolino'),
('Super League Oro C11', 3, 'FC Pavone', 'FC Biella Calcio', 'Andrea Verdi', '2026-03-10', '21:00:00', 'scheduled', NULL, NULL, 'played', NULL, 'Da disputare'),

-- Super League Argento C11
('Super League Argento C11', 1, 'Real Amis 2020', 'Vistrorio', 'Luca Bianchi', '2025-09-17', '21:00:00', 'played', 0, 2, 'played', NULL, 'Prima giornata'),
('Super League Argento C11', 1, 'S.S. Real Ivrea 2009', 'G.S.D Mezzese 1970', 'Mario Rossi', '2025-09-18', '21:00:00', 'played', 2, 2, 'played', NULL, 'Prima giornata'),
('Super League Argento C11', 2, 'Vistrorio', 'S.S. Real Ivrea 2009', 'Andrea Verdi', '2026-03-12', '21:00:00', 'scheduled', NULL, NULL, 'played', NULL, 'Da disputare'),

-- Master C7
('Master C7', 1, 'Dieci10 C7', 'Victoria FC', 'Andrea Verdi', '2025-09-18', '20:30:00', 'played', 4, 2, 'played', NULL, 'Prima giornata'),
('Master C7', 1, 'Bar L''Incontro', 'RistoPub La Piola', 'Mario Rossi', '2025-09-19', '21:30:00', 'played', 3, 3, 'played', NULL, 'Prima giornata'),
('Master C7', 2, 'La Sfiziosa', 'The Boys', 'Luca Bianchi', '2026-02-10', '20:30:00', 'scheduled', NULL, NULL, 'played', NULL, 'Da disputare'),

-- Over 35 C7
('Over 35 C7', 1, 'Dieci10 C7 O35', 'GR Ristrutturazioni', 'Mario Rossi', '2025-10-02', '20:30:00', 'played', 2, 0, 'played', NULL, 'Prima giornata'),
('Over 35 C7', 1, 'Lamma Boys', 'Atletico Mica Tanto', 'Luca Bianchi', '2026-02-12', '21:30:00', 'scheduled', NULL, NULL, 'played', NULL, 'Da disputare'),

-- Master C5
('Master C5', 1, 'Sfogliatella C5', 'Aston Birra C5', 'Andrea Verdi', '2025-09-20', '20:00:00', 'played', 5, 4, 'played', NULL, 'Prima giornata'),
('Master C5', 1, 'HDemia F.B. C5', 'La Vischese C5', 'Mario Rossi', '2026-02-14', '21:00:00', 'scheduled', NULL, NULL, 'played', NULL, 'Da disputare'),

-- Super League C5
('Super League C5', 1, 'AC Pro Secco', 'Zerb Team', 'Andrea Verdi', '2025-10-08', '20:00:00', 'played', 6, 2, 'played', NULL, 'Prima giornata'),
('Super League C5', 1, 'Val del Lys', 'Calabbria UTD', 'Luca Bianchi', '2026-02-18', '21:00:00', 'scheduled', NULL, NULL, 'played', NULL, 'Da disputare'),

-- Tournaments
('Memorial Gianblanco', 1, 'FC Bellavista', 'Dieci10 C7', 'Mario Rossi', '2026-06-05', '20:30:00', 'played', 2, 2, 'played', NULL, 'Girone'),
('Memorial Gianblanco', 1, 'FC Pavone', 'Victoria FC', 'Andrea Verdi', '2026-06-06', '21:30:00', 'scheduled', NULL, NULL, 'played', NULL, 'Girone'),
('Torneo di Salassa', 1, 'AC Pro Secco', 'Zerb Team', 'Andrea Verdi', '2026-06-24', '20:00:00', 'played', 3, 1, 'played', NULL, 'Girone'),
('Torneo di Aglie', 1, 'HDemia F.B. C5', 'Val del Lys', 'Luca Bianchi', '2026-06-28', '21:00:00', 'scheduled', NULL, NULL, 'played', NULL, 'Girone');

SET @expected_matches := (SELECT COUNT(*) FROM tmp_seed_matches);

SET @resolved_matches := (
    SELECT COUNT(*)
    FROM tmp_seed_matches tm
    JOIN competitions c
        ON c.season_id = @season_id
       AND c.name = tm.competition_name
    JOIN teams ht
        ON ht.name = tm.home_team_name
    JOIN competition_teams hct
        ON hct.competition_id = c.id
       AND hct.team_id = ht.id
    JOIN teams at
        ON at.name = tm.away_team_name
    JOIN competition_teams act
        ON act.competition_id = c.id
       AND act.team_id = at.id
    LEFT JOIN referees r
        ON r.name = tm.referee_name
    WHERE ht.id <> at.id
      AND (tm.referee_name IS NULL OR r.id IS NOT NULL)
);

-- Force a readable FK failure if competitions/teams/referees are missing.
INSERT INTO matches (season_id, competition_id, match_day, home_team_id, away_team_id, match_date, status)
SELECT 0, 0, 1, 0, 0, '2025-09-01', 'scheduled'
WHERE @resolved_matches <> @expected_matches;

INSERT INTO matches
    (
        season_id,
        competition_id,
        match_day,
        home_team_id,
        away_team_id,
        referee_id,
        match_date,
        match_time,
        status,
        home_goals,
        away_goals,
        result_type,
        walkover_reason,
        notes
    )
SELECT
    @season_id,
    c.id,
    tm.match_day,
    ht.id,
    at.id,
    r.id,
    tm.match_date,
    tm.match_time,
    tm.status,
    tm.home_goals,
    tm.away_goals,
    tm.result_type,
    tm.walkover_reason,
    tm.notes
FROM tmp_seed_matches tm
JOIN competitions c
    ON c.season_id = @season_id
   AND c.name = tm.competition_name
JOIN teams ht
    ON ht.name = tm.home_team_name
JOIN teams at
    ON at.name = tm.away_team_name
LEFT JOIN referees r
    ON r.name = tm.referee_name
WHERE NOT EXISTS (
    SELECT 1
    FROM matches existing
    WHERE existing.season_id = @season_id
      AND existing.competition_id = c.id
      AND existing.match_day = tm.match_day
      AND existing.home_team_id = ht.id
      AND existing.away_team_id = at.id
      AND existing.match_date = tm.match_date
);

DROP TEMPORARY TABLE IF EXISTS tmp_seed_matches;

COMMIT;

-- Verification queries:
-- SELECT status, COUNT(*) AS total
-- FROM matches
-- WHERE season_id = (SELECT id FROM seasons WHERE name = '2025/2026')
-- GROUP BY status;
--
-- SELECT c.name AS competition, m.match_day, m.match_date, ht.name AS home_team,
--        at.name AS away_team, m.status, m.home_goals, m.away_goals
-- FROM matches m
-- JOIN competitions c ON c.id = m.competition_id
-- JOIN teams ht ON ht.id = m.home_team_id
-- JOIN teams at ON at.id = m.away_team_id
-- WHERE m.season_id = (SELECT id FROM seasons WHERE name = '2025/2026')
-- ORDER BY c.name, m.match_day, m.match_date;
