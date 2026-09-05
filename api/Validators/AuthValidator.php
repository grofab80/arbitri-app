<?php
namespace Api\Validators;

class AuthValidator {

    public static function login(array $payload): array
    {
        $errors = [];

        if (empty($payload['username'])) {
            $errors['username'] = 'Username obbligatorio';
        }

        if (empty($payload['password'])) {
            $errors['password'] = 'Password obbligatoria';
        }

        return $errors;
    }
}
