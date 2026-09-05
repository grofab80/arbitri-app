<?php
namespace Api\Models;

use Api\Core\Database;
use Api\Services\SecretCipher;

class ExternalSource {

    public static function wordpress(): ?array
    {
        $stmt = Database::get()->prepare(
            "SELECT
                es.*,
                (
                    SELECT COALESCE(sr.finished_at, sr.started_at)
                    FROM sync_runs sr
                    WHERE sr.source_id = es.id
                    ORDER BY sr.id DESC
                    LIMIT 1
                ) AS last_execution_at
             FROM external_sources es
             WHERE es.code = 'wordpress_anwp'
             LIMIT 1"
        );
        $stmt->execute();
        $source = $stmt->fetch();

        return $source ?: null;
    }

    public static function publicData(array $source): array
    {
        $hasApiKey = !empty($source['api_key_encrypted']);
        unset($source['api_key_encrypted']);
        $source['id'] = (int)$source['id'];
        $source['enabled'] = (int)$source['enabled'];
        $source['verify_ssl'] = (int)$source['verify_ssl'];
        $source['request_timeout'] = (int)$source['request_timeout'];
        $source['api_key_configured'] = $hasApiKey;

        return $source;
    }

    public static function apiKey(array $source): string
    {
        return SecretCipher::decrypt($source['api_key_encrypted'] ?? null);
    }

    public static function updateWordpress(array $data): bool
    {
        $source = self::wordpress();
        if (!$source) {
            throw new \RuntimeException('Sorgente WordPress non configurata nel database.');
        }

        $apiKeyEncrypted = $source['api_key_encrypted'];
        $newApiKey = trim((string)($data['api_key'] ?? ''));
        if ($newApiKey !== '') {
            $apiKeyEncrypted = SecretCipher::encrypt($newApiKey);
        }

        $baseUrl = rtrim(trim((string)$data['base_url']), '/');
        $connectionChanged = $baseUrl !== rtrim((string)$source['base_url'], '/') || $newApiKey !== '';
        $connectionStatus = $connectionChanged ? 'never' : (string)$source['connection_status'];
        $lastConnectionAt = $connectionChanged ? null : $source['last_connection_at'];
        $lastConnectionMessage = $connectionChanged ? null : $source['last_connection_message'];

        $stmt = Database::get()->prepare(
            "UPDATE external_sources SET
                name = ?,
                base_url = ?,
                api_key_encrypted = ?,
                enabled = ?,
                verify_ssl = ?,
                request_timeout = ?,
                connection_status = ?,
                last_connection_at = ?,
                last_connection_message = ?
             WHERE id = ?"
        );

        return $stmt->execute([
            trim((string)$data['name']),
            $baseUrl,
            $apiKeyEncrypted,
            self::boolValue($data['enabled'] ?? 0),
            self::boolValue($data['verify_ssl'] ?? 1),
            max(5, min(120, (int)($data['request_timeout'] ?? 20))),
            $connectionStatus,
            $lastConnectionAt,
            $lastConnectionMessage,
            (int)$source['id']
        ]);
    }

    public static function recordConnectionStatus(int $id, string $status, string $message): bool
    {
        if (!in_array($status, ['success', 'failed'], true)) {
            throw new \InvalidArgumentException('Stato connessione non valido.');
        }

        $stmt = Database::get()->prepare(
            "UPDATE external_sources
             SET connection_status = ?, last_connection_at = CURRENT_TIMESTAMP,
                 last_connection_message = ?
             WHERE id = ?"
        );
        return $stmt->execute([$status, substr($message, 0, 500), $id]);
    }

    public static function recordStatus(int $id, string $status, string $message, ?string $cursor = null): bool
    {
        $sql = "UPDATE external_sources SET last_status = ?, last_message = ?";
        $params = [$status, substr($message, 0, 500)];

        if ($cursor !== null) {
            $sql .= ", last_sync_cursor = ?, last_sync_at = CURRENT_TIMESTAMP";
            $params[] = $cursor;
        }

        $sql .= " WHERE id = ?";
        $params[] = $id;

        $stmt = Database::get()->prepare($sql);
        return $stmt->execute($params);
    }

    private static function boolValue($value): int
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
    }
}
