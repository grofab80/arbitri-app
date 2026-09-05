<?php
namespace Api\Validators;

use Api\Models\Permission;
use Api\Models\User;

class UserValidator {

    public static function save(array $payload, ?int $id = null): array
    {
        $errors = [];
        $username = trim((string)($payload['username'] ?? ''));
        $firstName = trim((string)($payload['first_name'] ?? ''));
        $lastName = trim((string)($payload['last_name'] ?? ''));
        $email = trim((string)($payload['email'] ?? ''));
        $password = trim((string)($payload['password'] ?? ''));
        $profileId = (int)($payload['profile_id'] ?? 0);

        if ($username === '') {
            $errors['username'] = 'Username obbligatorio';
        } elseif (User::usernameExists($username, $id)) {
            $errors['username'] = 'Username gia utilizzato';
        }

        if ($firstName === '') {
            $errors['first_name'] = 'Nome obbligatorio';
        }

        if ($lastName === '') {
            $errors['last_name'] = 'Cognome obbligatorio';
        }

        if ($email !== '') {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Email non valida';
            } elseif (User::emailExists($email, $id)) {
                $errors['email'] = 'Email gia utilizzata';
            }
        }

        if (!$id && $password === '') {
            $errors['password'] = 'Password obbligatoria';
        }

        if ($password !== '' && strlen($password) < 8) {
            $errors['password'] = 'La password deve avere almeno 8 caratteri';
        }

        if ($profileId <= 0) {
            $errors['profile_id'] = 'Profilo obbligatorio';
        } elseif (!Permission::profile($profileId)) {
            $errors['profile_id'] = 'Profilo non valido';
        }

        return $errors;
    }

    public static function profile(array $payload): array
    {
        $errors = [];
        $profileId = (int)($payload['profile_id'] ?? 0);

        if ($profileId <= 0) {
            $errors['profile_id'] = 'Profilo obbligatorio';
            return $errors;
        }

        if (!Permission::profile($profileId)) {
            $errors['profile_id'] = 'Profilo non valido';
        }

        return $errors;
    }
}
