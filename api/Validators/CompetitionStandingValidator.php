<?php
namespace Api\Validators;

use Api\Models\CompetitionStanding;

class CompetitionStandingValidator {

    public static function validate(int $competitionId, array $payload): array
    {
        $errors = [];

        if (!isset($payload['standings']) || !is_array($payload['standings'])) {
            $errors['standings'] = 'Classifica obbligatoria';
            return $errors;
        }

        $allowedTeamIds = CompetitionStanding::teamIdsForCompetition($competitionId);
        $seenTeamIds = [];

        foreach ($payload['standings'] as $index => $row) {
            if (!is_array($row)) {
                $errors["standings.$index"] = 'Riga classifica non valida';
                continue;
            }

            $teamId = (int)($row['team_id'] ?? 0);

            if ($teamId <= 0 || !in_array($teamId, $allowedTeamIds, true)) {
                $errors["standings.$index.team_id"] = 'Squadra non valida per questa competizione';
                continue;
            }

            if (in_array($teamId, $seenTeamIds, true)) {
                $errors["standings.$index.team_id"] = 'Squadra duplicata';
                continue;
            }

            $seenTeamIds[] = $teamId;

            foreach (['played', 'won', 'drawn', 'lost', 'goals_for', 'goals_against'] as $field) {
                if (!self::isNonNegativeInteger($row[$field] ?? 0)) {
                    $errors["standings.$index.$field"] = 'Valore numerico non valido';
                }
            }

            if (isset($row['rank_position']) && $row['rank_position'] !== '' && !self::isPositiveInteger($row['rank_position'])) {
                $errors["standings.$index.rank_position"] = 'Posizione non valida';
            }

            if (!isset($row['points']) || !is_numeric($row['points']) || (int)$row['points'] != $row['points']) {
                $errors["standings.$index.points"] = 'Punti non validi';
            }

            if (isset($row['penalty_points']) && (!is_numeric($row['penalty_points']) || (int)$row['penalty_points'] != $row['penalty_points'])) {
                $errors["standings.$index.penalty_points"] = 'Penalizzazione non valida';
            }

            if (isset($row['notes']) && strlen((string)$row['notes']) > 255) {
                $errors["standings.$index.notes"] = 'Note troppo lunghe';
            }
        }

        return $errors;
    }

    private static function isNonNegativeInteger($value): bool
    {
        return is_numeric($value) && (int)$value == $value && (int)$value >= 0;
    }

    private static function isPositiveInteger($value): bool
    {
        return is_numeric($value) && (int)$value == $value && (int)$value > 0;
    }
}
