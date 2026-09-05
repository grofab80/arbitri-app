<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Football_Sync_Installer
{
    private const DB_VERSION = '0.2.0';

    public static function activate()
    {
        Football_Sync_Settings::activate();
        self::install();
    }

    public static function maybe_upgrade()
    {
        if (version_compare((string) get_option('football_sync_api_db_version', '0.0.0'), self::DB_VERSION, '<')) {
            self::install();
        }
    }

    public static function install()
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $index_table = $wpdb->prefix . 'football_sync_index';
        $logs_table = $wpdb->prefix . 'football_sync_logs';

        $index_sql = "CREATE TABLE {$index_table} (
            entity_type varchar(32) NOT NULL,
            external_id bigint(20) unsigned NOT NULL,
            external_hash char(64) NOT NULL,
            scan_token char(36) NOT NULL,
            first_seen_at datetime NOT NULL,
            last_seen_at datetime NOT NULL,
            changed_at datetime NOT NULL,
            deleted_at datetime DEFAULT NULL,
            PRIMARY KEY  (entity_type, external_id),
            KEY changed_at (changed_at),
            KEY deleted_at (deleted_at)
        ) {$charset_collate};";

        $logs_sql = "CREATE TABLE {$logs_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            request_id char(36) NOT NULL,
            endpoint varchar(100) NOT NULL,
            method varchar(10) NOT NULL,
            status_code smallint(5) unsigned NOT NULL,
            duration_ms int(10) unsigned NOT NULL DEFAULT 0,
            response_count int(10) unsigned DEFAULT NULL,
            client_hash char(64) DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY endpoint (endpoint),
            KEY created_at (created_at)
        ) {$charset_collate};";

        dbDelta($index_sql);
        dbDelta($logs_sql);

        add_option('football_sync_api_cache_ttl', 300, '', false);
        add_option('football_sync_api_log_retention_days', 30, '', false);
        add_option('football_sync_cache_generation', 1, '', false);
        update_option('football_sync_api_db_version', self::DB_VERSION, false);
    }
}
