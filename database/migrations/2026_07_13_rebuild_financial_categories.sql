-- Reset local accounting setup and rebuild financial categories.
--
-- IMPORTANT:
-- This script is intended for local/development use before importing historical
-- movements from the legacy `asdaca` database.
-- It deletes seasonal/sport/accounting data, recreates only season 2025/2026 as
-- current, and rebuilds categories with the new taxonomy.
-- It preserves users, profiles, permissions, referees and fields/stadiums.

START TRANSACTION;

-- Reset data that can reference seasons, competitions, teams, movements or categories.
DELETE FROM balance_closures;
DELETE FROM competition_standings;
DELETE FROM matches;
DELETE FROM movements;
DELETE FROM competition_teams;
DELETE FROM teams;
DELETE FROM competitions;
DELETE FROM seasons;
DELETE FROM categories WHERE parent_id IS NULL;

INSERT INTO seasons (name, starts_on, ends_on, status, is_current)
VALUES ('2025/2026', '2025-07-01', '2026-06-30', 'in_corso', 1);

-- Level 1
INSERT INTO categories (name, level, parent_id, allow_competition, allow_team, allow_referee)
VALUES
('Entrate', 1, NULL, 0, 0, 0),
('Uscite', 1, NULL, 0, 0, 0);

SET @entrate := (SELECT id FROM categories WHERE level = 1 AND name = 'Entrate' LIMIT 1);
SET @uscite := (SELECT id FROM categories WHERE level = 1 AND name = 'Uscite' LIMIT 1);

-- Entrate - Level 2
INSERT INTO categories (name, level, parent_id, allow_competition, allow_team, allow_referee)
VALUES
('Quote e iscrizioni', 2, @entrate, 0, 0, 0),
('Tesseramenti', 2, @entrate, 0, 0, 0),
('Sponsorizzazioni e pubblicita', 2, @entrate, 0, 0, 0),
('Eventi e tornei', 2, @entrate, 0, 0, 0);

SET @e_quote := (SELECT id FROM categories WHERE level = 2 AND parent_id = @entrate AND name = 'Quote e iscrizioni' LIMIT 1);
SET @e_tesseramenti := (SELECT id FROM categories WHERE level = 2 AND parent_id = @entrate AND name = 'Tesseramenti' LIMIT 1);
SET @e_sponsor := (SELECT id FROM categories WHERE level = 2 AND parent_id = @entrate AND name = 'Sponsorizzazioni e pubblicita' LIMIT 1);
SET @e_eventi := (SELECT id FROM categories WHERE level = 2 AND parent_id = @entrate AND name = 'Eventi e tornei' LIMIT 1);

-- Entrate - Level 3
INSERT INTO categories (name, level, parent_id, allow_competition, allow_team, allow_referee)
VALUES
('Iscrizioni competizioni', 3, @e_quote, 0, 0, 0),
('Tessere atleti', 3, @e_tesseramenti, 0, 0, 0),
('Sponsor', 3, @e_sponsor, 0, 0, 0),
('Pubblicita', 3, @e_sponsor, 0, 0, 0),
('Incassi evento', 3, @e_eventi, 0, 0, 0);

SET @e_iscrizioni_comp := (SELECT id FROM categories WHERE level = 3 AND parent_id = @e_quote AND name = 'Iscrizioni competizioni' LIMIT 1);
SET @e_tessere_atleti := (SELECT id FROM categories WHERE level = 3 AND parent_id = @e_tesseramenti AND name = 'Tessere atleti' LIMIT 1);
SET @e_sponsor_l3 := (SELECT id FROM categories WHERE level = 3 AND parent_id = @e_sponsor AND name = 'Sponsor' LIMIT 1);
SET @e_pubblicita_l3 := (SELECT id FROM categories WHERE level = 3 AND parent_id = @e_sponsor AND name = 'Pubblicita' LIMIT 1);
SET @e_incassi_evento := (SELECT id FROM categories WHERE level = 3 AND parent_id = @e_eventi AND name = 'Incassi evento' LIMIT 1);

