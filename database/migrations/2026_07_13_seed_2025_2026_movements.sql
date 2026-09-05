-- Seed sample movements for current season 2025/2026.
-- Data is inspired by the legacy `asdaca` accounting distribution, but kept
-- compact for frontend/API testing.
--
-- Run after:
-- - 2026_07_13_rebuild_financial_categories.sql
-- - 2026_07_13_seed_2025_2026_competitions_teams.sql
--
-- The script is idempotent by season/date/amount/description.

START TRANSACTION;

SET @season_id := (
    SELECT id
    FROM seasons
    WHERE name = '2025/2026'
      AND status = 'in_corso'
    LIMIT 1
);

-- Force a readable failure if the reset/current season script was not run.
INSERT INTO movements (season_id, category_id, amount, movement_date, description)
SELECT 0, 0, 0.00, '2025-07-01', 'ERRORE: stagione 2025/2026 in corso non trovata'
WHERE @season_id IS NULL;

-- Correct older demo rows that were outside the 2025/2026 season interval.
UPDATE movements
SET movement_date = '2026-06-28'
WHERE season_id = @season_id
  AND movement_date = '2026-07-08'
  AND amount = 300.00
  AND description = 'Iscrizione Torneo di Aglie - Val del Lys';

-- Ensure a few test referees exist for referee-linked movements.
INSERT INTO referees (name, rating, can_referee_11, can_referee_7, can_referee_5)
SELECT 'Mario Rossi', 4, 1, 1, 1
WHERE NOT EXISTS (SELECT 1 FROM referees WHERE name = 'Mario Rossi');

INSERT INTO referees (name, rating, can_referee_11, can_referee_7, can_referee_5)
SELECT 'Luca Bianchi', 3, 1, 1, 0
WHERE NOT EXISTS (SELECT 1 FROM referees WHERE name = 'Luca Bianchi');

INSERT INTO referees (name, rating, can_referee_11, can_referee_7, can_referee_5)
SELECT 'Andrea Verdi', 5, 0, 1, 1
WHERE NOT EXISTS (SELECT 1 FROM referees WHERE name = 'Andrea Verdi');

