<?php
namespace Api\Validators;

class RefereeAvailabilityValidator {

    public const TYPES = ['recurring', 'specific'];

    public static function validate(array $payload): array
    {
        $errors = [];
        $type = $payload['type'] ?? '';

        if (empty($payload['referee_id']) || (int)$payload['referee_id'] <= 0) {
            $errors['referee_id'] = 'Arbitro obbligatorio';
        }

        if (!in_array($type, self::TYPES, true)) {
            $errors['type'] = 'Tipo disponibilita non valido';
        }

        if ($type === 'recurring') {
            if (empty($payload['weekday']) || (int)$payload['weekday'] < 1 || (int)$payload['weekday'] > 7) {
                $errors['weekday'] = 'Giorno settimana non valido';
            }

            if (!empty($payload['available_date'])) {
                $errors['available_date'] = 'Data non consentita per disponibilita ricorrente';
            }
        }

        if ($type === 'specific') {
            if (empty($payload['available_date'])) {
                $errors['available_date'] = 'Data disponibilita obbligatoria';
            } elseif (!self::isDate($payload['available_date'])) {
                $errors['available_date'] = 'Data disponibilita non valida';
            }

            if (!empty($payload['weekday'])) {
                $errors['weekday'] = 'Giorno settimana non consentito per disponibilita puntuale';
            }
        }

        foreach (['start_time', 'end_time'] as $field) {
            if (empty($payload[$field])) {
                $errors[$field] = 'Ora obbligatoria';
            } elseif (!self::isTime($payload[$field])) {
                $errors[$field] = 'Ora non valida';
            }
        }

        if (
            empty($errors['start_time'])
            && empty($errors['end_time'])
            && self::timeToSeconds($payload['start_time']) >= self::timeToSeconds($payload['end_time'])
        ) {
            $errors['end_time'] = 'Ora fine deve essere successiva a ora inizio';
        }

        if (isset($payload['is_available']) && !in_array((int)$payload['is_available'], [0, 1], true)) {
            $errors['is_available'] = 'Disponibilita non valida';
        }

        if (isset($payload['notes']) && mb_strlen(trim((string)$payload['notes'])) > 255) {
            $errors['notes'] = 'Note troppo lunghe';
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

    private static function timeToSeconds(string $value): int
    {
        $parts = array_map('intval', explode(':', $value));

        return ($parts[0] * 3600) + ($parts[1] * 60) + ($parts[2] ?? 0);
    }
}
