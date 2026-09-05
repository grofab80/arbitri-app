<?php

$env = static function (string $name, $default = null) {
    $value = getenv($name);
    return $value === false ? $default : $value;
};

return [
    'host' => $env('DB_HOST', 'localhost'),
    'port' => (int)$env('DB_PORT', 3306),
    'name' => $env('DB_NAME', 'arbitri_app'),
    'user' => $env('DB_USER', 'root'),
    'pass' => $env('DB_PASS', ''),
    'charset' => $env('DB_CHARSET', 'utf8mb4')
];