CREATE TEMPORARY TABLE tmp_seed_movements (
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

INSERT INTO tmp_seed_movements
    (movement_date, type_name, area_name, voice_name, detail_name, amount, description, competition_name, team_name, referee_name)
VALUES
-- Entrate: iscrizioni e tessere campionati
('2025-09-15', 'Entrate', 'Quote e iscrizioni', 'Iscrizioni competizioni', 'Iscrizione campionato', 550.00, 'Quota iscrizione Super League Oro C11 - FC Bellavista', 'Super League Oro C11', 'FC Bellavista', NULL),
('2025-09-16', 'Entrate', 'Quote e iscrizioni', 'Iscrizioni competizioni', 'Iscrizione campionato', 550.00, 'Quota iscrizione Super League Oro C11 - FC Pavone', 'Super League Oro C11', 'FC Pavone', NULL),
('2025-09-17', 'Entrate', 'Quote e iscrizioni', 'Iscrizioni competizioni', 'Iscrizione campionato', 500.00, 'Quota iscrizione Super League Argento C11 - Real Amis 2020', 'Super League Argento C11', 'Real Amis 2020', NULL),
('2025-09-18', 'Entrate', 'Quote e iscrizioni', 'Iscrizioni competizioni', 'Iscrizione campionato', 420.00, 'Quota iscrizione Master C7 - Dieci10 C7', 'Master C7', 'Dieci10 C7', NULL),
('2025-09-19', 'Entrate', 'Quote e iscrizioni', 'Iscrizioni competizioni', 'Iscrizione campionato', 420.00, 'Quota iscrizione Master C7 - Victoria FC', 'Master C7', 'Victoria FC', NULL),
('2025-09-20', 'Entrate', 'Quote e iscrizioni', 'Iscrizioni competizioni', 'Iscrizione campionato', 350.00, 'Quota iscrizione Master C5 - Sfogliatella C5', 'Master C5', 'Sfogliatella C5', NULL),
('2025-09-21', 'Entrate', 'Tesseramenti', 'Tessere atleti', 'Tessera annuale', 150.00, 'Tessere atleti FC Bellavista', NULL, 'FC Bellavista', NULL),
('2025-09-22', 'Entrate', 'Tesseramenti', 'Tessere atleti', 'Tessera annuale', 150.00, 'Tessere atleti FC Pavone', NULL, 'FC Pavone', NULL),
('2025-09-23', 'Entrate', 'Tesseramenti', 'Tessere atleti', 'Tessera annuale', 150.00, 'Tessere atleti Dieci10 C7', NULL, 'Dieci10 C7', NULL),
('2025-09-24', 'Entrate', 'Tesseramenti', 'Tessere atleti', 'Tessera annuale', 150.00, 'Tessere atleti Sfogliatella C5', NULL, 'Sfogliatella C5', NULL),

-- Entrate: sponsor, pubblicita, tornei
('2025-10-10', 'Entrate', 'Sponsorizzazioni e pubblicita', 'Sponsor', 'Sponsor associazione', 800.00, 'Sponsor associazione - Energy Gold', NULL, NULL, NULL),
('2025-11-20', 'Entrate', 'Sponsorizzazioni e pubblicita', 'Pubblicita', 'Materiale pubblicitario', 1686.00, 'Incasso pubblicita materiale tecnico', NULL, NULL, NULL),
('2026-01-28', 'Entrate', 'Sponsorizzazioni e pubblicita', 'Pubblicita', 'Materiale pubblicitario', 584.00, 'Incasso stampa sponsor', NULL, NULL, NULL),
('2026-06-24', 'Entrate', 'Eventi e tornei', 'Incassi evento', 'Torneo', 400.00, 'Iscrizione Torneo di Salassa - AC Pro Secco', 'Torneo di Salassa', 'AC Pro Secco', NULL),
('2026-06-25', 'Entrate', 'Eventi e tornei', 'Incassi evento', 'Torneo', 400.00, 'Iscrizione Torneo di Salassa - Zerb Team', 'Torneo di Salassa', 'Zerb Team', NULL),
('2026-06-28', 'Entrate', 'Eventi e tornei', 'Incassi evento', 'Torneo', 300.00, 'Iscrizione Torneo di Aglie - Val del Lys', 'Torneo di Aglie', 'Val del Lys', NULL),
('2026-06-02', 'Entrate', 'Eventi e tornei', 'Incassi evento', 'Festa / evento', 1233.00, 'Incasso festa associazione', NULL, NULL, NULL),

-- Uscite: gare e ufficiali
('2025-09-15', 'Uscite', 'Arbitri e ufficiali gara', 'Compensi arbitri', 'Arbitro centrale', 55.00, 'Gettone arbitro Super League Oro C11', 'Super League Oro C11', NULL, 'Mario Rossi'),
('2025-09-15', 'Uscite', 'Arbitri e ufficiali gara', 'Compensi arbitri', 'Assistente', 35.00, 'Assistente Super League Oro C11', 'Super League Oro C11', NULL, 'Luca Bianchi'),
('2025-09-22', 'Uscite', 'Arbitri e ufficiali gara', 'Compensi arbitri', 'Arbitro centrale', 45.00, 'Gettone arbitro Master C7', 'Master C7', NULL, 'Andrea Verdi'),
('2025-10-15', 'Uscite', 'Arbitri e ufficiali gara', 'Osservatori', 'Osservatore', 60.00, 'Osservatore gara Super League Argento C11', 'Super League Argento C11', NULL, NULL),
('2025-10-22', 'Uscite', 'Arbitri e ufficiali gara', 'Rimborsi arbitri', 'Rimborso spese', 28.00, 'Rimborso spese arbitro trasferta', NULL, NULL, 'Mario Rossi'),
('2025-11-05', 'Uscite', 'Arbitri e ufficiali gara', 'Compensi arbitri', 'Terna', 120.00, 'Terna arbitrale Super League Oro C11', 'Super League Oro C11', NULL, NULL),

-- Uscite: competizioni, materiali, strutture
('2025-11-29', 'Uscite', 'Costi competizioni', 'Materiale gara', 'Attrezzatura sportiva', 2384.25, 'Ordine materiale tecnico Zeus', 'Super League Oro C11', NULL, NULL),
('2025-12-02', 'Uscite', 'Costi competizioni', 'Materiale gara', 'Referti', 240.00, 'Referti triplice copia', 'Super League Oro C11', NULL, NULL),
('2026-05-25', 'Uscite', 'Costi competizioni', 'Premi e coppe', 'Coppe', 3660.00, 'Coppe premiazione stagione', 'Super League Oro C11', NULL, NULL),
('2026-06-02', 'Uscite', 'Costi competizioni', 'Premi e coppe', 'Premi', 1000.00, 'Premio evento finale', 'Memorial Gianblanco', NULL, NULL),
('2026-05-25', 'Uscite', 'Strutture e logistica', 'Stadi e campi', 'Affitto campo', 280.00, 'Affitto campo finale calcio a 11', 'Super League Oro C11', NULL, NULL),
('2026-05-26', 'Uscite', 'Strutture e logistica', 'Stadi e campi', 'Affitto campo', 150.00, 'Affitto campo calcio a 7', 'Master C7', NULL, NULL),
('2026-06-02', 'Uscite', 'Strutture e logistica', 'Eventi', 'Cena arbitri', 450.00, 'Cena arbitri fine stagione', NULL, NULL, NULL),
('2026-06-03', 'Uscite', 'Strutture e logistica', 'Trasporti', 'Benzina', 35.00, 'Benzina per commissioni evento', NULL, NULL, NULL),

-- Uscite: associazione, sito, staff
('2025-11-29', 'Uscite', 'Costi associazione', 'Sito e software', 'Dominio', 91.49, 'Rinnovo dominio sito', NULL, NULL, NULL),
('2025-11-29', 'Uscite', 'Costi associazione', 'Sito e software', 'Plugin / servizi digitali', 110.00, 'Canva e plugin grafici', NULL, NULL, NULL),
('2025-12-02', 'Uscite', 'Costi associazione', 'Sito e software', 'Hosting / gestione sito', 2000.00, 'Gestione sito stagione', NULL, NULL, NULL),
('2026-05-25', 'Uscite', 'Costi associazione', 'Affiliazioni', 'ACSI', 150.00, 'Affiliazione ACSI', NULL, NULL, NULL),
('2026-05-25', 'Uscite', 'Tesseramenti passivi', 'Tessere', 'Tessere ACSI / ente', 3600.00, 'Tessere ente stagione 2025/2026', NULL, 'FC Bellavista', NULL),
('2026-06-02', 'Uscite', 'Staff e collaboratori', 'Rimborsi staff', 'Staff', 850.00, 'Rimborso staff segreteria finale stagione', NULL, NULL, NULL),
('2026-06-02', 'Uscite', 'Costi associazione', 'Sede e segreteria', 'Segreteria', 200.00, 'Supporto segreteria evento', NULL, NULL, NULL);

SET @expected_movements := (SELECT COUNT(*) FROM tmp_seed_movements);

SET @resolved_movements := (
    SELECT COUNT(*)
    FROM tmp_seed_movements tm
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
SELECT 0, 0, 0.00, '2025-07-01', 'ERRORE: seed movimenti non risolto completamente'
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
FROM tmp_seed_movements tm
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

DROP TEMPORARY TABLE IF EXISTS tmp_seed_movements;

COMMIT;

-- Verification queries:
-- SELECT COUNT(*) AS movements
-- FROM movements
-- WHERE season_id = (SELECT id FROM seasons WHERE name = '2025/2026');
--
-- SELECT c1.name AS type_name, COUNT(*) AS movements, SUM(m.amount) AS total
-- FROM movements m
-- JOIN categories c4 ON c4.id = m.category_id
-- JOIN categories c3 ON c3.id = c4.parent_id
-- JOIN categories c2 ON c2.id = c3.parent_id
-- JOIN categories c1 ON c1.id = c2.parent_id
-- WHERE m.season_id = (SELECT id FROM seasons WHERE name = '2025/2026')
-- GROUP BY c1.name;
