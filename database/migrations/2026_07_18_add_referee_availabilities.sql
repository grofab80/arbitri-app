-- Add referee availability calendar.
-- Availabilities can be recurring by weekday or specific to one date.
-- Times are stored as TIME, so an end at 24:00 should be entered as 23:59.

START TRANSACTION;

CREATE TABLE IF NOT EXISTS referee_availabilities (
    id int(11) NOT NULL AUTO_INCREMENT,
    referee_id int(11) NOT NULL,
    type enum('recurring','specific') NOT NULL,
    weekday tinyint unsigned DEFAULT NULL,
    available_date date DEFAULT NULL,
    start_time time NOT NULL,
    end_time time NOT NULL,
    is_available tinyint(1) NOT NULL DEFAULT 1,
    notes varchar(255) DEFAULT NULL,
    created_at timestamp NOT NULL DEFAULT current_timestamp(),
    updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (id),
    KEY idx_referee_availabilities_referee (referee_id),
    KEY idx_referee_availabilities_recurring (referee_id, weekday, start_time, end_time),
    KEY idx_referee_availabilities_specific (referee_id, available_date, start_time, end_time),
    CONSTRAINT fk_referee_availability_referee
        FOREIGN KEY (referee_id)
        REFERENCES referees (id)
        ON DELETE CASCADE,
    CONSTRAINT chk_referee_availability_weekday
        CHECK (weekday IS NULL OR weekday BETWEEN 1 AND 7),
    CONSTRAINT chk_referee_availability_time_range
        CHECK (start_time < end_time),
    CONSTRAINT chk_referee_availability_type_fields
        CHECK (
            (type = 'recurring' AND weekday IS NOT NULL AND available_date IS NULL)
            OR
            (type = 'specific' AND weekday IS NULL AND available_date IS NOT NULL)
        )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;

-- Verification query:
-- SELECT r.name, a.type, a.weekday, a.available_date, a.start_time, a.end_time, a.is_available
-- FROM referee_availabilities a
-- JOIN referees r ON r.id = a.referee_id
-- ORDER BY r.name, a.type, a.available_date, a.weekday, a.start_time;
