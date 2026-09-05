-- Add permissions for referee availability management.

START TRANSACTION;

INSERT INTO permissions (code, description, scope)
SELECT 'referee_availabilities.view', 'Visualizzazione disponibilita arbitri', 'action'
WHERE NOT EXISTS (
    SELECT 1
    FROM permissions
    WHERE code = 'referee_availabilities.view'
);

INSERT INTO permissions (code, description, scope)
SELECT 'referee_availabilities.create', 'Creazione disponibilita arbitri', 'action'
WHERE NOT EXISTS (
    SELECT 1
    FROM permissions
    WHERE code = 'referee_availabilities.create'
);

INSERT INTO permissions (code, description, scope)
SELECT 'referee_availabilities.edit', 'Modifica disponibilita arbitri', 'action'
WHERE NOT EXISTS (
    SELECT 1
    FROM permissions
    WHERE code = 'referee_availabilities.edit'
);

INSERT INTO permissions (code, description, scope)
SELECT 'referee_availabilities.delete', 'Eliminazione disponibilita arbitri', 'action'
WHERE NOT EXISTS (
    SELECT 1
    FROM permissions
    WHERE code = 'referee_availabilities.delete'
);

INSERT INTO profile_permissions (profile_id, permission_id)
SELECT profiles.id, permissions.id
FROM profiles
JOIN permissions ON permissions.code IN (
    'referee_availabilities.view',
    'referee_availabilities.create',
    'referee_availabilities.edit',
    'referee_availabilities.delete'
)
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
-- WHERE pm.code LIKE 'referee\_availabilities.%'
-- ORDER BY p.code, pm.code;
