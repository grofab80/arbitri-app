-- Seed comparison data for season 2024/2025.
-- Creates a closed previous season with competitions, team relations and
-- financial movements comparable with 2025/2026.
--
-- Run after:
-- - 2026_07_13_rebuild_financial_categories.sql
-- - 2026_07_13_seed_2025_2026_competitions_teams.sql
--
-- The script is idempotent by season/name for competitions and by
-- season/date/amount/description for movements.

START TRANSACTION;

INSERT INTO seasons (name, starts_on, ends_on, status, is_current)
SELECT '2024/2025', '2024-07-01', '2025-06-30', 'chiuso', 0
WHERE NOT EXISTS (
    SELECT 1
    FROM seasons
    WHERE name = '2024/2025'
);

UPDATE seasons
SET starts_on = '2024-07-01',
    ends_on = '2025-06-30',
    status = 'chiuso',
    is_current = 0
WHERE name = '2024/2025';

SET @season_id := (
    SELECT id
    FROM seasons
    WHERE name = '2024/2025'
    LIMIT 1
);

CREATE TEMPORARY TABLE tmp_2024_competitions (
    name varchar(100) NOT NULL,
    type enum('campionato','torneo') NOT NULL,
    football_type enum('11','7','5') NOT NULL,
    PRIMARY KEY (name)
) ENGINE=Memory;

INSERT INTO tmp_2024_competitions (name, type, football_type)
VALUES
('Super League Oro C11', 'campionato', '11'),
('Super League Argento C11', 'campionato', '11'),
('Master C7', 'campionato', '7'),
('Over 35 C7', 'campionato', '7'),
('Master C5', 'campionato', '5'),
('Super League C5', 'campionato', '5'),
('Memorial Gianblanco', 'torneo', '7'),
('Torneo di Salassa', 'torneo', '5');

INSERT INTO competitions (season_id, name, type, football_type, season)
SELECT @season_id, tc.name, tc.type, tc.football_type, '2024/2025'
FROM tmp_2024_competitions tc
WHERE NOT EXISTS (
    SELECT 1
    FROM competitions c
    WHERE c.season_id = @season_id
      AND c.name = tc.name
);

CREATE TEMPORARY TABLE tmp_2024_competition_teams (
    competition_name varchar(100) NOT NULL,
    team_name varchar(100) NOT NULL,
    PRIMARY KEY (competition_name, team_name)
) ENGINE=Memory;

INSERT INTO tmp_2024_competition_teams (competition_name, team_name)
VALUES
('Super League Oro C11', 'FC Bellavista'),
('Super League Oro C11', 'FC Pavone'),
('Super League Oro C11', 'FC Biella Calcio'),
('Super League Oro C11', 'HDemia F.B.'),
('Super League Argento C11', 'Real Amis 2020'),
('Super League Argento C11', 'S.S. Real Ivrea 2009'),
('Super League Argento C11', 'Vistrorio'),
('Super League Argento C11', 'G.S.D Mezzese 1970'),
('Master C7', 'Dieci10 C7'),
('Master C7', 'Bar L''Incontro'),
('Master C7', 'RistoPub La Piola'),
('Master C7', 'Victoria FC'),
('Over 35 C7', 'Dieci10 C7 O35'),
('Over 35 C7', 'GR Ristrutturazioni'),
('Over 35 C7', 'Lamma Boys'),
('Over 35 C7', 'Atletico Mica Tanto'),
('Master C5', 'Sfogliatella C5'),
('Master C5', 'Aston Birra C5'),
('Master C5', 'HDemia F.B. C5'),
('Master C5', 'La Vischese C5'),
('Super League C5', 'AC Pro Secco'),
('Super League C5', 'Zerb Team'),
('Super League C5', 'Val del Lys'),
('Super League C5', 'Calabbria UTD'),
('Memorial Gianblanco', 'FC Bellavista'),
('Memorial Gianblanco', 'FC Pavone'),
('Memorial Gianblanco', 'Dieci10 C7'),
('Memorial Gianblanco', 'Victoria FC'),
('Torneo di Salassa', 'Sfogliatella C5'),
('Torneo di Salassa', 'Aston Birra C5'),
('Torneo di Salassa', 'AC Pro Secco'),
('Torneo di Salassa', 'Zerb Team');

