-- Add geocoding fields to fields/stadiums.

START TRANSACTION;

ALTER TABLE fields
    ADD COLUMN latitude decimal(10,7) DEFAULT NULL AFTER city,
    ADD COLUMN longitude decimal(10,7) DEFAULT NULL AFTER latitude,
    ADD COLUMN geocoded_at timestamp NULL DEFAULT NULL AFTER longitude;

COMMIT;
