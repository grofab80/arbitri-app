-- Add base data model for referee designations.
-- Phase 1 introduces match difficulty, designation records, referee/team
-- blacklist and RBAC action permissions. UI and automatic proposal logic will
-- be introduced in later phases.

START TRANSACTION;

ALTER TABLE matches
    ADD COLUMN difficulty_rating tinyint unsigned NOT NULL DEFAULT 3 AFTER match_day,
    ADD CONSTRAINT chk_match_difficulty_rating
        CHECK (difficulty_rating BETWEEN 1 AND 5);

CREATE TABLE IF NOT EXISTS designations (
    id int(11) NOT NULL AUTO_INCREMENT,
    match_id int(11) NOT NULL,
    referee_id int(11) DEFAULT NULL,
    status enum('proposta','confermata','modificata') NOT NULL DEFAULT 'proposta',
    assignment_type enum('auto','manual') NOT NULL DEFAULT 'auto',
    score decimal(6,2) DEFAULT NULL,
    score_details_json longtext DEFAULT NULL,
    notes varchar(255) DEFAULT NULL,
    created_by int(11) DEFAULT NULL,
    updated_by int(11) DEFAULT NULL,
    created_at timestamp NOT NULL DEFAULT current_timestamp(),
    updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (id),
    UNIQUE KEY ux_designations_match (match_id),
    KEY idx_designations_referee (referee_id),
    KEY idx_designations_status (status),
    KEY idx_designations_created_by (created_by),
    KEY idx_designations_updated_by (updated_by),
    CONSTRAINT fk_designation_match
        FOREIGN KEY (match_id)
        REFERENCES matches (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_designation_referee
        FOREIGN KEY (referee_id)
        REFERENCES referees (id)
        ON DELETE SET NULL,
    CONSTRAINT fk_designation_created_by
        FOREIGN KEY (created_by)
        REFERENCES users (id)
        ON DELETE SET NULL,
    CONSTRAINT fk_designation_updated_by
        FOREIGN KEY (updated_by)
        REFERENCES users (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS referee_team_blacklist (
    id int(11) NOT NULL AUTO_INCREMENT,
    referee_id int(11) NOT NULL,
    team_id int(11) NOT NULL,
    reason varchar(255) DEFAULT NULL,
    active tinyint(1) NOT NULL DEFAULT 1,
    created_at timestamp NOT NULL DEFAULT current_timestamp(),
    updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (id),
    UNIQUE KEY ux_referee_team_blacklist (referee_id, team_id),
    KEY idx_referee_team_blacklist_referee (referee_id),
    KEY idx_referee_team_blacklist_team (team_id),
    KEY idx_referee_team_blacklist_active (active),
    CONSTRAINT fk_referee_team_blacklist_referee
        FOREIGN KEY (referee_id)
        REFERENCES referees (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_referee_team_blacklist_team
        FOREIGN KEY (team_id)
        REFERENCES teams (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (code, description, scope)
SELECT 'designations.generate', 'Generazione proposta designazioni', 'action'
WHERE NOT EXISTS (
    SELECT 1 FROM permissions WHERE code = 'designations.generate'
);

INSERT INTO permissions (code, description, scope)
SELECT 'designations.edit', 'Modifica designazioni', 'action'
WHERE NOT EXISTS (
    SELECT 1 FROM permissions WHERE code = 'designations.edit'
);

INSERT INTO permissions (code, description, scope)
SELECT 'designations.confirm', 'Conferma designazioni', 'action'
WHERE NOT EXISTS (
    SELECT 1 FROM permissions WHERE code = 'designations.confirm'
);

INSERT INTO permissions (code, description, scope)
SELECT 'designations.blacklist.manage', 'Gestione blacklist arbitro squadra', 'action'
WHERE NOT EXISTS (
    SELECT 1 FROM permissions WHERE code = 'designations.blacklist.manage'
);

INSERT INTO profile_permissions (profile_id, permission_id)
SELECT profiles.id, permissions.id
FROM profiles
JOIN permissions ON permissions.code IN (
    'designations.generate',
    'designations.edit',
    'designations.confirm',
    'designations.blacklist.manage'
)
WHERE profiles.code = 'admin'
  AND NOT EXISTS (
      SELECT 1
      FROM profile_permissions existing
      WHERE existing.profile_id = profiles.id
        AND existing.permission_id = permissions.id
  );

INSERT INTO profile_permissions (profile_id, permission_id)
SELECT profiles.id, permissions.id
FROM profiles
JOIN permissions ON permissions.code IN (
    'designations.generate',
    'designations.edit',
    'designations.confirm'
)
WHERE profiles.code = 'user'
  AND NOT EXISTS (
      SELECT 1
      FROM profile_permissions existing
      WHERE existing.profile_id = profiles.id
        AND existing.permission_id = permissions.id
  );

COMMIT;

-- Verification queries:
-- SHOW COLUMNS FROM matches LIKE 'difficulty_rating';
-- SELECT COUNT(*) AS designations_table FROM information_schema.tables
-- WHERE table_schema = DATABASE() AND table_name = 'designations';
-- SELECT COUNT(*) AS blacklist_table FROM information_schema.tables
-- WHERE table_schema = DATABASE() AND table_name = 'referee_team_blacklist';
-- SELECT p.code AS profile, pm.code AS permission
-- FROM profile_permissions pp
-- JOIN profiles p ON p.id = pp.profile_id
-- JOIN permissions pm ON pm.id = pp.permission_id
-- WHERE pm.code LIKE 'designations.%'
-- ORDER BY p.code, pm.code;
