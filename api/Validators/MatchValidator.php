<?php
namespace Api\Validators;

class MatchValidator {

    public static function validate(array $payload): array
    {
        $errors = [];

        foreach (['competition_id', 'home_team_id', 'away_team_id'] as $field) {
            if (empty($payload[$field]) || (int)$payload[$field] <= 0) {
                $errors[$field] = 'Campo obbligatorio';
            }
        }

        if (empty($payload['match_day']) || !is_numeric($payload['match_day']) || (int)$payload['match_day'] <= 0) {
            $errors['match_day'] = 'Giornata obbligatoria';
        }

        if (
            isset($payload['difficulty_rating'])
            && $payload['difficulty_rating'] !== ''
            && (
                !is_numeric($payload['difficulty_rating'])
                || (int)$payload['difficulty_rating'] < 1
                || (int)$payload['difficulty_rating'] > 5
            )
        ) {
            $errors['difficulty_rating'] = 'Difficolta partita non valida';
        }

        if (empty($payload['match_date'])) {
            $errors['match_date'] = 'Data partita obbligatoria';
        } elseif (!self::isDate($payload['match_date'])) {
            $errors['match_date'] = 'Data partita non valida';
        }

        if (!empty($payload['match_time']) && !self::isTime($payload['match_time'])) {
            $errors['match_time'] = 'Ora partita non valida';
        }

        if (
            !empty($payload['home_team_id'])
            && !empty($payload['away_team_id'])
            && (int)$payload['home_team_id'] === (int)$payload['away_team_id']
        ) {
            $errors['away_team_id'] = 'Le squadre devono essere diverse';
        }

        if (!empty($payload['referee_id']) && (int)$payload['referee_id'] <= 0) {
            $errors['referee_id'] = 'Arbitro non valido';
        }

        if (!empty($payload['field_id']) && (int)$payload['field_id'] <= 0) {
            $errors['field_id'] = 'Stadio non valido';
        }

        if (!empty($payload['status']) && !in_array($payload['status'], ['scheduled', 'played', 'cancelled'], true)) {
            $errors['status'] = 'Stato partita non valido';
        }

        $resultType = $payload['result_type'] ?? 'played';
        if (!in_array($resultType, ['played', 'walkover_home', 'walkover_away'], true)) {
            $errors['result_type'] = 'Tipo risultato non valido';
        }

        foreach (['home_goals', 'away_goals'] as $field) {
            if (
                isset($payload[$field])
                && $payload[$field] !== ''
                && (!is_numeric($payload[$field]) || (int)$payload[$field] < 0)
            ) {
                $errors[$field] = 'Reti non valide';
            }
        }

        $hasHomeGoals = isset($payload['home_goals']) && $payload['home_goals'] !== '';
        $hasAwayGoals = isset($payload['away_goals']) && $payload['away_goals'] !== '';

        if ($hasHomeGoals xor $hasAwayGoals) {
            $errors['away_goals'] = 'Indica le reti di entrambe le squadre';
        }

        if ($resultType !== 'played') {
            if (!$hasHomeGoals || !$hasAwayGoals) {
                $errors['home_goals'] = 'Indica il risultato a tavolino';
            }

            if (empty($payload['walkover_reason'])) {
                $errors['walkover_reason'] = 'Motivo tavolino obbligatorio';
            }
        }

        return $errors;
    }

    private static function isDate(string $value): bool
    {
        $date = \DateTime::createFromFormat('Y-m-d', $value);

        return $date && $date->format('Y-m-d') === $value;
    }

    private static function isTime(string $value): bool
    {
        if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value)) {
            return false;
        }

        $format = strlen($value) === 5 ? 'H:i' : 'H:i:s';
        $time = \DateTime::createFromFormat($format, $value);

        return $time && $time->format($format) === $value;
    }
}
