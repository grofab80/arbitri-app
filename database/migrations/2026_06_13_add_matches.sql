-- Introduce matches.
-- Every match belongs to one competition, and every competition belongs to one season.

START TRANSACTION;

ALTER TABLE competitions
    ADD COLUMN season_id int(11) DEFAULT NULL AFTER id,
    ADD KEY idx_competitions_season (season_id);

UPDATE competitions
SET season_id = (
    SELECT id
    FROM seasons
    WHERE is_current = 1
    LIMIT 1
)
WHERE season_id IS NULL;

ALTER TABLE competitions
    MODIFY season_id int(11) NOT NULL,
    ADD CONSTRAINT fk_competition_season
        FOREIGN KEY (season_id)
        REFERENCES seasons (id);

CREATE TABLE IF NOT EXISTS matches (
    id int(11) NOT NULL AUTO_INCREMENT,
    season_id int(11) NOT NULL,
    competition_id int(11) NOT NULL,
    home_team_id int(11) NOT NULL,
    away_team_id int(11) NOT NULL,
    field_id int(11) DEFAULT NULL,
    match_date date NOT NULL,
    match_time time DEFAULT NULL,
    status enum('scheduled','played','cancelled') NOT NULL DEFAULT 'scheduled',
    notes varchar(255) DEFAULT NULL,
    created_at timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (id),
    KEY idx_matches_season (season_id),
    KEY idx_matches_competition (competition_id),
    KEY idx_matches_home_team (home_team_id),
    KEY idx_matches_away_team (away_team_id),
    KEY idx_matches_date (match_date),
    CONSTRAINT fk_match_season
        FOREIGN KEY (season_id)
        REFERENCES seasons (id),
    CONSTRAINT fk_match_competition
        FOREIGN KEY (competition_id)
        REFERENCES competitions (id),
    CONSTRAINT fk_match_home_team
        FOREIGN KEY (home_team_id)
        REFERENCES teams (id),
    CONSTRAINT fk_match_away_team
        FOREIGN KEY (away_team_id)
        REFERENCES teams (id),
    CONSTRAINT chk_match_different_teams
        CHECK (home_team_id <> away_team_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
