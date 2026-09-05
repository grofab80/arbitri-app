-- Add profile fields to users for frontend display and contact information.

START TRANSACTION;

ALTER TABLE users
    ADD COLUMN first_name varchar(100) DEFAULT NULL AFTER username,
    ADD COLUMN last_name varchar(100) DEFAULT NULL AFTER first_name,
    ADD COLUMN email varchar(150) DEFAULT NULL AFTER last_name;

UPDATE users
SET
    first_name = 'Admin',
    last_name = 'ASDACA',
    email = 'admin@asdaca.local'
WHERE username = 'admin'
  AND first_name IS NULL;

UPDATE users
SET
    first_name = 'Utente',
    last_name = 'Test',
    email = 'user@asdaca.local'
WHERE username = 'user'
  AND first_name IS NULL;

COMMIT;
