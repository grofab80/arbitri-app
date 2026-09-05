-- Merge the WordPress season 2025-2026 into the canonical current season
-- 2025/2026. The separator difference previously created a duplicate season.
-- This migration preserves all local and imported records.

START TRANSACTION;

SET @canonical_season_id := (
    SELECT id
    FROM seasons
    WHERE name = '2025/2026'
      AND (status = 'in_corso' OR is_current = 1)
    ORDER BY is_current DESC, id
    LIMIT 1
);

SET @duplicate_season_id := (
    SELECT id
    FROM seasons
    WHERE name = '2025-2026'
      AND id <> @canonical_season_id
    ORDER BY id
    LIMIT 1
);

-- A missing canonical season intentionally causes a constraint error and
-- rolls back the migration instead of attaching records to an arbitrary ID.
UPDATE competitions
SET season_id = @canonical_season_id,
    season = '2025/2026'
WHERE season_id = @duplicate_season_id;

UPDATE matches
SET season_id = @canonical_season_id
WHERE season_id = @duplicate_season_id;

UPDATE movements
SET season_id = @canonical_season_id
WHERE season_id = @duplicate_season_id;

UPDATE balance_closures
SET season_id = @canonical_season_id
WHERE season_id = @duplicate_season_id;

UPDATE external_sync_overrides
SET season_local_id = @canonical_season_id
WHERE season_local_id = @duplicate_season_id;

UPDATE external_mappings
SET local_id = @canonical_season_id
WHERE entity_type = 'seasons'
  AND local_id = @duplicate_season_id;

DELETE FROM seasons
WHERE id = @duplicate_season_id;

COMMIT;

-- Verification queries:
-- SELECT id, name, status, is_current
-- FROM seasons
-- WHERE REPLACE(name, '-', '/') = '2025/2026';
--
-- SELECT s.id, s.name, COUNT(m.id) AS matches
-- FROM seasons s
-- LEFT JOIN matches m ON m.season_id = s.id
-- WHERE s.name = '2025/2026'
-- GROUP BY s.id, s.name;
--
-- SELECT em.external_id, em.local_id, s.name
-- FROM external_mappings em
-- JOIN seasons s ON s.id = em.local_id
-- WHERE em.entity_type = 'seasons'
--   AND em.external_id = '151';
