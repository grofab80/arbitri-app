<?php
namespace Api\Services;

use Api\Models\User;
use Firebase\JWT\JWT;

class AuthService {

    public static function login(string $username, string $password): ?string
    {
        $user = User::findByUsername($username);

        if (!$user || !password_verify($password, $user['password'])) {
            return null;
        }

        $cfg = require __DIR__ . '/../../config/jwt.php';

        $payload = [
            'iss'  => $cfg['issuer'],
            'iat'  => time(),
            'exp'  => time() + $cfg['expire'],
            'uid'  => $user['id'],
            'profile_code' => $user['profile_code'] ?? '',
            'profile_name' => $user['profile_name'] ?? '',
            'first_name' => $user['first_name'] ?? '',
            'last_name' => $user['last_name'] ?? '',
            'email' => $user['email'] ?? '',
            'permissions' => User::permissions((int) $user['id'])
        ];

        return JWT::encode($payload, $cfg['secret'], 'HS256');
    }
}
