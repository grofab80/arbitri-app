-- Enrich balance closures for approval/storicization workflow.
-- The operating balance remains derived from movements until approval logic is
-- introduced. These fields prepare the official snapshot lifecycle.

START TRANSACTION;

ALTER TABLE balance_closures
    ADD COLUMN approval_status enum('draft','approved') NOT NULL DEFAULT 'draft' AFTER snapshot_json,
    ADD COLUMN approved_at timestamp NULL DEFAULT NULL AFTER approval_status,
    ADD COLUMN approved_by int(11) DEFAULT NULL AFTER approved_at,
    ADD COLUMN movements_deleted_at timestamp NULL DEFAULT NULL AFTER approved_by,
    ADD COLUMN deleted_movements_count int(11) NOT NULL DEFAULT 0 AFTER movements_deleted_at,
    ADD COLUMN snapshot_version smallint unsigned NOT NULL DEFAULT 1 AFTER deleted_movements_count,
    ADD COLUMN snapshot_hash char(64) DEFAULT NULL AFTER snapshot_version,
    ADD KEY idx_balance_closures_approval_status (approval_status),
    ADD KEY idx_balance_closures_approved_by (approved_by),
    ADD CONSTRAINT fk_balance_closure_approved_by
        FOREIGN KEY (approved_by)
        REFERENCES users (id)
        ON DELETE SET NULL;

COMMIT;

-- Verification query:
-- SELECT s.name, bc.approval_status, bc.approved_at, bc.approved_by,
--        bc.movements_deleted_at, bc.deleted_movements_count,
--        bc.snapshot_version, bc.snapshot_hash
-- FROM balance_closures bc
-- JOIN seasons s ON s.id = bc.season_id
-- ORDER BY s.starts_on;
