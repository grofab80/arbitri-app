<?php
namespace Api\Controllers;

use Api\Http\Request;
use Api\Middleware\JwtMiddleware;
use Api\Models\User;
use Api\Services\AuthService;
use Api\Validators\AuthValidator;
use Api\V1\Response;

class AuthController {

    public function login()
    {
        $data = Request::json();

        $errors = AuthValidator::login($data);

        if (!empty($errors)) {
            Response::error('Validazione fallita', 422, $errors);
        }

        $token = AuthService::login(
            $data['username'] ?? '',
            $data['password'] ?? ''
        );

        if (!$token) {
            Response::error('Credenziali errate', 401);
        }

        Response::ok(['token' => $token]);
    }

    public function me()
    {
        $payload = JwtMiddleware::user();
        $userId = (int) ($payload->uid ?? 0);
        $user = $userId > 0 ? User::findById($userId) : null;

        if (!$user) {
            Response::error('Utente non trovato', 404);
        }

        Response::ok([
            'id' => (int) $user['id'],
            'username' => $user['username'],
            'profile_id' => isset($user['profile_id']) ? (int) $user['profile_id'] : null,
            'profile_code' => $user['profile_code'] ?? '',
            'profile_name' => $user['profile_name'] ?? '',
            'first_name' => $user['first_name'] ?? '',
            'last_name' => $user['last_name'] ?? '',
            'email' => $user['email'] ?? '',
            'permissions' => User::permissions((int) $user['id'])
        ]);
    }
}
