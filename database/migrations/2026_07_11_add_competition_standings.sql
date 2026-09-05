-- Add standings for league competitions.
-- Standings are attached to competitions and teams; application logic will
-- expose them only for competitions with type = 'campionato'.

START TRANSACTION;

CREATE TABLE IF NOT EXISTS competition_standings (
    id int(11) NOT NULL AUTO_INCREMENT,
    competition_id int(11) NOT NULL,
    team_id int(11) NOT NULL,
    rank_position smallint unsigned DEFAULT NULL,
    played smallint unsigned NOT NULL DEFAULT 0,
    won smallint unsigned NOT NULL DEFAULT 0,
    drawn smallint unsigned NOT NULL DEFAULT 0,
    lost smallint unsigned NOT NULL DEFAULT 0,
    goals_for smallint unsigned NOT NULL DEFAULT 0,
    goals_against smallint unsigned NOT NULL DEFAULT 0,
    points smallint NOT NULL DEFAULT 0,
    notes varchar(255) DEFAULT NULL,
    updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    created_at timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (id),
    UNIQUE KEY ux_competition_standings_team (competition_id, team_id),
    KEY idx_competition_standings_competition (competition_id),
    KEY idx_competition_standings_team (team_id),
    KEY idx_competition_standings_order (competition_id, points, goals_for),
    CONSTRAINT fk_standing_competition
        FOREIGN KEY (competition_id)
        REFERENCES competitions (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_standing_team
        FOREIGN KEY (team_id)
        REFERENCES teams (id)
        ON DELETE CASCADE,
    CONSTRAINT chk_standing_played
        CHECK (played >= 0),
    CONSTRAINT chk_standing_won
        CHECK (won >= 0),
    CONSTRAINT chk_standing_drawn
        CHECK (drawn >= 0),
    CONSTRAINT chk_standing_lost
        CHECK (lost >= 0),
    CONSTRAINT chk_standing_goals_for
        CHECK (goals_for >= 0),
    CONSTRAINT chk_standing_goals_against
        CHECK (goals_against >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (code, description, scope)
SELECT 'competitions.standings.manage', 'Gestione classifiche competizioni', 'action'
WHERE NOT EXISTS (
    SELECT 1
    FROM permissions
    WHERE code = 'competitions.standings.manage'
);

INSERT INTO profile_permissions (profile_id, permission_id)
SELECT profiles.id, permissions.id
FROM profiles
JOIN permissions ON permissions.code = 'competitions.standings.manage'
WHERE profiles.code = 'admin'
  AND NOT EXISTS (
      SELECT 1
      FROM profile_permissions existing
      WHERE existing.profile_id = profiles.id
        AND existing.permission_id = permissions.id
  );

COMMIT;

-- Verification query:
-- SELECT c.name AS competition, t.name AS team, cs.points
-- FROM competition_standings cs
-- JOIN competitions c ON c.id = cs.competition_id
-- JOIN teams t ON t.id = cs.team_id
-- ORDER BY c.name, cs.points DESC, t.name;
