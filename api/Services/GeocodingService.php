<?php
namespace Api\Services;

class GeocodingService {

    public static function geocode(array $address): ?array
    {
        $query = self::addressQuery($address);

        if ($query === '') {
            return null;
        }

        $cfg = require __DIR__ . '/../../config/geocoding.php';

        $url = $cfg['endpoint'] . '?' . http_build_query([
            'format' => 'jsonv2',
            'limit' => 1,
            'addressdetails' => 1,
            'q' => $query
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => (int)($cfg['timeout'] ?? 8),
                'header' => implode("\r\n", [
                    'User-Agent: ' . ($cfg['user_agent'] ?? 'arbitri-app/1.0'),
                    'Accept: application/json'
                ])
            ]
        ]);

        $raw = @file_get_contents($url, false, $context);

        if ($raw === false) {
            throw new \RuntimeException('Servizio geocoding non raggiungibile');
        }

        $data = json_decode($raw, true);

        if (!is_array($data) || empty($data[0])) {
            return null;
        }

        $match = $data[0];

        if (!isset($match['lat'], $match['lon'])) {
            return null;
        }

        return [
            'latitude' => (float)$match['lat'],
            'longitude' => (float)$match['lon'],
            'display_name' => $match['display_name'] ?? $query,
            'provider' => $cfg['provider'] ?? 'nominatim'
        ];
    }

    public static function addressQuery(array $address): string
    {
        return implode(', ', array_filter([
            trim((string)($address['address'] ?? '')),
            trim((string)($address['postal_code'] ?? '')),
            trim((string)($address['city'] ?? '')),
            trim((string)($address['province'] ?? '')),
            trim((string)($address['country'] ?? 'Italia'))
        ]));
    }
}
