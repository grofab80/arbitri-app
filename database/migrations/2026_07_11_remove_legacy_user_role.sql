-- Remove legacy users.role after RBAC profile migration.
-- profiles/profile_id is now the canonical authorization profile.

START TRANSACTION;

UPDATE users
JOIN profiles ON profiles.code = users.role
SET users.profile_id = profiles.id
WHERE users.profile_id IS NULL;

UPDATE users
SET profile_id = (
    SELECT id
    FROM profiles
    WHERE code = 'user'
    LIMIT 1
)
WHERE profile_id IS NULL;

ALTER TABLE users
    DROP FOREIGN KEY fk_user_profile;

ALTER TABLE users
    MODIFY profile_id int(11) NOT NULL,
    ADD CONSTRAINT fk_user_profile
        FOREIGN KEY (profile_id)
        REFERENCES profiles (id);

ALTER TABLE users
    DROP COLUMN role;

COMMIT;
