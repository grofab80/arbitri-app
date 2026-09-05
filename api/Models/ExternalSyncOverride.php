<?php
namespace Api\Models;

use Api\Core\Database;

class ExternalSyncOverride {

    private const ENTITY_TYPES = ['seasons', 'competitions'];

    public static function all(int $sourceId): array
    {
        $stmt = Database::get()->prepare(
            "SELECT eso.*, s.name AS season_name
             FROM external_sync_overrides eso
             LEFT JOIN seasons s ON s.id = eso.season_local_id
             WHERE eso.source_id = ?
             ORDER BY
                CASE WHEN eso.issue_message IS NULL THEN 1 ELSE 0 END,
                eso.entity_type,
                eso.external_name,
                eso.external_id"
        );
        $stmt->execute([$sourceId]);

        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function find(int $sourceId, string $entityType, $externalId): ?array
    {
        if ($externalId === null || $externalId === '') {
            return null;
        }

        $stmt = Database::get()->prepare(
            "SELECT * FROM external_sync_overrides
             WHERE source_id = ? AND entity_type = ? AND external_id = ?"
        );
        $stmt->execute([$sourceId, $entityType, (string)$externalId]);
        $row = $stmt->fetch();

        return $row ? self::hydrate($row) : null;
    }

    public static function isIgnored(int $sourceId, string $entityType, $externalId): bool
    {
        $override = self::find($sourceId, $entityType, $externalId);
        return $override && (int)$override['ignored'] === 1;
    }

    public static function recordIssue(
        int $sourceId,
        string $entityType,
        string $externalId,
        array $record,
        string $message
    ): void {
        if (!in_array($entityType, self::ENTITY_TYPES, true)) {
            return;
        }

        $name = trim((string)($record['name'] ?? '')) ?: null;
        $context = self::context($entityType, $record);
        $stmt = Database::get()->prepare(
            "INSERT INTO external_sync_overrides
                (source_id, entity_type, external_id, external_name, source_context_json, issue_message, last_error_at)
             VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
             ON DUPLICATE KEY UPDATE
                external_name = VALUES(external_name),
                source_context_json = VALUES(source_context_json),
                issue_message = VALUES(issue_message),
                last_error_at = CURRENT_TIMESTAMP"
        );
        $stmt->execute([
            $sourceId,
            $entityType,
            $externalId,
            $name,
            json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            substr($message, 0, 500)
        ]);
    }

    public static function clearIssue(int $sourceId, string $entityType, string $externalId): void
    {
        $override = self::find($sourceId, $entityType, $externalId);
        if (!$override) {
            return;
        }

        $hasConfiguration = !empty($override['football_type'])
            || !empty($override['season_local_id'])
            || (int)$override['ignored'] === 1;

        if (!$hasConfiguration) {
            $stmt = Database::get()->prepare("DELETE FROM external_sync_overrides WHERE id = ?");
            $stmt->execute([(int)$override['id']]);
            return;
        }

        $stmt = Database::get()->prepare(
            "UPDATE external_sync_overrides
             SET issue_message = NULL, last_error_at = NULL
             WHERE id = ?"
        );
        $stmt->execute([(int)$override['id']]);
    }

    public static function sync(int $sourceId, array $items): void
    {
        $db = Database::get();
        $db->beginTransaction();

        try {
            foreach ($items as $item) {
                $entityType = (string)$item['entity_type'];
                $externalId = (string)$item['external_id'];
                $footballType = $item['football_type'] ?: null;
                $seasonId = !empty($item['season_local_id']) ? (int)$item['season_local_id'] : null;
                $ignored = !empty($item['ignored']) ? 1 : 0;

                if ($seasonId !== null && !self::seasonExists($seasonId)) {
                    throw new \RuntimeException('La stagione locale selezionata non esiste.');
                }

                $stmt = $db->prepare(
                    "UPDATE external_sync_overrides
                     SET football_type = ?, season_local_id = ?, ignored = ?
                     WHERE source_id = ? AND entity_type = ? AND external_id = ?"
                );
                $stmt->execute([$footballType, $seasonId, $ignored, $sourceId, $entityType, $externalId]);

                if ($stmt->rowCount() === 0 && !self::find($sourceId, $entityType, $externalId)) {
                    throw new \RuntimeException('Correzione esterna non trovata: eseguire prima un sync completo.');
                }
            }

            $cleanup = $db->prepare(
                "DELETE FROM external_sync_overrides
                 WHERE source_id = ?
                   AND football_type IS NULL
                   AND season_local_id IS NULL
                   AND ignored = 0
                   AND issue_message IS NULL"
            );
            $cleanup->execute([$sourceId]);

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    private static function seasonExists(int $seasonId): bool
    {
        $stmt = Database::get()->prepare("SELECT 1 FROM seasons WHERE id = ?");
        $stmt->execute([$seasonId]);
        return (bool)$stmt->fetchColumn();
    }

    private static function context(string $entityType, array $record): array
    {
        if ($entityType === 'competitions') {
            return [
                'league_external_id' => $record['league_external_id'] ?? null,
                'league_name' => $record['league_name'] ?? null,
                'season_external_id' => $record['season_external_id'] ?? null,
                'season_external_ids' => $record['season_external_ids'] ?? [],
                'type' => $record['type'] ?? null,
                'football_type' => $record['football_type'] ?? null
            ];
        }

        return [
            'starts_on' => $record['starts_on'] ?? null,
            'ends_on' => $record['ends_on'] ?? null,
            'status' => $record['status'] ?? null
        ];
    }

    private static function hydrate(array $row): array
    {
        $context = json_decode((string)($row['source_context_json'] ?? ''), true);
        $row['source_context'] = is_array($context) ? $context : [];
        unset($row['source_context_json']);
        return $row;
    }
}
