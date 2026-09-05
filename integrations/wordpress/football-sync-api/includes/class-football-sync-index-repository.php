<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Football_Sync_Index_Repository
{
    private $wpdb;
    private $table;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = $wpdb->prefix . 'football_sync_index';
    }

    public function is_ready()
    {
        $found = $this->wpdb->get_var(
            $this->wpdb->prepare('SHOW TABLES LIKE %s', $this->wpdb->esc_like($this->table))
        );

        return $found === $this->table;
    }

    public function has_baseline()
    {
        return (string) get_option('football_sync_last_indexed_at', '') !== '';
    }

    public function last_indexed_at()
    {
        $value = (string) get_option('football_sync_last_indexed_at', '');
        return $value !== '' ? $value : null;
    }

    public function begin()
    {
        $this->wpdb->query('START TRANSACTION');
    }

    public function commit($indexed_at)
    {
        $this->wpdb->query('COMMIT');
        update_option('football_sync_last_indexed_at', $indexed_at, false);
    }

    public function rollback()
    {
        $this->wpdb->query('ROLLBACK');
    }

    public function refresh_entity($entity_type, array $records, $observed_at)
    {
        $scan_token = wp_generate_uuid4();
        $indexed = 0;

        foreach ($records as $record) {
            if (empty($record['external_id']) || empty($record['hash'])) {
                continue;
            }

            $sql = "INSERT INTO {$this->table}
                (entity_type, external_id, external_hash, scan_token, first_seen_at, last_seen_at, changed_at, deleted_at)
                VALUES (%s, %d, %s, %s, %s, %s, %s, NULL)
                ON DUPLICATE KEY UPDATE
                    changed_at = IF(external_hash <> VALUES(external_hash) OR deleted_at IS NOT NULL, VALUES(changed_at), changed_at),
                    external_hash = VALUES(external_hash),
                    scan_token = VALUES(scan_token),
                    last_seen_at = VALUES(last_seen_at),
                    deleted_at = NULL";

            $this->wpdb->query($this->wpdb->prepare(
                $sql,
                $entity_type,
                (int) $record['external_id'],
                $record['hash'],
                $scan_token,
                $observed_at,
                $observed_at,
                $observed_at
            ));

            if ($this->wpdb->last_error) {
                throw new RuntimeException('Errore aggiornamento indice: ' . $this->wpdb->last_error);
            }
            $indexed++;
        }

        $deleted = $this->wpdb->query($this->wpdb->prepare(
            "UPDATE {$this->table}
             SET deleted_at = %s, changed_at = %s
             WHERE entity_type = %s
               AND scan_token <> %s
               AND deleted_at IS NULL",
            $observed_at,
            $observed_at,
            $entity_type,
            $scan_token
        ));

        if ($this->wpdb->last_error) {
            throw new RuntimeException('Errore rilevazione cancellazioni: ' . $this->wpdb->last_error);
        }

        return array('indexed' => $indexed, 'deleted' => max(0, (int) $deleted));
    }

    public function changes_since($updated_after, array $entity_types)
    {
        $changes = array();

        foreach ($entity_types as $entity_type) {
            $updated = $this->wpdb->get_col($this->wpdb->prepare(
                "SELECT external_id FROM {$this->table}
                 WHERE entity_type = %s
                   AND changed_at > %s
                   AND deleted_at IS NULL
                 ORDER BY external_id",
                $entity_type,
                $updated_after
            ));
            $deleted = $this->wpdb->get_col($this->wpdb->prepare(
                "SELECT external_id FROM {$this->table}
                 WHERE entity_type = %s
                   AND deleted_at > %s
                 ORDER BY external_id",
                $entity_type,
                $updated_after
            ));

            $changes[$entity_type] = array(
                'updated' => array_map('intval', $updated),
                'deleted' => array_map('intval', $deleted),
            );
        }

        return $changes;
    }

    public function stats()
    {
        if (!$this->is_ready()) {
            return array('active' => 0, 'deleted' => 0);
        }

        $row = $this->wpdb->get_row(
            "SELECT
                SUM(CASE WHEN deleted_at IS NULL THEN 1 ELSE 0 END) AS active,
                SUM(CASE WHEN deleted_at IS NOT NULL THEN 1 ELSE 0 END) AS deleted
             FROM {$this->table}",
            ARRAY_A
        );

        return array(
            'active' => isset($row['active']) ? (int) $row['active'] : 0,
            'deleted' => isset($row['deleted']) ? (int) $row['deleted'] : 0,
        );
    }
}
