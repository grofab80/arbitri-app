-- Standardize application roles.
-- Supported roles are:
-- - admin: full access
-- - user: read-only/basic access

START TRANSACTION;

ALTER TABLE users
    MODIFY role enum('admin','staff','user') NOT NULL DEFAULT 'user';

UPDATE users
SET role = 'user'
WHERE role = 'staff';

ALTER TABLE users
    MODIFY role enum('admin','user') NOT NULL DEFAULT 'user';

COMMIT;
