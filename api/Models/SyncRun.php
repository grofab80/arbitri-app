<?php
namespace Api\Models;

use Api\Core\Database;

class SyncRun {

    public static function all(int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));
        $stmt = Database::get()->prepare(
            "SELECT
                sr.*,
                es.name AS source_name,
                CONCAT_WS(' ', u.first_name, u.last_name) AS user_name
             FROM sync_runs sr
             JOIN external_sources es ON es.id = sr.source_id
             LEFT JOIN users u ON u.id = sr.created_by
             ORDER BY sr.started_at DESC, sr.id DESC
             LIMIT $limit"
        );
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function items(int $runId): array
    {
        $stmt = Database::get()->prepare(
            "SELECT id, entity_type, external_id, local_id, action, message, created_at
             FROM sync_run_items
             WHERE sync_run_id = ?
             ORDER BY id"
        );
        $stmt->execute([$runId]);

        return $stmt->fetchAll();
    }

    public static function isRunning(int $sourceId): bool
    {
        self::expireStale($sourceId);
        $stmt = Database::get()->prepare(
            "SELECT 1 FROM sync_runs
             WHERE source_id = ? AND status = 'running'
               AND COALESCE(heartbeat_at, started_at) >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
             LIMIT 1"
        );
        $stmt->execute([$sourceId]);

        return (bool)$stmt->fetchColumn();
    }

    public static function start(int $sourceId, string $mode, ?int $userId, string $cursorFrom): int
    {
        self::expireStale($sourceId);

        $stmt = Database::get()->prepare(
            "INSERT INTO sync_runs
                (source_id, sync_mode, trigger_type, status, cursor_from, created_by, heartbeat_at)
             VALUES (?, ?, 'manual', 'running', ?, ?, CURRENT_TIMESTAMP)"
        );
        $stmt->execute([$sourceId, $mode, $cursorFrom, $userId]);

        return (int)Database::get()->lastInsertId();
    }

    public static function initializeProgress(int $runId, int $total): void
    {
        $stmt = Database::get()->prepare(
            "UPDATE sync_runs
             SET total_count = ?, processed_count = 0, current_entity = NULL,
                 heartbeat_at = CURRENT_TIMESTAMP
             WHERE id = ? AND status = 'running'"
        );
        $stmt->execute([max(0, $total), $runId]);
    }

    public static function setCurrentEntity(int $runId, string $entityType): void
    {
        $stmt = Database::get()->prepare(
            "UPDATE sync_runs
             SET current_entity = ?, heartbeat_at = CURRENT_TIMESTAMP
             WHERE id = ? AND status = 'running'"
        );
        $stmt->execute([$entityType, $runId]);
    }

    public static function advance(int $runId): void
    {
        $stmt = Database::get()->prepare(
            "UPDATE sync_runs
             SET processed_count = LEAST(total_count, processed_count + 1),
                 heartbeat_at = CURRENT_TIMESTAMP
             WHERE id = ? AND status = 'running'"
        );
        $stmt->execute([$runId]);
    }

    public static function activeProgress(int $sourceId): ?array
    {
        self::expireStale($sourceId);
        $stmt = Database::get()->prepare(
            "SELECT
                id, sync_mode, status, total_count, processed_count,
                current_entity, heartbeat_at, started_at,
                TIMESTAMPDIFF(SECOND, started_at, NOW()) AS elapsed_seconds
             FROM sync_runs
             WHERE source_id = ? AND status = 'running'
             ORDER BY id DESC
             LIMIT 1"
        );
        $stmt->execute([$sourceId]);
        $run = $stmt->fetch();
        if (!$run) {
            return null;
        }

        $total = (int)$run['total_count'];
        $processed = (int)$run['processed_count'];
        $run['percentage'] = $total > 0
            ? min(100, round(($processed / $total) * 100, 1))
            : null;

        return $run;
    }

    private static function expireStale(int $sourceId): void
    {
        $stale = Database::get()->prepare(
            "UPDATE sync_runs
             SET status = 'failed', current_entity = NULL,
                 message = 'Esecuzione interrotta o scaduta.',
                 finished_at = CURRENT_TIMESTAMP
             WHERE source_id = ? AND status = 'running'
               AND COALESCE(heartbeat_at, started_at) < DATE_SUB(NOW(), INTERVAL 30 MINUTE)"
        );
        $stale->execute([$sourceId]);
    }

    public static function addItem(
        int $runId,
        string $entityType,
        string $externalId,
        ?int $localId,
        string $action,
        ?string $message = null
    ): void {
        $stmt = Database::get()->prepare(
            "INSERT INTO sync_run_items
                (sync_run_id, entity_type, external_id, local_id, action, message)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $runId,
            $entityType,
            $externalId,
            $localId,
            $action,
            $message === null ? null : substr($message, 0, 500)
        ]);
    }

    public static function finish(
        int $runId,
        string $status,
        ?string $cursorTo,
        array $counts,
        string $message
    ): void {
        $stmt = Database::get()->prepare(
            "UPDATE sync_runs SET
                status = ?, cursor_to = ?, created_count = ?, updated_count = ?,
                skipped_count = ?, ignored_count = ?, failed_count = ?, deleted_count = ?,
                current_entity = NULL, heartbeat_at = CURRENT_TIMESTAMP,
                message = ?, finished_at = CURRENT_TIMESTAMP
             WHERE id = ?"
        );
        $stmt->execute([
            $status,
            $cursorTo,
            (int)($counts['created'] ?? 0),
            (int)($counts['updated'] ?? 0),
            (int)($counts['skipped'] ?? 0),
            (int)($counts['ignored'] ?? 0),
            (int)($counts['failed'] ?? 0),
            (int)($counts['deleted_at_source'] ?? 0),
            substr($message, 0, 500),
            $runId
        ]);
    }
}
