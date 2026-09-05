<?php
namespace Api\Controllers;

use Api\Http\Request;
use Api\Middleware\JwtMiddleware;
use Api\Models\Permission;
use Api\Models\User;
use Api\Validators\UserValidator;
use Api\V1\Response;

class UserController {

    public function index(): void
    {
        Response::ok([
            'users' => User::all(),
            'profiles' => Permission::profiles()
        ]);
    }

    public function store(): void
    {
        $payload = Request::json();
        $errors = UserValidator::save($payload);

        if (!empty($errors)) {
            Response::error('Validazione fallita', 422, $errors);
        }

        $id = User::create($payload);

        Response::ok(['id' => $id]);
    }

    public function update(): void
    {
        $id = (int)($_GET['id'] ?? 0);

        if (!$id) {
            Response::error('ID utente mancante', 400);
        }

        if (!User::findById($id)) {
            Response::error('Utente non trovato', 404);
        }

        $payload = Request::json();
        $errors = UserValidator::save($payload, $id);

        if (!empty($errors)) {
            Response::error('Validazione fallita', 422, $errors);
        }

        User::update($id, $payload);

        Response::ok(['updated' => true]);
    }

    public function delete(): void
    {
        $id = (int)($_GET['id'] ?? 0);

        if (!$id) {
            Response::error('ID utente mancante', 400);
        }

        $user = User::findById($id);

        if (!$user) {
            Response::error('Utente non trovato', 404);
        }

        $payload = JwtMiddleware::user();
        $currentUserId = (int)($payload->uid ?? 0);

        if ($currentUserId === $id) {
            Response::error('Non puoi eliminare il tuo utente', 409);
        }

        if (($user['profile_code'] ?? '') === 'admin' && User::adminCount() <= 1) {
            Response::error('Non puoi eliminare l\'ultimo amministratore', 409);
        }

        User::delete($id);

        Response::ok(['deleted' => true]);
    }

    public function updateProfile(): void
    {
        $id = (int)($_GET['id'] ?? 0);

        if (!$id) {
            Response::error('ID utente mancante', 400);
        }

        if (!User::findById($id)) {
            Response::error('Utente non trovato', 404);
        }

        $payload = Request::json();
        $errors = UserValidator::profile($payload);

        if (!empty($errors)) {
            Response::error('Validazione fallita', 422, $errors);
        }

        User::updateProfile($id, (int)$payload['profile_id']);

        Response::ok(['updated' => true]);
    }
}
