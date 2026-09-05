<?php
namespace Api\Services;

class FootballSyncClient implements FootballSyncGateway {

    private string $baseUrl;
    private string $apiKey;
    private bool $verifySsl;
    private int $timeout;

    public function __construct(string $baseUrl, string $apiKey, bool $verifySsl = true, int $timeout = 20)
    {
        $this->baseUrl = self::normalizeBaseUrl($baseUrl);
        $this->apiKey = trim($apiKey);
        $this->verifySsl = $verifySsl;
        $this->timeout = max(1, $timeout);
    }

    public function info(): array
    {
        return $this->get('info');
    }

    public function changes(string $updatedAfter, bool $refresh = true): array
    {
        return $this->get('sync', [
            'updated_after' => $updatedAfter,
            'refresh' => $refresh ? 'true' : 'false'
        ]);
    }

    public function record(string $entityType, string $externalId): array
    {
        $allowed = ['seasons', 'competitions', 'teams', 'stadiums', 'referees', 'matches'];
        if (!in_array($entityType, $allowed, true)) {
            throw new \InvalidArgumentException('Tipo entita sync non supportato.');
        }

        $response = $this->get($entityType, ['id' => $externalId, 'limit' => 1]);
        $records = $response['data'] ?? [];

        if (!is_array($records) || count($records) !== 1 || !is_array($records[0])) {
            throw new \RuntimeException("Record $entityType/$externalId non trovato nella sorgente.");
        }

        return $records[0];
    }

    public function records(string $entityType, array $externalIds): array
    {
        $externalIds = array_values(array_unique(array_map('strval', $externalIds)));
        if (!$externalIds) {
            return [];
        }

        if (count($externalIds) <= 10) {
            $records = [];
            foreach ($externalIds as $externalId) {
                $records[$externalId] = $this->record($entityType, $externalId);
            }
            return $records;
        }

        $wanted = array_fill_keys($externalIds, true);
        $records = [];
        $page = 1;

        do {
            $response = $this->get($entityType, ['page' => $page, 'limit' => 500]);
            foreach ((array)($response['data'] ?? []) as $record) {
                $externalId = (string)($record['external_id'] ?? '');
                if (isset($wanted[$externalId])) {
                    $records[$externalId] = $record;
                }
            }
            $page++;
        } while (!empty($response['has_more']) && count($records) < count($wanted));

        return $records;
    }

    private function get(string $path, array $query = []): array
    {
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('Estensione cURL non disponibile.');
        }

        if ($this->apiKey === '') {
            throw new \RuntimeException('Chiave API WordPress non configurata.');
        }

        $url = $this->baseUrl . '/' . ltrim($path, '/');
        if ($query) {
            $url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        $config = require __DIR__ . '/../../config/sync.php';
        $handle = curl_init($url);
        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'X-API-Key: ' . $this->apiKey
            ],
            CURLOPT_CONNECTTIMEOUT => (int)($config['connect_timeout'] ?? 5),
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => $this->verifySsl,
            CURLOPT_SSL_VERIFYHOST => $this->verifySsl ? 2 : 0
        ]);

        $body = curl_exec($handle);
        $status = (int)curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $curlError = curl_error($handle);
        curl_close($handle);

        if ($body === false) {
            throw new \RuntimeException('Errore collegamento WordPress: ' . $curlError);
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException("Risposta WordPress non valida (HTTP $status).");
        }

        if ($status < 200 || $status >= 300 || empty($decoded['success'])) {
            $message = $decoded['error'] ?? "Errore WordPress HTTP $status";
            $code = $decoded['code'] ?? 'REMOTE_ERROR';
            throw new \RuntimeException($message . ' [' . $code . ']');
        }

        return $decoded;
    }

    private static function normalizeBaseUrl(string $baseUrl): string
    {
        $baseUrl = rtrim(trim($baseUrl), '/');
        if (!preg_match('#^https?://#i', $baseUrl)) {
            throw new \InvalidArgumentException('Base URL WordPress non valida.');
        }

        if (!preg_match('#/wp-json/football-sync/v1$#i', $baseUrl)) {
            $baseUrl .= '/wp-json/football-sync/v1';
        }

        return $baseUrl;
    }
}
