-- Add official balance snapshots for closed seasons.
-- The operating balance remains derived from movements; this table stores the
-- approved snapshot created when a season changes to status 'chiuso'.

START TRANSACTION;

CREATE TABLE IF NOT EXISTS balance_closures (
    id int(11) NOT NULL AUTO_INCREMENT,
    season_id int(11) NOT NULL,
    income decimal(10,2) NOT NULL DEFAULT 0.00,
    expenses decimal(10,2) NOT NULL DEFAULT 0.00,
    profit decimal(10,2) NOT NULL DEFAULT 0.00,
    total_movements int(11) NOT NULL DEFAULT 0,
    snapshot_json longtext DEFAULT NULL,
    closed_at timestamp NOT NULL DEFAULT current_timestamp(),
    created_at timestamp NOT NULL DEFAULT current_timestamp(),
    updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (id),
    UNIQUE KEY ux_balance_closures_season (season_id),
    CONSTRAINT fk_balance_closure_season
        FOREIGN KEY (season_id)
        REFERENCES seasons (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;

-- Verification query:
-- SELECT s.name, bc.income, bc.expenses, bc.profit, bc.total_movements, bc.closed_at
-- FROM balance_closures bc
-- JOIN seasons s ON s.id = bc.season_id
-- ORDER BY bc.closed_at DESC;
