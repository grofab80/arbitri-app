<?php
namespace Api\Validators;

class TeamValidator {

    public static function validate(array $payload): array
    {
        $errors = [];

        if (empty(trim($payload['name'] ?? ''))) {
            $errors['name'] = 'Nome squadra obbligatorio';
        }

        if (!empty($payload['field_id']) && (int)$payload['field_id'] <= 0) {
            $errors['field_id'] = 'Stadio non valido';
        }

        $competitionIds = $payload['competition_ids'] ?? [];
        if (!is_array($competitionIds) || empty($competitionIds)) {
            $errors['competition_ids'] = 'Seleziona almeno una competizione';
        } else {
            foreach ($competitionIds as $competitionId) {
                if ((int)$competitionId <= 0) {
                    $errors['competition_ids'] = 'Competizione non valida';
                    break;
                }
            }
        }

        return $errors;
    }
}
