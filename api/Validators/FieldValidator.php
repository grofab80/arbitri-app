<?php
namespace Api\Validators;

class FieldValidator {

    public static function validate(array $payload): array
    {
        $errors = [];

        if (empty(trim($payload['name'] ?? ''))) {
            $errors['name'] = 'Nome stadio obbligatorio';
        }

        foreach (['can_host_11', 'can_host_7', 'can_host_5', 'is_active'] as $field) {
            if (isset($payload[$field]) && !in_array((int)$payload[$field], [0, 1], true)) {
                $errors[$field] = 'Valore non valido';
            }
        }

        if (
            empty($errors['can_host_11'])
            && empty($errors['can_host_7'])
            && empty($errors['can_host_5'])
            && (int)($payload['can_host_11'] ?? 0) === 0
            && (int)($payload['can_host_7'] ?? 0) === 0
            && (int)($payload['can_host_5'] ?? 0) === 0
        ) {
            $errors['football_types'] = 'Seleziona almeno una tipologia';
        }

        return $errors;
    }
}
