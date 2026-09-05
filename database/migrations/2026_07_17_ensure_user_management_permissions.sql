-- Ensure user management permissions are available and assigned to admin.
-- This keeps the users page actions visible/enabled even on databases that
-- missed an intermediate RBAC migration during development.

START TRANSACTION;

INSERT INTO permissions (code, description, scope)
SELECT 'users.view', 'Visualizzazione utenti', 'page'
WHERE NOT EXISTS (
    SELECT 1
    FROM permissions
    WHERE code = 'users.view'
);

INSERT INTO permissions (code, description, scope)
SELECT 'users.create', 'Creazione utenti', 'action'
WHERE NOT EXISTS (
    SELECT 1
    FROM permissions
    WHERE code = 'users.create'
);

INSERT INTO permissions (code, description, scope)
SELECT 'users.edit', 'Modifica utenti', 'action'
WHERE NOT EXISTS (
    SELECT 1
    FROM permissions
    WHERE code = 'users.edit'
);

INSERT INTO profile_permissions (profile_id, permission_id)
SELECT profiles.id, permissions.id
FROM profiles
JOIN permissions ON permissions.code IN ('users.view', 'users.create', 'users.edit')
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
-- WHERE p.code = 'admin'
--   AND pm.code IN ('users.view', 'users.create', 'users.edit')
-- ORDER BY pm.code;