INSERT IGNORE INTO competition_teams (competition_id, team_id)
SELECT c.id, t.id
FROM tmp_2024_competition_teams sct
JOIN competitions c
    ON c.season_id = @season_id
   AND c.name = sct.competition_name
JOIN teams t
    ON t.name = sct.team_name;

CREATE TEMPORARY TABLE tmp_2024_movements (
    movement_date date NOT NULL,
    type_name varchar(100) NOT NULL,
    area_name varchar(100) NOT NULL,
    voice_name varchar(100) NOT NULL,
    detail_name varchar(100) NOT NULL,
    amount decimal(10,2) NOT NULL,
    description varchar(255) NOT NULL,
    competition_name varchar(100) DEFAULT NULL,
    team_name varchar(100) DEFAULT NULL,
    referee_name varchar(100) DEFAULT NULL
) ENGINE=Memory;

INSERT INTO tmp_2024_movements
    (movement_date, type_name, area_name, voice_name, detail_name, amount, description, competition_name, team_name, referee_name)
VALUES
-- Entrate
('2024-09-12', 'Entrate', 'Quote e iscrizioni', 'Iscrizioni competizioni', 'Iscrizione campionato', 500.00, 'Quota iscrizione 2024 Super League Oro C11 - FC Bellavista', 'Super League Oro C11', 'FC Bellavista', NULL),
('2024-09-13', 'Entrate', 'Quote e iscrizioni', 'Iscrizioni competizioni', 'Iscrizione campionato', 500.00, 'Quota iscrizione 2024 Super League Oro C11 - FC Pavone', 'Super League Oro C11', 'FC Pavone', NULL),
('2024-09-14', 'Entrate', 'Quote e iscrizioni', 'Iscrizioni competizioni', 'Iscrizione campionato', 450.00, 'Quota iscrizione 2024 Super League Argento C11 - Real Amis 2020', 'Super League Argento C11', 'Real Amis 2020', NULL),
('2024-09-15', 'Entrate', 'Quote e iscrizioni', 'Iscrizioni competizioni', 'Iscrizione campionato', 390.00, 'Quota iscrizione 2024 Master C7 - Dieci10 C7', 'Master C7', 'Dieci10 C7', NULL),
('2024-09-16', 'Entrate', 'Quote e iscrizioni', 'Iscrizioni competizioni', 'Iscrizione campionato', 320.00, 'Quota iscrizione 2024 Master C5 - Sfogliatella C5', 'Master C5', 'Sfogliatella C5', NULL),
('2024-09-17', 'Entrate', 'Tesseramenti', 'Tessere atleti', 'Tessera annuale', 140.00, 'Tessere atleti 2024 FC Bellavista', NULL, 'FC Bellavista', NULL),
('2024-09-18', 'Entrate', 'Tesseramenti', 'Tessere atleti', 'Tessera annuale', 140.00, 'Tessere atleti 2024 Dieci10 C7', NULL, 'Dieci10 C7', NULL),
('2024-09-19', 'Entrate', 'Tesseramenti', 'Tessere atleti', 'Tessera annuale', 140.00, 'Tessere atleti 2024 Sfogliatella C5', NULL, 'Sfogliatella C5', NULL),
('2024-10-12', 'Entrate', 'Sponsorizzazioni e pubblicita', 'Sponsor', 'Sponsor associazione', 700.00, 'Sponsor associazione 2024 - Energy Gold', NULL, NULL, NULL),
('2024-11-18', 'Entrate', 'Sponsorizzazioni e pubblicita', 'Pubblicita', 'Materiale pubblicitario', 1200.00, 'Incasso pubblicita materiale tecnico 2024', NULL, NULL, NULL),
('2025-01-25', 'Entrate', 'Sponsorizzazioni e pubblicita', 'Pubblicita', 'Materiale pubblicitario', 420.00, 'Incasso stampa sponsor 2024', NULL, NULL, NULL),
('2025-06-05', 'Entrate', 'Eventi e tornei', 'Incassi evento', 'Festa / evento', 900.00, 'Incasso festa associazione 2024', NULL, NULL, NULL),
('2025-06-20', 'Entrate', 'Eventi e tornei', 'Incassi evento', 'Torneo', 350.00, 'Iscrizione Torneo di Salassa 2024 - AC Pro Secco', 'Torneo di Salassa', 'AC Pro Secco', NULL),
('2025-06-21', 'Entrate', 'Eventi e tornei', 'Incassi evento', 'Torneo', 300.00, 'Iscrizione Torneo di Salassa 2024 - Zerb Team', 'Torneo di Salassa', 'Zerb Team', NULL),

