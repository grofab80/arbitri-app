<?php
namespace Api\Validators;

use Api\Models\Category;
use Api\Models\Season;

class MovementValidator {

    public static function validate(array $payload): array
    {
        $errors = [];

        /* -------- CAMPI BASE -------- */

        if (empty($payload['category_id'])) {
            $errors['category_id'] = 'Categoria obbligatoria';
        } elseif (!Category::isLevel4((int)$payload['category_id'])) {
            $errors['category_id'] = 'Categoria non valida';
        }

        $movementDate = isset($payload['movement_date']) && is_scalar($payload['movement_date'])
            ? (string)$payload['movement_date']
            : '';

        if ($movementDate === '') {
            $errors['movement_date'] = 'Data obbligatoria';
        } elseif (!self::isValidDate($movementDate)) {
            $errors['movement_date'] = 'Data non valida';
        } else {
            $season = Season::current();

            if ($season && !self::isDateInSeason($movementDate, $season)) {
                $errors['movement_date'] = 'La data deve rientrare nella stagione corrente';
            }
        }

        if (!isset($payload['amount']) || !is_numeric($payload['amount'])) {
            $errors['amount'] = 'Importo non valido';
        }

        /* -------- REGOLE DINAMICHE -------- */

        if (!empty($payload['category_id'])) {
            $cat = Category::rules((int)$payload['category_id']);

            if ($cat) {

                // OBBLIGATORI
                if ($cat['allow_competition'] && empty($payload['competition_id'])) {
                    $errors['competition_id'] = 'Competizione obbligatoria';
                }

                if ($cat['allow_team'] && empty($payload['team_id'])) {
                    $errors['team_id'] = 'Squadra obbligatoria';
                }

                if ($cat['allow_referee'] && empty($payload['referee_id'])) {
                    $errors['referee_id'] = 'Arbitro obbligatorio';
                }

                // NON CONSENTITI
                if (!$cat['allow_competition'] && !empty($payload['competition_id'])) {
                    $errors['competition_id'] = 'Campo non consentito per questa categoria';
                }

                if (!$cat['allow_team'] && !empty($payload['team_id'])) {
                    $errors['team_id'] = 'Campo non consentito per questa categoria';
                }

                if (!$cat['allow_referee'] && !empty($payload['referee_id'])) {
                    $errors['referee_id'] = 'Campo non consentito per questa categoria';
                }
            }
        }


        return $errors;
    }

    private static function isValidDate(string $date): bool
    {
        $parsed = \DateTime::createFromFormat('Y-m-d', $date);

        return $parsed && $parsed->format('Y-m-d') === $date;
    }

    private static function isDateInSeason(string $date, array $season): bool
    {
        if (!empty($season['starts_on']) && $date < $season['starts_on']) {
            return false;
        }

        if (!empty($season['ends_on']) && $date > $season['ends_on']) {
            return false;
        }

        return true;
    }
}
