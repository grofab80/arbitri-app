-- Seed sample competitions and teams for current season 2025/2026.
-- Data is inspired by the legacy `asdaca` database, but normalized for
-- arbitri-app.
--
-- Run after:
-- - 2026_07_13_rebuild_financial_categories.sql
--
-- The script is idempotent by competition/team name for the 2025/2026 season.

START TRANSACTION;

SET @season_id := (
    SELECT id
    FROM seasons
    WHERE name = '2025/2026'
      AND status = 'in_corso'
    LIMIT 1
);

-- Force a readable failure if the reset/current season script was not run.
INSERT INTO competitions (season_id, name, type, football_type, season)
SELECT 0, 'ERRORE: stagione 2025/2026 in corso non trovata', 'campionato', '11', '2025/2026'
WHERE @season_id IS NULL;

CREATE TEMPORARY TABLE tmp_seed_competitions (
    name varchar(100) NOT NULL,
    type enum('campionato','torneo') NOT NULL,
    football_type enum('11','7','5') NOT NULL,
    PRIMARY KEY (name)
) ENGINE=Memory;

INSERT INTO tmp_seed_competitions (name, type, football_type)
VALUES
('Super League Oro C11', 'campionato', '11'),
('Super League Argento C11', 'campionato', '11'),
('Master C7', 'campionato', '7'),
('Over 35 C7', 'campionato', '7'),
('Master C5', 'campionato', '5'),
('Super League C5', 'campionato', '5'),
('Memorial Gianblanco', 'torneo', '7'),
('Torneo di Salassa', 'torneo', '5'),
('Torneo di Aglie', 'torneo', '5');

INSERT INTO competitions (season_id, name, type, football_type, season)
SELECT @season_id, tc.name, tc.type, tc.football_type, '2025/2026'
FROM tmp_seed_competitions tc
WHERE NOT EXISTS (
    SELECT 1
    FROM competitions c
    WHERE c.season_id = @season_id
      AND c.name = tc.name
);

CREATE TEMPORARY TABLE tmp_seed_team_primary (
    team_name varchar(100) NOT NULL,
    primary_competition varchar(100) NOT NULL,
    PRIMARY KEY (team_name)
) ENGINE=Memory;

INSERT INTO tmp_seed_team_primary (team_name, primary_competition)
VALUES
-- Calcio a 11
('FC Bellavista', 'Super League Oro C11'),
('FC Biella Calcio', 'Super League Oro C11'),
('FC Pavone', 'Super League Oro C11'),
('ASD Piverone Calcio', 'Super League Oro C11'),
('HDemia F.B.', 'Super League Oro C11'),
('Kanavesana19', 'Super League Oro C11'),
('Real Amis 2020', 'Super League Argento C11'),
('S.S. Real Ivrea 2009', 'Super League Argento C11'),
('Vistrorio', 'Super League Argento C11'),
('G.S.D Mezzese 1970', 'Super League Argento C11'),

-- Calcio a 7
('Dieci10 C7', 'Master C7'),
('Bar L''Incontro', 'Master C7'),
('RistoPub La Piola', 'Master C7'),
('La Sfiziosa', 'Master C7'),
('The Boys', 'Master C7'),
('Victoria FC', 'Master C7'),
('Dieci10 C7 O35', 'Over 35 C7'),
('GR Ristrutturazioni', 'Over 35 C7'),
('Lamma Boys', 'Over 35 C7'),
('Atletico Mica Tanto', 'Over 35 C7'),

-- Calcio a 5
('Sfogliatella C5', 'Master C5'),
('Aston Birra C5', 'Master C5'),
('HDemia F.B. C5', 'Master C5'),
('La Vischese C5', 'Master C5'),
('AC Pro Secco', 'Super League C5'),
('Zerb Team', 'Super League C5'),
('Val del Lys', 'Super League C5'),
('Calabbria UTD', 'Super League C5');

INSERT INTO teams (name, competition_id)
SELECT
    tp.team_name,
    c.id
FROM tmp_seed_team_primary tp
JOIN competitions c
    ON c.season_id = @season_id
   AND c.name = tp.primary_competition