-- Uscite
('2024-09-15', 'Uscite', 'Arbitri e ufficiali gara', 'Compensi arbitri', 'Arbitro centrale', 50.00, 'Gettone arbitro 2024 Super League Oro C11', 'Super League Oro C11', NULL, 'Mario Rossi'),
('2024-09-15', 'Uscite', 'Arbitri e ufficiali gara', 'Compensi arbitri', 'Assistente', 30.00, 'Assistente 2024 Super League Oro C11', 'Super League Oro C11', NULL, 'Luca Bianchi'),
('2024-09-22', 'Uscite', 'Arbitri e ufficiali gara', 'Compensi arbitri', 'Arbitro centrale', 40.00, 'Gettone arbitro 2024 Master C7', 'Master C7', NULL, 'Andrea Verdi'),
('2024-10-20', 'Uscite', 'Arbitri e ufficiali gara', 'Rimborsi arbitri', 'Rimborso spese', 24.00, 'Rimborso spese arbitro 2024', NULL, NULL, 'Mario Rossi'),
('2024-11-10', 'Uscite', 'Arbitri e ufficiali gara', 'Compensi arbitri', 'Terna', 105.00, 'Terna arbitrale 2024 Super League Oro C11', 'Super League Oro C11', NULL, NULL),
('2024-11-25', 'Uscite', 'Costi competizioni', 'Materiale gara', 'Attrezzatura sportiva', 2100.00, 'Ordine materiale tecnico Zeus 2024', 'Super League Oro C11', NULL, NULL),
('2024-12-02', 'Uscite', 'Costi competizioni', 'Materiale gara', 'Referti', 200.00, 'Referti gara stagione 2024', 'Super League Oro C11', NULL, NULL),
('2025-05-22', 'Uscite', 'Costi competizioni', 'Premi e coppe', 'Coppe', 3000.00, 'Coppe premiazione stagione 2024', 'Super League Oro C11', NULL, NULL),
('2025-06-04', 'Uscite', 'Costi competizioni', 'Premi e coppe', 'Premi', 800.00, 'Premio evento finale 2024', 'Memorial Gianblanco', NULL, NULL),
('2025-05-25', 'Uscite', 'Strutture e logistica', 'Stadi e campi', 'Affitto campo', 250.00, 'Affitto campo finale calcio a 11 2024', 'Super League Oro C11', NULL, NULL),
('2025-05-26', 'Uscite', 'Strutture e logistica', 'Stadi e campi', 'Affitto campo', 130.00, 'Affitto campo calcio a 7 2024', 'Master C7', NULL, NULL),
('2025-06-03', 'Uscite', 'Strutture e logistica', 'Eventi', 'Cena arbitri', 390.00, 'Cena arbitri fine stagione 2024', NULL, NULL, NULL),
('2025-06-04', 'Uscite', 'Strutture e logistica', 'Trasporti', 'Benzina', 30.00, 'Benzina commissioni evento 2024', NULL, NULL, NULL),
('2024-11-29', 'Uscite', 'Costi associazione', 'Sito e software', 'Dominio', 85.00, 'Rinnovo dominio sito 2024', NULL, NULL, NULL),
('2024-12-02', 'Uscite', 'Costi associazione', 'Sito e software', 'Hosting / gestione sito', 1800.00, 'Gestione sito stagione 2024', NULL, NULL, NULL),
('2025-05-20', 'Uscite', 'Costi associazione', 'Affiliazioni', 'ACSI', 140.00, 'Affiliazione ACSI 2024', NULL, NULL, NULL),
('2025-05-22', 'Uscite', 'Tesseramenti passivi', 'Tessere', 'Tessere ACSI / ente', 3200.00, 'Tessere ente stagione 2024/2025', NULL, 'FC Bellavista', NULL),
('2025-06-02', 'Uscite', 'Staff e collaboratori', 'Rimborsi staff', 'Staff', 750.00, 'Rimborso staff segreteria finale stagione 2024', NULL, NULL, NULL),
('2025-06-03', 'Uscite', 'Costi associazione', 'Sede e segreteria', 'Segreteria', 180.00, 'Supporto segreteria evento 2024', NULL, NULL, NULL);

