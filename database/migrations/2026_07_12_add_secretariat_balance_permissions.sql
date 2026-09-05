-- Add secretariat profile and balance permissions.
-- The balance is derived from current-season movements.

START TRANSACTION;

INSERT INTO profiles (code, name, is_system)
SELECT 'segreteria', 'Segreteria', 1
WHERE NOT EXISTS (
    SELECT 1
    FROM profiles
    WHERE code = 'segreteria'
);

INSERT INTO permissions (code, description, scope)
SELECT 'balance.view', 'Visualizzazione bilancio', 'page'
WHERE NOT EXISTS (
    SELECT 1
    FROM permissions
    WHERE code = 'balance.view'
);

INSERT INTO permissions (code, description, scope)
SELECT 'balance.export', 'Esportazione bilancio', 'action'
WHERE NOT EXISTS (
    SELECT 1
    FROM permissions
    WHERE code = 'balance.export'
);

INSERT INTO profile_permissions (profile_id, permission_id)
SELECT profiles.id, permissions.id
FROM profiles
CROSS JOIN permissions
WHERE profiles.code = 'admin'
  AND permissions.code IN ('balance.view', 'balance.export')
  AND NOT EXISTS (
      SELECT 1
      FROM profile_permissions existing
      WHERE existing.profile_id = profiles.id
        AND existing.permission_id = permissions.id
  );

INSERT INTO profile_permissions (profile_id, permission_id)
SELECT profiles.id, permissions.id
FROM profiles
JOIN permissions ON permissions.code IN (
    'auth.me',
    'dashboard.view',
    'movements.view',
    'movements.create',
    'movements.edit',
    'balance.view'
)
WHERE profiles.code = 'segreteria'
  AND NOT EXISTS (
      SELECT 1
      FROM profile_permissions existing
      WHERE existing.profile_id = profiles.id
        AND existing.permission_id = permissions.id
  );

COMMIT;

-- Verification query:
-- SELECT p.code AS profile, GROUP_CONCAT(pm.code ORDER BY pm.code SEPARATOR ', ') AS permissions
-- FROM profiles p
-- LEFT JOIN profile_permissions pp ON pp.profile_id = p.id
-- LEFT JOIN permissions pm ON pm.id = pp.permission_id
-- WHERE p.code IN ('admin', 'segreteria')
-- GROUP BY p.id, p.code;
