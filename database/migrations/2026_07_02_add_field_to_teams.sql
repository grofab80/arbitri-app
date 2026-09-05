-- Add optional home field/stadium to teams.

START TRANSACTION;

ALTER TABLE teams
    ADD COLUMN field_id int(11) DEFAULT NULL AFTER name,
    ADD KEY idx_teams_field (field_id),
    ADD CONSTRAINT fk_team_field
        FOREIGN KEY (field_id)
        REFERENCES fields (id)
        ON DELETE SET NULL;

COMMIT;