-- Entrate - Level 4
INSERT INTO categories (name, level, parent_id, allow_competition, allow_team, allow_referee)
VALUES
('Iscrizione campionato', 4, @e_iscrizioni_comp, 1, 1, 0),
('Iscrizione torneo', 4, @e_iscrizioni_comp, 1, 1, 0),
('Tessera annuale', 4, @e_tessere_atleti, 0, 1, 0),
('Sponsor associazione', 4, @e_sponsor_l3, 0, 0, 0),
('Materiale pubblicitario', 4, @e_pubblicita_l3, 0, 0, 0),
('Torneo', 4, @e_incassi_evento, 1, 1, 0),
('Festa / evento', 4, @e_incassi_evento, 0, 0, 0),
('Altro evento', 4, @e_incassi_evento, 0, 0, 0);

-- Uscite - Level 2
INSERT INTO categories (name, level, parent_id, allow_competition, allow_team, allow_referee)
VALUES
('Arbitri e ufficiali gara', 2, @uscite, 0, 0, 0),
('Costi competizioni', 2, @uscite, 0, 0, 0),
('Strutture e logistica', 2, @uscite, 0, 0, 0),
('Costi associazione', 2, @uscite, 0, 0, 0),
('Tesseramenti passivi', 2, @uscite, 0, 0, 0),
('Staff e collaboratori', 2, @uscite, 0, 0, 0);

SET @u_arbitri := (SELECT id FROM categories WHERE level = 2 AND parent_id = @uscite AND name = 'Arbitri e ufficiali gara' LIMIT 1);
SET @u_competizioni := (SELECT id FROM categories WHERE level = 2 AND parent_id = @uscite AND name = 'Costi competizioni' LIMIT 1);
SET @u_logistica := (SELECT id FROM categories WHERE level = 2 AND parent_id = @uscite AND name = 'Strutture e logistica' LIMIT 1);
SET @u_associazione := (SELECT id FROM categories WHERE level = 2 AND parent_id = @uscite AND name = 'Costi associazione' LIMIT 1);
SET @u_tesseramenti := (SELECT id FROM categories WHERE level = 2 AND parent_id = @uscite AND name = 'Tesseramenti passivi' LIMIT 1);
SET @u_staff := (SELECT id FROM categories WHERE level = 2 AND parent_id = @uscite AND name = 'Staff e collaboratori' LIMIT 1);

-- Uscite - Level 3
INSERT INTO categories (name, level, parent_id, allow_competition, allow_team, allow_referee)
VALUES
('Compensi arbitri', 3, @u_arbitri, 0, 0, 0),
('Osservatori', 3, @u_arbitri, 0, 0, 0),
('Rimborsi arbitri', 3, @u_arbitri, 0, 0, 0),
('Premi e coppe', 3, @u_competizioni, 0, 0, 0),
('Materiale gara', 3, @u_competizioni, 0, 0, 0),
('Stadi e campi', 3, @u_logistica, 0, 0, 0),
('Eventi', 3, @u_logistica, 0, 0, 0),
('Trasporti', 3, @u_logistica, 0, 0, 0),
('Affiliazioni', 3, @u_associazione, 0, 0, 0),
('Sede e segreteria', 3, @u_associazione, 0, 0, 0),
('Sito e software', 3, @u_associazione, 0, 0, 0),
('Tessere', 3, @u_tesseramenti, 0, 0, 0),
('Rimborsi staff', 3, @u_staff, 0, 0, 0);

SET @u_compensi_arbitri := (SELECT id FROM categories WHERE level = 3 AND parent_id = @u_arbitri AND name = 'Compensi arbitri' LIMIT 1);
SET @u_osservatori := (SELECT id FROM categories WHERE level = 3 AND parent_id = @u_arbitri AND name = 'Osservatori' LIMIT 1);
SET @u_rimborsi_arbitri := (SELECT id FROM categories WHERE level = 3 AND parent_id = @u_arbitri AND name = 'Rimborsi arbitri' LIMIT 1);
SET @u_premi_coppe := (SELECT id FROM categories WHERE level = 3 AND parent_id = @u_competizioni AND name = 'Premi e coppe' LIMIT 1);
SET @u_materiale_gara := (SELECT id FROM categories WHERE level = 3 AND parent_id = @u_competizioni AND name = 'Materiale gara' LIMIT 1);
SET @u_stadi_campi := (SELECT id FROM categories WHERE level = 3 AND parent_id = @u_logistica AND name = 'Stadi e campi' LIMIT 1);
SET @u_eventi := (SELECT id FROM categories WHERE level = 3 AND parent_id = @u_logistica AND name = 'Eventi' LIMIT 1);
SET @u_trasporti := (SELECT id FROM categories WHERE level = 3 AND parent_id = @u_logistica AND name = 'Trasporti' LIMIT 1);
SET @u_affiliazioni := (SELECT id FROM categories WHERE level = 3 AND parent_id = @u_associazione AND name = 'Affiliazioni' LIMIT 1);
SET @u_sede := (SELECT id FROM categories WHERE level = 3 AND parent_id = @u_associazione AND name = 'Sede e segreteria' LIMIT 1);
SET @u_sito := (SELECT id FROM categories WHERE level = 3 AND parent_id = @u_associazione AND name = 'Sito e software' LIMIT 1);
SET @u_tessere := (SELECT id FROM categories WHERE level = 3 AND parent_id = @u_tesseramenti AND name = 'Tessere' LIMIT 1);
SET @u_rimborsi_staff := (SELECT id FROM categories WHERE level = 3 AND parent_id = @u_staff AND name = 'Rimborsi staff' LIMIT 1);

