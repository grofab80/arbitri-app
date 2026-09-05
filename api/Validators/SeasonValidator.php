<?php
namespace Api\Validators;

use Api\Models\Season;

class SeasonValidator {

    public const STATUSES = ['nuovo', 'in_corso', 'chiuso'];

    public static function create(array $payload): array
    {
        $errors = [];

        if (empty($payload['name'])) {
            $errors['name'] = 'Nome stagione obbligatorio';
        } elseif (!preg_match('/^\d{4}\/\d{4}$/', $payload['name'])) {
            $errors['name'] = 'Formato stagione non valido';
        } elseif (Season::existsByName($payload['name'])) {
            $errors['name'] = 'Stagione già presente';
        }

        if (empty($payload['starts_on'])) {
            $errors['starts_on'] = 'Data inizio obbligatoria';
        } elseif (!self::isDate($payload['starts_on'])) {
            $errors['starts_on'] = 'Data inizio non valida';
        }

        if (empty($payload['ends_on'])) {
            $errors['ends_on'] = 'Data fine obbligatoria';
        } elseif (!self::isDate($payload['ends_on'])) {
            $errors['ends_on'] = 'Data fine non valida';
        }

        if (
            empty($errors['starts_on'])
            && empty($errors['ends_on'])
            && strtotime($payload['starts_on']) >= strtotime($payload['ends_on'])
        ) {
            $errors['ends_on'] = 'La data fine deve essere successiva alla data inizio';
        }

        return $errors;
    }

    public static function status(array $payload): array
    {
        $errors = [];

        if (empty($payload['status']) || !in_array($payload['status'], self::STATUSES, true)) {
            $errors['status'] = 'Stato stagione non valido';
        }

        return $errors;
    }

    private static function isDate(string $value): bool
    {
        $date = \DateTime::createFromFormat('Y-m-d', $value);

        return $date && $date->format('Y-m-d') === $value;
    }
}
