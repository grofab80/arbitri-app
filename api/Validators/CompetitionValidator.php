<?php
namespace Api\Validators;

class CompetitionValidator {

    public static function validate(array $payload): array
    {
        $errors = [];

        if (empty(trim($payload['name'] ?? ''))) {
            $errors['name'] = 'Nome competizione obbligatorio';
        }

        if (empty($payload['season_id']) || (int)$payload['season_id'] <= 0) {
            $errors['season_id'] = 'Stagione obbligatoria';
        }

        if (empty($payload['type']) || !in_array($payload['type'], ['campionato', 'torneo'], true)) {
            $errors['type'] = 'Tipo competizione non valido';
        }

        if (empty($payload['football_type']) || !in_array((string)$payload['football_type'], ['11', '7', '5'], true)) {
            $errors['football_type'] = 'Tipologia calcio non valida';
        }

        return $errors;
    }
}
