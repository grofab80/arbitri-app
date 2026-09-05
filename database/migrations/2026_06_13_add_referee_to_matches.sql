-- Add optional referee assignment to matches.

START TRANSACTION;

ALTER TABLE matches
    ADD COLUMN referee_id int(11) DEFAULT NULL AFTER away_team_id,
    ADD KEY idx_matches_referee (referee_id),
    ADD CONSTRAINT fk_match_referee
        FOREIGN KEY (referee_id)
        REFERENCES referees (id)
        ON DELETE SET NULL;

COMMIT;
