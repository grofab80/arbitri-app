-- Add the migration ledger to installations created before baseline 2026-09-05.
-- New installations already receive this table from the baseline schema.

START TRANSACTION;

CREATE TABLE IF NOT EXISTS schema_migrations (
    version varchar(100) NOT NULL,
    description varchar(255) NOT NULL,
    applied_at timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO schema_migrations (version, description)
VALUES ('baseline:2026-09-05', 'Schema and reference data baseline')
ON DUPLICATE KEY UPDATE description = VALUES(description);

COMMIT;

-- Verification query:
-- SELECT version, description, applied_at
-- FROM schema_migrations
-- ORDER BY applied_at, version;
