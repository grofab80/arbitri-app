<?php
namespace Api\Services;

class SecretCipher {

    private const PREFIX = 'v1:';

    public static function encrypt(string $plaintext): string
    {
        if ($plaintext === '') {
            return '';
        }

        self::assertOpenSsl();
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt(
            $plaintext,
            'aes-256-gcm',
            self::key(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($ciphertext === false) {
            throw new \RuntimeException('Impossibile cifrare la chiave API.');
        }

        return self::PREFIX . base64_encode($iv . $tag . $ciphertext);
    }

    public static function decrypt(?string $payload): string
    {
        if (!$payload) {
            return '';
        }

        self::assertOpenSsl();

        if (strpos($payload, self::PREFIX) !== 0) {
            throw new \RuntimeException('Formato chiave API cifrata non supportato.');
        }

        $decoded = base64_decode(substr($payload, strlen(self::PREFIX)), true);
        if ($decoded === false || strlen($decoded) < 29) {
            throw new \RuntimeException('Chiave API cifrata non valida.');
        }

        $plaintext = openssl_decrypt(
            substr($decoded, 28),
            'aes-256-gcm',
            self::key(),
            OPENSSL_RAW_DATA,
            substr($decoded, 0, 12),
            substr($decoded, 12, 16)
        );

        if ($plaintext === false) {
            throw new \RuntimeException('Impossibile decifrare la chiave API.');
        }

        return $plaintext;
    }

    private static function key(): string
    {
        $config = require __DIR__ . '/../../config/sync.php';
        $key = (string)($config['encryption_key'] ?? '');

        if ($key === '') {
            throw new \RuntimeException('Chiave di cifratura sync non configurata.');
        }

        return hash('sha256', $key, true);
    }

    private static function assertOpenSsl(): void
    {
        if (!function_exists('openssl_encrypt') || !function_exists('openssl_decrypt')) {
            throw new \RuntimeException('Estensione OpenSSL non disponibile.');
        }
    }
}
