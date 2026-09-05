-- Assign designations page visibility to the user profile.
-- The designations module is operational for both user and admin profiles.

START TRANSACTION;

INSERT INTO profile_permissions (profile_id, permission_id)
SELECT profiles.id, permissions.id
FROM profiles
JOIN permissions ON permissions.code = 'designations.view'
WHERE profiles.code = 'user'
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
-- WHERE pm.code = 'designations.view';
