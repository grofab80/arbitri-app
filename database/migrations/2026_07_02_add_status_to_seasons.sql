-- Add explicit season status while keeping is_current for compatibility.

START TRANSACTION;

ALTER TABLE seasons
    ADD COLUMN status enum('nuovo','in_corso','chiuso') NOT NULL DEFAULT 'nuovo' AFTER ends_on,
    ADD KEY idx_seasons_status (status);

UPDATE seasons
SET status = CASE
    WHEN is_current = 1 THEN 'in_corso'
    ELSE 'nuovo'
END;

COMMIT;
