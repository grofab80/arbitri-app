-- Add structured address details to fields/stadiums.
-- This aligns stadium addresses with referee residence addresses.

START TRANSACTION;

ALTER TABLE fields
    ADD COLUMN province varchar(50) DEFAULT NULL AFTER city,
    ADD COLUMN postal_code varchar(20) DEFAULT NULL AFTER province,
    ADD COLUMN country varchar(100) DEFAULT 'Italia' AFTER postal_code,
    ADD KEY idx_fields_province (province);

COMMIT;