-- Uscite - Level 4
INSERT INTO categories (name, level, parent_id, allow_competition, allow_team, allow_referee)
VALUES
('Arbitro centrale', 4, @u_compensi_arbitri, 1, 0, 1),
('Assistente', 4, @u_compensi_arbitri, 1, 0, 1),
('Terna', 4, @u_compensi_arbitri, 1, 0, 0),
('Osservatore', 4, @u_osservatori, 1, 0, 0),
('Rimborso spese', 4, @u_rimborsi_arbitri, 0, 0, 1),
('Chilometraggio', 4, @u_rimborsi_arbitri, 0, 0, 1),
('Coppe', 4, @u_premi_coppe, 1, 0, 0),
('Premi', 4, @u_premi_coppe, 1, 0, 0),
('Coppa disciplina', 4, @u_premi_coppe, 1, 0, 0),
('Palloni', 4, @u_materiale_gara, 1, 0, 0),
('Referti', 4, @u_materiale_gara, 1, 0, 0),
('Attrezzatura sportiva', 4, @u_materiale_gara, 1, 0, 0),
('Affitto campo', 4, @u_stadi_campi, 1, 0, 0),
('Noleggio struttura', 4, @u_stadi_campi, 1, 0, 0),
('Cena arbitri', 4, @u_eventi, 0, 0, 0),
('Premiazione', 4, @u_eventi, 0, 0, 0),
('Bar / ristoro', 4, @u_eventi, 0, 0, 0),
('Benzina', 4, @u_trasporti, 0, 0, 0),
('Corrieri', 4, @u_trasporti, 0, 0, 0),
('Noleggio mezzo', 4, @u_trasporti, 0, 0, 0),
('ACSI', 4, @u_affiliazioni, 0, 0, 0),
('Affiliazione federativa', 4, @u_affiliazioni, 0, 0, 0),
('Cancelleria', 4, @u_sede, 0, 0, 0),
('Segreteria', 4, @u_sede, 0, 0, 0),
('Manutenzione', 4, @u_sede, 0, 0, 0),
('Donazioni', 4, @u_sede, 0, 0, 0),
('Altro sede', 4, @u_sede, 0, 0, 0),
('Dominio', 4, @u_sito, 0, 0, 0),
('Hosting / gestione sito', 4, @u_sito, 0, 0, 0),
('Plugin / servizi digitali', 4, @u_sito, 0, 0, 0),
('Canva / software', 4, @u_sito, 0, 0, 0),
('Tessere ACSI / ente', 4, @u_tessere, 0, 1, 0),
('Staff', 4, @u_rimborsi_staff, 0, 0, 0),
('Collaboratori', 4, @u_rimborsi_staff, 0, 0, 0);

COMMIT;

-- Verification query:
-- SELECT id, name, starts_on, ends_on, status, is_current FROM seasons;
--
-- SELECT c1.name AS tipo, c2.name AS area, c3.name AS voce, c4.name AS dettaglio,
--        c4.allow_competition, c4.allow_team, c4.allow_referee
-- FROM categories c4
-- JOIN categories c3 ON c3.id = c4.parent_id
-- JOIN categories c2 ON c2.id = c3.parent_id
-- JOIN categories c1 ON c1.id = c2.parent_id
-- WHERE c4.level = 4
-- ORDER BY c1.name, c2.name, c3.name, c4.name;
