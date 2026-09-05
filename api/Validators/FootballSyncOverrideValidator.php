<?php
namespace Api\Validators;

class FootballSyncOverrideValidator {

    public static function validate(array $payload): array
    {
        $items = $payload['items'] ?? null;
        if (!is_array($items)) {
            return ['items' => 'Elenco correzioni non valido'];
        }
        if (count($items) > 500) {
            return ['items' => 'Puoi salvare al massimo 500 correzioni per volta'];
        }

        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                return ['items' => "Correzione $index non valida"];
            }
            if (!in_array(($item['entity_type'] ?? ''), ['seasons', 'competitions'], true)) {
                return ['items' => "Entita della correzione $index non valida"];
            }
            if (trim((string)($item['external_id'] ?? '')) === '') {
                return ['items' => "ID esterno della correzione $index mancante"];
            }

            $footballType = $item['football_type'] ?? null;
            if ($footballType !== null && $footballType !== '' && !in_array((string)$footballType, ['5', '7', '11'], true)) {
                return ['items' => "Disciplina della correzione $index non valida"];
            }
            if (($item['entity_type'] ?? '') === 'seasons' && !empty($footballType)) {
                return ['items' => "La disciplina non e applicabile alla stagione della correzione $index"];
            }

            $seasonId = $item['season_local_id'] ?? null;
            if ($seasonId !== null && $seasonId !== '' && (!is_numeric($seasonId) || (int)$seasonId <= 0)) {
                return ['items' => "Stagione locale della correzione $index non valida"];
            }
        }

        return [];
    }
}
