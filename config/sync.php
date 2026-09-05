<?php

$environmentKey = getenv('FOOTBALL_SYNC_ENCRYPTION_KEY') ?: '';
$environment = strtolower((string)(getenv('APP_ENV') ?: 'local'));
$jwt = require __DIR__ . '/jwt.php';

if (in_array($environment, ['production', 'prod'], true) && strlen($environmentKey) < 32) {
    throw new RuntimeException(
        'FOOTBALL_SYNC_ENCRYPTION_KEY deve contenere almeno 32 caratteri in produzione.'
    );
}

return [
    // Set FOOTBALL_SYNC_ENCRYPTION_KEY in production. The JWT-derived fallback
    // keeps existing local installations usable without storing plaintext keys.
    'encryption_key' => $environmentKey !== ''
        ? $environmentKey
        : ($jwt['secret'] . '|football-sync'),
    'connect_timeout' => 5,
    'default_timeout' => 20,
    'max_timeout' => 120
];
