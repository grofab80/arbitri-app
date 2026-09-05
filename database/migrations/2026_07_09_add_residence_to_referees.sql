-- Add residence address fields to referees.
-- Latitude/longitude are prepared for future OpenStreetMap/Nominatim geocoding.

START TRANSACTION;

ALTER TABLE referees
    ADD COLUMN address varchar(255) DEFAULT NULL AFTER rating,
    ADD COLUMN city varchar(100) DEFAULT NULL AFTER address,
    ADD COLUMN province varchar(50) DEFAULT NULL AFTER city,
    ADD COLUMN postal_code varchar(20) DEFAULT NULL AFTER province,
    ADD COLUMN country varchar(100) DEFAULT 'Italia' AFTER postal_code,
    ADD COLUMN latitude decimal(10,7) DEFAULT NULL AFTER country,
    ADD COLUMN longitude decimal(10,7) DEFAULT NULL AFTER latitude,
    ADD COLUMN geocoded_at timestamp NULL DEFAULT NULL AFTER longitude,
    ADD KEY idx_referees_city (city),
    ADD KEY idx_referees_province (province);

COMMIT;
