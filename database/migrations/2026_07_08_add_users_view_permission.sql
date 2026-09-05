-- Add user management page permission to RBAC catalog.

START TRANSACTION;

INSERT INTO permissions (code, description, scope)
SELECT 'users.view', 'Visualizzazione utenti', 'page'
WHERE NOT EXISTS (
    SELECT 1
    FROM permissions
    WHERE code = 'users.view'
);

INSERT INTO profile_permissions (profile_id, permission_id)
SELECT profiles.id, permissions.id
FROM profiles
JOIN permissions ON permissions.code = 'users.view'
WHERE profiles.code = 'admin'
  AND NOT EXISTS (
      SELECT 1
      FROM profile_permissions existing
      WHERE existing.profile_id = profiles.id
        AND existing.permission_id = permissions.id
  );

COMMIT;
