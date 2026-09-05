<?php
namespace Api\Validators;

class RefereeValidator {

    public static function validate(array $payload): array
    {
        $errors = [];

        if (empty(trim($payload['name'] ?? ''))) {
            $errors['name'] = 'Nome arbitro obbligatorio';
        }

        if (
            isset($payload['rating'])
            && (!is_numeric($payload['rating']) || (int)$payload['rating'] < 1 || (int)$payload['rating'] > 5)
        ) {
            $errors['rating'] = 'Rating arbitro non valido';
        }

        $maxLengths = [
            'address' => 255,
            'city' => 100,
            'province' => 50,
            'postal_code' => 20,
            'country' => 100
        ];

        foreach ($maxLengths as $field => $maxLength) {
            if (isset($payload[$field]) && mb_strlen(trim((string)$payload[$field])) > $maxLength) {
                $errors[$field] = 'Valore troppo lungo';
            }
        }

        foreach (['can_referee_11', 'can_referee_7', 'can_referee_5'] as $field) {
            if (isset($payload[$field]) && !in_array((int)$payload[$field], [0, 1], true)) {
                $errors[$field] = 'Valore non valido';
            }
        }

        if (
            empty($errors['can_referee_11'])
            && empty($errors['can_referee_7'])
            && empty($errors['can_referee_5'])
            && (int)($payload['can_referee_11'] ?? 0) === 0
            && (int)($payload['can_referee_7'] ?? 0) === 0
            && (int)($payload['can_referee_5'] ?? 0) === 0
        ) {
            $errors['football_types'] = 'Seleziona almeno una tipologia';
        }

        return $errors;
    }
}
