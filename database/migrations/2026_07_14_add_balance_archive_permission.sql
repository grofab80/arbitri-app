-- Add permission for archiving approved balance closures by deleting movements.

START TRANSACTION;

INSERT INTO permissions (code, description, scope)
SELECT 'balance.archive', 'Storicizzazione bilancio approvato', 'action'
WHERE NOT EXISTS (
    SELECT 1
    FROM permissions
    WHERE code = 'balance.archive'
);

INSERT INTO profile_permissions (profile_id, permission_id)
SELECT profiles.id, permissions.id
FROM profiles
JOIN permissions ON permissions.code = 'balance.archive'
WHERE profiles.code = 'admin'
  AND NOT EXISTS (
      SELECT 1
      FROM profile_permissions existing
      WHERE existing.profile_id = profiles.id
        AND existing.permission_id = permissions.id
  );

COMMIT;

-- Verification query:
-- SELECT p.code AS profile, pm.code AS permission
-- FROM profile_permissions pp
-- JOIN profiles p ON p.id = pp.profile_id
-- JOIN permissions pm ON pm.id = pp.permission_id
-- WHERE pm.code = 'balance.archive';
