<?php
namespace Api\Models;

use Api\Core\Database;

class ExternalMapping {

    public static function find(int $sourceId, string $entityType, string $externalId): ?array
    {
        $stmt = Database::get()->prepare(
            "SELECT * FROM external_mappings
             WHERE source_id = ? AND entity_type = ? AND external_id = ?"
        );
        $stmt->execute([$sourceId, $entityType, $externalId]);

        return $stmt->fetch() ?: null;
    }

    public static function localId(int $sourceId, string $entityType, $externalId): ?int
    {
        if ($externalId === null || $externalId === '') {
            return null;
        }

        $mapping = self::find($sourceId, $entityType, (string)$externalId);
        return $mapping && empty($mapping['deleted_at_source'])
            ? (int)$mapping['local_id']
            : null;
    }

    public static function save(
        int $sourceId,
        string $entityType,
        string $externalId,
        int $localId,
        ?string $hash
    ): void {
        $stmt = Database::get()->prepare(
            "INSERT INTO external_mappings
                (source_id, entity_type, external_id, local_id, external_hash, last_seen_at, last_synced_at)
             VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
             ON DUPLICATE KEY UPDATE
                local_id = VALUES(local_id),
                external_hash = VALUES(external_hash),
                last_seen_at = CURRENT_TIMESTAMP,
                last_synced_at = CURRENT_TIMESTAMP,
                deleted_at_source = NULL"
        );
        $stmt->execute([$sourceId, $entityType, $externalId, $localId, $hash]);
    }

    public static function markDeleted(int $sourceId, string $entityType, string $externalId): ?int
    {
        $mapping = self::find($sourceId, $entityType, $externalId);
        if (!$mapping) {
            return null;
        }

        $stmt = Database::get()->prepare(
            "UPDATE external_mappings
             SET deleted_at_source = CURRENT_TIMESTAMP, last_seen_at = CURRENT_TIMESTAMP
             WHERE id = ?"
        );
        $stmt->execute([(int)$mapping['id']]);

        return (int)$mapping['local_id'];
    }
}
