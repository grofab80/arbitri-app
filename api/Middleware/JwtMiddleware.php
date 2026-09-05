<?php
namespace Api\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Api\Models\User;
use Api\V1\Response;

class JwtMiddleware {

    private static ?object $user = null;

    public static function check() {

        $authorization = self::authorizationHeader();

        if (!$authorization) {
            Response::error('Token mancante', 401);
        }

        if (!preg_match('/^Bearer\s+(\S+)$/i', $authorization, $matches)) {
            Response::error('Token non valido', 401);
        }

        $token = $matches[1];
        $cfg = require __DIR__ . '/../../config/jwt.php';

        try {
            self::$user = JWT::decode($token, new Key($cfg['secret'], 'HS256'));
            return self::$user;
        } catch (\Exception $e) {
            Response::error('Token scaduto o non valido', 401);
        }
    }

    public static function user(): ?object
    {
        return self::$user;
    }

    public static function authorize(array $roles): void
    {
        $user = self::user();
        $profileCode = $user->profile_code ?? null;

        if (empty($profileCode)) {
            $storedUser = User::findById((int)($user->uid ?? 0));
            $profileCode = $storedUser['profile_code'] ?? null;
        }

        if (!$user || empty($profileCode) || !in_array($profileCode, $roles, true)) {
            Response::error('Accesso non autorizzato', 403);
        }
    }

    public static function authorizePermissions(array $permissions): void
    {
        $user = self::user();
        $userId = (int) ($user->uid ?? 0);

        if (!$user || $userId <= 0 || !User::hasPermissions($userId, $permissions)) {
            Response::error('Accesso non autorizzato', 403);
        }
    }

    private static function authorizationHeader(): ?string
    {
        if (function_exists('getallheaders')) {
            foreach (getallheaders() as $name => $value) {
                if (strtolower($name) === 'authorization') {
                    return trim($value);
                }
            }
        }

        $serverKeys = [
            'HTTP_AUTHORIZATION',
            'REDIRECT_HTTP_AUTHORIZATION'
        ];

        foreach ($serverKeys as $key) {
            if (!empty($_SERVER[$key])) {
                return trim($_SERVER[$key]);
            }
        }

        return null;
    }
}
