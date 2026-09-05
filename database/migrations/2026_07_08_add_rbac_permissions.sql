-- Introduce configurable RBAC permissions.
-- Roles remain available as legacy compatibility; profiles and permissions become
-- the canonical source for page visibility and action authorization.

START TRANSACTION;

CREATE TABLE profiles (
    id int(11) NOT NULL AUTO_INCREMENT,
    code varchar(50) NOT NULL,
    name varchar(100) NOT NULL,
    is_system tinyint(1) NOT NULL DEFAULT 0,
    created_at timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (id),
    UNIQUE KEY ux_profiles_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE permissions (
    id int(11) NOT NULL AUTO_INCREMENT,
    code varchar(100) NOT NULL,
    description varchar(255) NOT NULL,
    scope enum('page','action','system') NOT NULL DEFAULT 'action',
    active tinyint(1) NOT NULL DEFAULT 1,
    created_at timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (id),
    UNIQUE KEY ux_permissions_code (code),
    KEY idx_permissions_scope (scope),
    KEY idx_permissions_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE profile_permissions (
    profile_id int(11) NOT NULL,
    permission_id int(11) NOT NULL,
    created_at timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (profile_id, permission_id),
    KEY idx_profile_permissions_permission (permission_id),
    CONSTRAINT fk_profile_permissions_profile
        FOREIGN KEY (profile_id)
        REFERENCES profiles (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_profile_permissions_permission
        FOREIGN KEY (permission_id)
        REFERENCES permissions (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE users
    ADD COLUMN profile_id int(11) DEFAULT NULL AFTER role,
    ADD KEY idx_users_profile (profile_id),
    ADD CONSTRAINT fk_user_profile
        FOREIGN KEY (profile_id)
        REFERENCES profiles (id)
        ON DELETE SET NULL;

INSERT INTO profiles (code, name, is_system) VALUES
('admin', 'Amministratore', 1),
('user', 'Utente', 1);

INSERT INTO permissions (code, description, scope) VALUES
('auth.me', 'Lettura identita utente autenticato', 'system'),
('dashboard.view', 'Visualizzazione dashboard', 'page'),
('movements.view', 'Visualizzazione movimenti', 'page'),
('movements.create', 'Creazione movimenti', 'action'),
('movements.edit', 'Modifica movimenti', 'action'),
('movements.delete', 'Eliminazione movimenti', 'action'),
('matches.view', 'Visualizzazione partite', 'page'),
('matches.create', 'Creazione partite', 'action'),
('matches.edit', 'Modifica partite', 'action'),
('matches.delete', 'Eliminazione partite', 'action'),
('seasons.view', 'Visualizzazione stagioni', 'page'),
('seasons.create', 'Creazione stagioni', 'action'),
('seasons.status.change', 'Cambio stato stagioni', 'action'),
('competitions.view', 'Visualizzazione competizioni', 'page'),
('competitions.create', 'Creazione competizioni', 'action'),
('competitions.edit', 'Modifica competizioni', 'action'),
('competitions.delete', 'Eliminazione competizioni', 'action'),
('teams.view', 'Visualizzazione squadre', 'page'),
('teams.create', 'Creazione squadre', 'action'),
('teams.edit', 'Modifica squadre', 'action'),
('teams.delete', 'Eliminazione squadre', 'action'),
('referees.view', 'Visualizzazione arbitri', 'page'),
('referees.create', 'Creazione arbitri', 'action'),
('referees.edit', 'Modifica arbitri', 'action'),
('referees.delete', 'Eliminazione arbitri', 'action'),
('fields.view', 'Visualizzazione stadi', 'page'),
('fields.create', 'Creazione stadi', 'action'),
('fields.edit', 'Modifica stadi', 'action'),
('fields.delete', 'Eliminazione stadi', 'action'),
('designations.view', 'Visualizzazione designazioni', 'page'),
('import.view', 'Visualizzazione import', 'page'),
('settings.view', 'Visualizzazione configurazione', 'page'),
('settings.manage', 'Gestione configurazione', 'action'),
('permissions.view', 'Visualizzazione permessi', 'page'),
('permissions.manage', 'Gestione permessi', 'action'),
('users.manage', 'Gestione utenti', 'action');

UPDATE users
SET profile_id = (
    SELECT id
    FROM profiles
    WHERE profiles.code = users.role
    LIMIT 1
)
WHERE profile_id IS NULL;

INSERT INTO profile_permissions (profile_id, permission_id)
SELECT profiles.id, permissions.id
FROM profiles
CROSS JOIN permissions
WHERE profiles.code = 'admin';

INSERT INTO profile_permissions (profile_id, permission_id)
SELECT profiles.id, permissions.id
FROM profiles
JOIN permissions ON permissions.code IN (
    'auth.me',
    'dashboard.view',
    'movements.view',
    'matches.view',
    'matches.create',
    'matches.edit',
    'competitions.view',
    'teams.view',
    'referees.view',
    'fields.view'
)
WHERE profiles.code = 'user';

COMMIT;

-- Verification query:
-- SELECT p.code AS profile, GROUP_CONCAT(pm.code ORDER BY pm.code SEPARATOR ', ') AS permissions
-- FROM profiles p
-- LEFT JOIN profile_permissions pp ON pp.profile_id = p.id
-- LEFT JOIN permissions pm ON pm.id = pp.permission_id
-- GROUP BY p.id, p.code
-- ORDER BY p.code;
