-- Add observable progress to WordPress synchronization runs.

START TRANSACTION;

ALTER TABLE sync_runs
    ADD COLUMN total_count int unsigned NOT NULL DEFAULT 0 AFTER cursor_to,
    ADD COLUMN processed_count int unsigned NOT NULL DEFAULT 0 AFTER total_count,
    ADD COLUMN current_entity varchar(30) DEFAULT NULL AFTER processed_count,
    ADD COLUMN heartbeat_at timestamp NULL DEFAULT NULL AFTER current_entity;

COMMIT;

-- Verification query:
-- SELECT id, status, total_count, processed_count, current_entity, heartbeat_at
-- FROM sync_runs
-- ORDER BY id DESC
-- LIMIT 10;
