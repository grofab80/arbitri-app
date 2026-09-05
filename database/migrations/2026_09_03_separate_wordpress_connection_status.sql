-- Separate WordPress connection health from synchronization results.

START TRANSACTION;

ALTER TABLE external_sources
    ADD COLUMN connection_status enum('never','success','failed') NOT NULL DEFAULT 'never' AFTER request_timeout,
    ADD COLUMN last_connection_at timestamp NULL DEFAULT NULL AFTER connection_status,
    ADD COLUMN last_connection_message varchar(500) DEFAULT NULL AFTER last_connection_at;

-- A successful or partial sync proves that the remote connection worked.
UPDATE external_sources
SET connection_status = 'success',
    last_connection_at = COALESCE(last_sync_at, updated_at),
    last_connection_message = 'Connessione disponibile durante l''ultima sincronizzazione.'
WHERE last_status IN ('success', 'partial');

COMMIT;

-- Verification query:
-- SELECT code, connection_status, last_connection_at, last_status, last_sync_at
-- FROM external_sources;