WHERE NOT EXISTS (
    SELECT 1
    FROM teams t
    WHERE t.name = tp.team_name
);

CREATE TEMPORARY TABLE tmp_seed_competition_teams (
    competition_name varchar(100) NOT NULL,
    team_name varchar(100) NOT NULL,
    PRIMARY KEY (competition_name, team_name)
) ENGINE=Memory;

INSERT INTO tmp_seed_competition_teams (competition_name, team_name)
VALUES
-- Campionati C11
('Super League Oro C11', 'FC Bellavista'),
('Super League Oro C11', 'FC Biella Calcio'),
('Super League Oro C11', 'FC Pavone'),
('Super League Oro C11', 'ASD Piverone Calcio'),
('Super League Oro C11', 'HDemia F.B.'),
('Super League Oro C11', 'Kanavesana19'),
('Super League Argento C11', 'Real Amis 2020'),
('Super League Argento C11', 'S.S. Real Ivrea 2009'),
('Super League Argento C11', 'Vistrorio'),
('Super League Argento C11', 'G.S.D Mezzese 1970'),

-- Campionati C7
('Master C7', 'Dieci10 C7'),
('Master C7', 'Bar L''Incontro'),
('Master C7', 'RistoPub La Piola'),
('Master C7', 'La Sfiziosa'),
('Master C7', 'The Boys'),
('Master C7', 'Victoria FC'),
('Over 35 C7', 'Dieci10 C7 O35'),
('Over 35 C7', 'GR Ristrutturazioni'),
('Over 35 C7', 'Lamma Boys'),
('Over 35 C7', 'Atletico Mica Tanto'),

-- Campionati C5
('Master C5', 'Sfogliatella C5'),
('Master C5', 'Aston Birra C5'),
('Master C5', 'HDemia F.B. C5'),
('Master C5', 'La Vischese C5'),
('Super League C5', 'AC Pro Secco'),
('Super League C5', 'Zerb Team'),
('Super League C5', 'Val del Lys'),
('Super League C5', 'Calabbria UTD'),

-- Tornei, con alcune squadre gia presenti in campionati diversi
('Memorial Gianblanco', 'FC Bellavista'),
('Memorial Gianblanco', 'FC Pavone'),
('Memorial Gianblanco', 'Dieci10 C7'),
('Memorial Gianblanco', 'Victoria FC'),
('Torneo di Salassa', 'Sfogliatella C5'),
('Torneo di Salassa', 'Aston Birra C5'),
('Torneo di Salassa', 'AC Pro Secco'),
('Torneo di Salassa', 'Zerb Team'),
('Torneo di Aglie', 'HDemia F.B. C5'),
('Torneo di Aglie', 'La Vischese C5'),
('Torneo di Aglie', 'Val del Lys'),
('Torneo di Aglie', 'Calabbria UTD');

INSERT IGNORE INTO competition_teams (competition_id, team_id)
SELECT c.id, t.id
FROM tmp_seed_competition_teams sct
JOIN competitions c
    ON c.season_id = @season_id
   AND c.name = sct.competition_name
JOIN teams t
    ON t.name = sct.team_name;

DROP TEMPORARY TABLE IF EXISTS tmp_seed_competition_teams;
DROP TEMPORARY TABLE IF EXISTS tmp_seed_team_primary;
DROP TEMPORARY TABLE IF EXISTS tmp_seed_competitions;

COMMIT;

-- Verification queries:
-- SELECT football_type, type, COUNT(*) AS competitions
-- FROM competitions
-- WHERE season_id = (SELECT id FROM seasons WHERE name = '2025/2026')
-- GROUP BY football_type, type
-- ORDER BY football_type, type;
--
-- SELECT c.name AS competition, c.football_type, c.type, COUNT(ct.team_id) AS teams
-- FROM competitions c
-- LEFT JOIN competition_teams ct ON ct.competition_id = c.id
-- WHERE c.season_id = (SELECT id FROM seasons WHERE name = '2025/2026')
-- GROUP BY c.id, c.name, c.football_type, c.type
-- ORDER BY c.football_type, c.type, c.name;
