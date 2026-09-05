-- Introduce seasons and associate every movement to the current season.
-- The frontend movements grid should only show the current season, so season
-- is intentionally not added as a visible grid column.

START TRANSACTION;

CREATE TABLE IF NOT EXISTS seasons (
    id int(11) NOT NULL AUTO_INCREMENT,
    name varchar(9) NOT NULL,
    starts_on date NOT NULL,
    ends_on date NOT NULL,
    is_current tinyint(1) NOT NULL DEFAULT 0,
    created_at timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (id),
    UNIQUE KEY uq_seasons_name (name),
    KEY idx_seasons_current (is_current)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO seasons (name, starts_on, ends_on, is_current)
VALUES ('2025/2026', '2025-07-01', '2026-06-30', 1)
ON DUPLICATE KEY UPDATE
    starts_on = VALUES(starts_on),
    ends_on = VALUES(ends_on),
    is_current = 1;

UPDATE seasons
SET is_current = CASE WHEN name = '2025/2026' THEN 1 ELSE 0 END;

ALTER TABLE movements
    ADD COLUMN season_id int(11) DEFAULT NULL AFTER id,
    ADD KEY idx_movements_season (season_id);

UPDATE movements
SET season_id = (
    SELECT id
    FROM seasons
    WHERE is_current = 1
    LIMIT 1
)
WHERE season_id IS NULL;

ALTER TABLE movements
    MODIFY season_id int(11) NOT NULL,
    ADD CONSTRAINT fk_movement_season
        FOREIGN KEY (season_id)
        REFERENCES seasons (id);

COMMIT;
