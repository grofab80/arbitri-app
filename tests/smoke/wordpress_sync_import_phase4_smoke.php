<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Api\Services\FootballSyncClient;
use Api\Services\SecretCipher;
use Api\Models\ExternalSource;
use Api\Models\FootballSyncImport;
use Api\Models\SyncRun;
use Api\Validators\FootballSyncSourceValidator;
use Api\Validators\FootballSyncOverrideValidator;

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: $message\n");
        exit(1);
    }
}

$secret = 'phase-4-test-secret';
$encrypted = SecretCipher::encrypt($secret);
assertTrue($encrypted !== $secret, 'La chiave API non deve restare in chiaro.');
assertTrue(SecretCipher::decrypt($encrypted) === $secret, 'Round-trip cifratura non riuscito.');

$tampered = substr($encrypted, 0, -2) . 'xx';
$tamperRejected = false;
try {
    SecretCipher::decrypt($tampered);
} catch (RuntimeException $e) {
    $tamperRejected = true;
}
assertTrue($tamperRejected, 'Un payload cifrato alterato deve essere rifiutato.');

$valid = FootballSyncSourceValidator::validate([
    'name' => 'WordPress produzione',
    'base_url' => 'https://example.test',
    'request_timeout' => 20
], true);
assertTrue($valid === [], 'Una sorgente valida non deve produrre errori.');

$invalid = FootballSyncSourceValidator::validate([
    'name' => '',
    'base_url' => 'javascript:alert(1)',
    'request_timeout' => 2
], false);
assertTrue(isset($invalid['name'], $invalid['base_url'], $invalid['api_key'], $invalid['request_timeout']), 'Validazione sorgente incompleta.');

new FootballSyncClient('https://example.test', 'test-key', true, 20);

$invalidUrlRejected = false;
try {
    new FootballSyncClient('not-a-url', 'test-key');
} catch (InvalidArgumentException $e) {
    $invalidUrlRejected = true;
}
assertTrue($invalidUrlRejected, 'Il client deve rifiutare una base URL non valida.');

$validOverrides = FootballSyncOverrideValidator::validate([
    'items' => [[
        'entity_type' => 'competitions',
        'external_id' => '19330',
        'football_type' => '11',
        'season_local_id' => 1,
        'ignored' => false
    ]]
]);
assertTrue($validOverrides === [], 'Una correzione mapping valida non deve produrre errori.');

$invalidOverrides = FootballSyncOverrideValidator::validate([
    'items' => [[
        'entity_type' => 'seasons',
        'external_id' => '150',
        'football_type' => '7'
    ]]
]);
assertTrue(isset($invalidOverrides['items']), 'La disciplina non deve essere applicabile a una stagione.');
assertTrue(method_exists(SyncRun::class, 'initializeProgress'), 'Inizializzazione avanzamento sync mancante.');
assertTrue(method_exists(SyncRun::class, 'activeProgress'), 'Lettura avanzamento sync mancante.');
assertTrue(method_exists(ExternalSource::class, 'recordConnectionStatus'), 'Stato connessione separato dallo stato sync mancante.');

$normalizeSeason = new ReflectionMethod(FootballSyncImport::class, 'canonicalSeasonName');
$normalizeSeason->setAccessible(true);
assertTrue(
    $normalizeSeason->invoke(null, '2025-2026') === '2025/2026',
    'Il nome stagione WordPress deve usare il formato canonico locale.'
);
assertTrue(
    $normalizeSeason->invoke(null, '2025/2026') === '2025/2026',
    'Un nome stagione gia canonico non deve cambiare.'
);

echo "WordPress sync import phase 4 smoke test: OK\n";
