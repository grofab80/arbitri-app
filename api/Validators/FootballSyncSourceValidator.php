<?php
namespace Api\Validators;

class FootballSyncSourceValidator {

    public static function validate(array $payload, bool $apiKeyAlreadyConfigured = false): array
    {
        $errors = [];
        $name = trim((string)($payload['name'] ?? ''));
        $baseUrl = trim((string)($payload['base_url'] ?? ''));
        $apiKey = trim((string)($payload['api_key'] ?? ''));
        $timeout = (int)($payload['request_timeout'] ?? 20);

        if ($name === '') {
            $errors['name'] = 'Nome sorgente obbligatorio';
        } elseif (strlen($name) > 100) {
            $errors['name'] = 'Nome sorgente troppo lungo';
        }

        if ($baseUrl === '' || !filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            $errors['base_url'] = 'Base URL WordPress non valida';
        } elseif (!in_array(strtolower((string)parse_url($baseUrl, PHP_URL_SCHEME)), ['http', 'https'], true)) {
            $errors['base_url'] = 'Sono ammessi solo URL HTTP o HTTPS';
        }

        if (!$apiKeyAlreadyConfigured && $apiKey === '') {
            $errors['api_key'] = 'Chiave API obbligatoria';
        }

        if ($timeout < 5 || $timeout > 120) {
            $errors['request_timeout'] = 'Timeout compreso tra 5 e 120 secondi';
        }

        return $errors;
    }
}
