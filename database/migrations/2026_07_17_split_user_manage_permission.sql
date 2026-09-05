-- Split legacy users.manage into explicit user action permissions.
-- Admin receives all user permissions by default; the legacy users.manage
-- permission is removed from the active catalog to avoid ambiguous UI choices.

START TRANSACTION;

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

INSERT INTO permissions (code, description, scope)
SELECT 'users.delete', 'Eliminazione utenti', 'action'
WHERE NOT EXISTS (
    SELECT 1
    FROM permissions
    WHERE code = 'users.delete'
);

INSERT INTO profile_permissions (profile_id, permission_id)
SELECT profiles.id, permissions.id
FROM profiles
JOIN permissions ON permissions.code IN ('users.create', 'users.edit', 'users.delete')
WHERE profiles.code = 'admin'
  AND NOT EXISTS (
      SELECT 1
      FROM profile_permissions existing
      WHERE existing.profile_id = profiles.id
        AND existing.permission_id = permissions.id
  );

DELETE profile_permissions
FROM profile_permissions
JOIN permissions ON permissions.id = profile_permissions.permission_id
WHERE permissions.code = 'users.manage';

DELETE FROM permissions
WHERE code = 'users.manage';

COMMIT;

-- Verification query:
-- SELECT p.code AS profile, pm.code AS permission
-- FROM profile_permissions pp
-- JOIN profiles p ON p.id = pp.profile_id
-- JOIN permissions pm ON pm.id = pp.permission_id
-- WHERE pm.code LIKE 'users.%'
-- ORDER BY p.code, pm.code;
