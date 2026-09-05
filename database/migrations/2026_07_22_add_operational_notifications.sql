-- Add operational notification storage.
-- Operational alerts represent the current state; notifications are created
-- only when an alert count increases compared to the last stored snapshot.

START TRANSACTION;

CREATE TABLE IF NOT EXISTS operational_alert_snapshots (
    id int(11) NOT NULL AUTO_INCREMENT,
    alert_code varchar(100) NOT NULL,
    count_value int(11) NOT NULL DEFAULT 0,
    last_checked_at timestamp NULL DEFAULT NULL,
    created_at timestamp NOT NULL DEFAULT current_timestamp(),
    updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (id),
    UNIQUE KEY ux_operational_alert_snapshots_code (alert_code),
    CONSTRAINT chk_operational_alert_snapshot_count
        CHECK (count_value >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS operational_notifications (
    id int(11) NOT NULL AUTO_INCREMENT,
    alert_code varchar(100) NOT NULL,
    title varchar(150) NOT NULL,
    message varchar(255) NOT NULL,
    delta int(11) NOT NULL DEFAULT 1,
    count_value int(11) NOT NULL DEFAULT 0,
    href varchar(255) DEFAULT NULL,
    required_permissions_json longtext DEFAULT NULL,
    created_at timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (id),
    KEY idx_operational_notifications_alert (alert_code),
    KEY idx_operational_notifications_created_at (created_at),
    CONSTRAINT chk_operational_notification_delta
        CHECK (delta > 0),
    CONSTRAINT chk_operational_notification_count
        CHECK (count_value >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS operational_notification_reads (
    notification_id int(11) NOT NULL,
    user_id int(11) NOT NULL,
    read_at timestamp NULL DEFAULT NULL,
    dismissed_at timestamp NULL DEFAULT NULL,
    PRIMARY KEY (notification_id, user_id),
    KEY idx_operational_notification_reads_user (user_id),
    CONSTRAINT fk_operational_notification_read_notification
        FOREIGN KEY (notification_id)
        REFERENCES operational_notifications (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_operational_notification_read_user
        FOREIGN KEY (user_id)
        REFERENCES users (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;

-- Verification queries:
-- SHOW TABLES LIKE 'operational_%';
--
-- SELECT alert_code, count_value, last_checked_at
-- FROM operational_alert_snapshots
-- ORDER BY alert_code;
--
-- SELECT alert_code, title, delta, count_value, href, created_at
-- FROM operational_notifications
-- ORDER BY created_at DESC;