SET @expected_movements := (SELECT COUNT(*) FROM tmp_2024_movements);

SET @resolved_movements := (
    SELECT COUNT(*)
    FROM tmp_2024_movements tm
    JOIN categories c4 ON c4.level = 4 AND c4.name = tm.detail_name
    JOIN categories c3 ON c3.id = c4.parent_id AND c3.name = tm.voice_name
    JOIN categories c2 ON c2.id = c3.parent_id AND c2.name = tm.area_name
    JOIN categories c1 ON c1.id = c2.parent_id AND c1.name = tm.type_name
    LEFT JOIN competitions c ON c.season_id = @season_id AND c.name = tm.competition_name
    LEFT JOIN teams t ON t.name = tm.team_name
    LEFT JOIN referees r ON r.name = tm.referee_name
    WHERE (tm.competition_name IS NULL OR c.id IS NOT NULL)
      AND (tm.team_name IS NULL OR t.id IS NOT NULL)
      AND (tm.referee_name IS NULL OR r.id IS NOT NULL)
);

-- Force a readable FK failure if categories/competitions/teams/referees are missing.
INSERT INTO movements (season_id, category_id, amount, movement_date, description)
SELECT 0, 0, 0.00, '2024-07-01', 'ERRORE: seed movimenti 2024/2025 non risolto completamente'
WHERE @resolved_movements <> @expected_movements;

INSERT INTO movements
    (season_id, category_id, amount, movement_date, description, competition_id, team_id, referee_id)
SELECT
    @season_id,
    c4.id,
    tm.amount,
    tm.movement_date,
    tm.description,
    c.id,
    t.id,
    r.id
FROM tmp_2024_movements tm
JOIN categories c4 ON c4.level = 4 AND c4.name = tm.detail_name
JOIN categories c3 ON c3.id = c4.parent_id AND c3.name = tm.voice_name
JOIN categories c2 ON c2.id = c3.parent_id AND c2.name = tm.area_name
JOIN categories c1 ON c1.id = c2.parent_id AND c1.name = tm.type_name
LEFT JOIN competitions c ON c.season_id = @season_id AND c.name = tm.competition_name
LEFT JOIN teams t ON t.name = tm.team_name
LEFT JOIN referees r ON r.name = tm.referee_name
WHERE NOT EXISTS (
    SELECT 1
    FROM movements existing
    WHERE existing.season_id = @season_id
      AND existing.movement_date = tm.movement_date
      AND existing.amount = tm.amount
      AND existing.description = tm.description
);

DROP TEMPORARY TABLE IF EXISTS tmp_2024_movements;
DROP TEMPORARY TABLE IF EXISTS tmp_2024_competition_teams;
DROP TEMPORARY TABLE IF EXISTS tmp_2024_competitions;

COMMIT;

-- Verification queries:
-- SELECT id, name, starts_on, ends_on, status, is_current
-- FROM seasons
-- ORDER BY starts_on;
--
-- SELECT s.name AS season, c1.name AS type_name, COUNT(*) AS movements, SUM(m.amount) AS total
-- FROM movements m
-- JOIN seasons s ON s.id = m.season_id
-- JOIN categories c4 ON c4.id = m.category_id
-- JOIN categories c3 ON c3.id = c4.parent_id
-- JOIN categories c2 ON c2.id = c3.parent_id
-- JOIN categories c1 ON c1.id = c2.parent_id
-- WHERE s.name IN ('2024/2025', '2025/2026')
-- GROUP BY s.name, c1.name
-- ORDER BY s.name, c1.name;
