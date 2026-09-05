-- Restrict all delete permissions to the admin profile only.

START TRANSACTION;

DELETE profile_permissions
FROM profile_permissions
JOIN profiles ON profiles.id = profile_permissions.profile_id
JOIN permissions ON permissions.id = profile_permissions.permission_id
WHERE profiles.code <> 'admin'
  AND permissions.code LIKE '%.delete';

COMMIT;
