-- Add manual corrections for ambiguous WordPress/AnWP records.
-- Overrides affect only the local import and never modify WordPress data.

START TRANSACTION;

CREATE TABLE IF NOT EXISTS external_sync_overrides (
    id bigint unsigned NOT NULL AUTO_INCREMENT,
    source_id int(11) NOT NULL,
    entity_type varchar(30) NOT NULL,
    external_id varchar(100) NOT NULL,
    external_name varchar(255) DEFAULT NULL,
    football_type enum('5','7','11') DEFAULT NULL,
    season_local_id int(11) DEFAULT NULL,
    ignored tinyint(1) NOT NULL DEFAULT 0,
    source_context_json longtext DEFAULT NULL,
    issue_message varchar(500) DEFAULT NULL,
    last_error_at timestamp NULL DEFAULT NULL,
    created_at timestamp NOT NULL DEFAULT current_timestamp(),
    updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (id),
    UNIQUE KEY ux_external_sync_override (source_id, entity_type, external_id),
    KEY idx_external_sync_override_issue (source_id, issue_message(100)),
    KEY idx_external_sync_override_season (season_local_id),
    CONSTRAINT fk_external_sync_override_source
        FOREIGN KEY (source_id)
        REFERENCES external_sources (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_external_sync_override_season
        FOREIGN KEY (season_local_id)
        REFERENCES seasons (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE sync_runs
    ADD COLUMN ignored_count int unsigned NOT NULL DEFAULT 0 AFTER skipped_count;

COMMIT;

-- Verification queries:
-- SHOW COLUMNS FROM sync_runs LIKE 'ignored_count';
-- SELECT entity_type, external_id, external_name, football_type,
--        season_local_id, ignored, issue_message
-- FROM external_sync_overrides
-- ORDER BY entity_type, external_name;
