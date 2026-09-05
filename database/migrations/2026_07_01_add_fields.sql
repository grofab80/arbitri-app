-- Add playing fields and connect them to matches.

START TRANSACTION;

CREATE TABLE IF NOT EXISTS fields (
    id int(11) NOT NULL AUTO_INCREMENT,
    name varchar(100) NOT NULL,
    address varchar(150) DEFAULT NULL,
    city varchar(100) DEFAULT NULL,
    can_host_11 tinyint(1) NOT NULL DEFAULT 1,
    can_host_7 tinyint(1) NOT NULL DEFAULT 1,
    can_host_5 tinyint(1) NOT NULL DEFAULT 1,
    is_active tinyint(1) NOT NULL DEFAULT 1,
    notes varchar(255) DEFAULT NULL,
    created_at timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (id),
    KEY idx_fields_name (name),
    KEY idx_fields_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE matches
    ADD KEY idx_matches_field (field_id),
    ADD CONSTRAINT fk_match_field
        FOREIGN KEY (field_id)
        REFERENCES fields (id)
        ON DELETE SET NULL;

COMMIT;
