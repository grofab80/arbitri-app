-- Add the local WordPress/AnWP import infrastructure.
-- Remote deletions are stored as tombstones and never delete local records.

START TRANSACTION;

CREATE TABLE IF NOT EXISTS external_sources (
    id int(11) NOT NULL AUTO_INCREMENT,
    code varchar(50) NOT NULL,
    name varchar(100) NOT NULL,
    base_url varchar(255) NOT NULL,
    api_key_encrypted text DEFAULT NULL,
    enabled tinyint(1) NOT NULL DEFAULT 0,
    verify_ssl tinyint(1) NOT NULL DEFAULT 1,
    request_timeout smallint unsigned NOT NULL DEFAULT 20,
    last_sync_cursor varchar(40) DEFAULT NULL,
    last_sync_at timestamp NULL DEFAULT NULL,
    last_status enum('never','success','partial','failed') NOT NULL DEFAULT 'never',
    last_message varchar(500) DEFAULT NULL,
    created_at timestamp NOT NULL DEFAULT current_timestamp(),
    updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (id),
    UNIQUE KEY ux_external_sources_code (code),
    KEY idx_external_sources_enabled (enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS external_mappings (
    id bigint unsigned NOT NULL AUTO_INCREMENT,
    source_id int(11) NOT NULL,
    entity_type varchar(30) NOT NULL,
    external_id varchar(100) NOT NULL,
    local_id int(11) NOT NULL,
    external_hash char(64) DEFAULT NULL,
    last_seen_at timestamp NULL DEFAULT NULL,
    last_synced_at timestamp NULL DEFAULT NULL,
    deleted_at_source timestamp NULL DEFAULT NULL,
    created_at timestamp NOT NULL DEFAULT current_timestamp(),
    updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (id),
    UNIQUE KEY ux_external_mapping (source_id, entity_type, external_id),
    KEY idx_external_mapping_local (entity_type, local_id),
    KEY idx_external_mapping_deleted (deleted_at_source),
    CONSTRAINT fk_external_mapping_source
        FOREIGN KEY (source_id)
        REFERENCES external_sources (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sync_runs (
    id bigint unsigned NOT NULL AUTO_INCREMENT,
    source_id int(11) NOT NULL,
    sync_mode enum('full','incremental') NOT NULL DEFAULT 'incremental',
    trigger_type enum('manual','scheduled') NOT NULL DEFAULT 'manual',
    status enum('running','success','partial','failed') NOT NULL DEFAULT 'running',
    cursor_from varchar(40) DEFAULT NULL,
    cursor_to varchar(40) DEFAULT NULL,
    created_count int unsigned NOT NULL DEFAULT 0,
    updated_count int unsigned NOT NULL DEFAULT 0,
    skipped_count int unsigned NOT NULL DEFAULT 0,
    failed_count int unsigned NOT NULL DEFAULT 0,
    deleted_count int unsigned NOT NULL DEFAULT 0,
    message varchar(500) DEFAULT NULL,
    created_by int(11) DEFAULT NULL,
    started_at timestamp NOT NULL DEFAULT current_timestamp(),
    finished_at timestamp NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_sync_runs_source_started (source_id, started_at),
    KEY idx_sync_runs_status (status),
    CONSTRAINT fk_sync_run_source
        FOREIGN KEY (source_id)
        REFERENCES external_sources (id),
    CONSTRAINT fk_sync_run_user
        FOREIGN KEY (created_by)
        REFERENCES users (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sync_run_items (
    id bigint unsigned NOT NULL AUTO_INCREMENT,
    sync_run_id bigint unsigned NOT NULL,
    entity_type varchar(30) NOT NULL,
    external_id varchar(100) NOT NULL,
    local_id int(11) DEFAULT NULL,
    action varchar(30) NOT NULL,
    message varchar(500) DEFAULT NULL,
    created_at timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (id),
    KEY idx_sync_run_items_run (sync_run_id),
    KEY idx_sync_run_items_entity (entity_type, external_id),
    KEY idx_sync_run_items_action (action),
    CONSTRAINT fk_sync_run_item_run
        FOREIGN KEY (sync_run_id)
        REFERENCES sync_runs (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO external_sources (code, name, base_url, enabled)
SELECT 'wordpress_anwp', 'WordPress / AnWP Football Leagues', '', 0
WHERE NOT EXISTS (
    SELECT 1 FROM external_sources WHERE code = 'wordpress_anwp'
);

INSERT INTO permissions (code, description, scope)
SELECT 'import.manage', 'Configurazione sorgente import WordPress', 'action'
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code = 'import.manage');

INSERT INTO permissions (code, description, scope)
SELECT 'import.run', 'Esecuzione import WordPress', 'action'
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code = 'import.run');

INSERT INTO profile_permissions (profile_id, permission_id)
SELECT p.id, pm.id
FROM profiles p
JOIN permissions pm ON pm.code IN ('import.view', 'import.manage', 'import.run')
WHERE p.code = 'admin'
  AND NOT EXISTS (
      SELECT 1
      FROM profile_permissions existing
      WHERE existing.profile_id = p.id
        AND existing.permission_id = pm.id
  );

COMMIT;

-- Verification query:
-- SELECT code, name, enabled, last_status, last_sync_cursor FROM external_sources;
-- SELECT code FROM permissions WHERE code LIKE 'import.%' ORDER BY code;
