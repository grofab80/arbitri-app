<?php

$environment = strtolower((string)(getenv('APP_ENV') ?: 'local'));
$secret = (string)(getenv('JWT_SECRET') ?: '');

if (in_array($environment, ['production', 'prod'], true) && strlen($secret) < 32) {
    throw new RuntimeException('JWT_SECRET deve contenere almeno 32 caratteri in produzione.');
}

return [
    'secret' => $secret !== '' ? $secret : 'CAMBIA-QUESTA-CHIAVE-MOLTO-LUNGA',
    'issuer' => getenv('JWT_ISSUER') ?: 'arbitri-app',
    'expire' => max(300, (int)(getenv('JWT_EXPIRE') ?: 3600))
];
