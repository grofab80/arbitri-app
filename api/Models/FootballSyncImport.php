<?php
namespace Api\Models;

use Api\Core\Database;

class FootballSyncImport {

    private const TABLES = [
        'seasons' => 'seasons',
        'competitions' => 'competitions',
        'stadiums' => 'fields',
        'teams' => 'teams',
        'referees' => 'referees',
        'matches' => 'matches'
    ];

    public static function transaction(callable $callback)
    {
        $db = Database::get();
        $db->beginTransaction();

        try {
            $result = $callback();
            $db->commit();
            return $result;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function exists(string $entityType, int $localId): bool
    {
        $table = self::TABLES[$entityType] ?? null;
        if (!$table) {
            return false;
        }

        $stmt = Database::get()->prepare("SELECT 1 FROM $table WHERE id = ?");
        $stmt->execute([$localId]);
        return (bool)$stmt->fetchColumn();
    }

    public static function upsertSeason(?int $localId, array $record): array
    {
        $name = self::canonicalSeasonName(self::requiredString($record, 'name'));
        [$startsOn, $endsOn] = self::seasonDates($record, $name);
        $linked = false;

        if (!$localId) {
            $localId = self::uniqueId(
                "SELECT id FROM seasons WHERE name = ?",
                [$name],
                'stagione'
            );
            $linked = (bool)$localId;
        }

        if ($localId) {
            $stmt = Database::get()->prepare(
                "UPDATE seasons SET name = ?, starts_on = ?, ends_on = ? WHERE id = ?"
            );
            $stmt->execute([$name, $startsOn, $endsOn, $localId]);

            return self::result($localId, false, $linked);
        }

        $isCurrentRequested = !empty($record['is_current']) || ($record['status'] ?? '') === 'active';
        $hasCurrent = (bool)Database::get()->query(
            "SELECT 1 FROM seasons WHERE status = 'in_corso' OR is_current = 1 LIMIT 1"
        )->fetchColumn();
        $status = ($record['status'] ?? '') === 'closed' ? 'chiuso' : 'nuovo';
        $isCurrent = 0;

        if ($isCurrentRequested && !$hasCurrent) {
            $status = 'in_corso';
            $isCurrent = 1;
        }

        $stmt = Database::get()->prepare(
            "INSERT INTO seasons (name, starts_on, ends_on, status, is_current)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$name, $startsOn, $endsOn, $status, $isCurrent]);

        return self::result((int)Database::get()->lastInsertId(), true, false);
    }

    public static function upsertCompetition(?int $localId, array $record, int $seasonId, string $seasonName): array
    {
        $name = self::requiredString($record, 'name');
        $typeMap = ['league' => 'campionato', 'tournament' => 'torneo'];
        $type = $typeMap[$record['type'] ?? ''] ?? null;
        $footballType = (string)($record['football_type'] ?? '');

        if (!$type) {
            throw new \RuntimeException('Tipo competizione esterno non valido.');
        }
        if (!in_array($footballType, ['5', '7', '11'], true)) {
            throw new \RuntimeException('Disciplina competizione mancante o non valida.');
        }

        $linked = false;
        if (!$localId) {
            $localId = self::uniqueId(
                "SELECT id FROM competitions WHERE season_id = ? AND name = ?",
                [$seasonId, $name],
                'competizione'
            );
            $linked = (bool)$localId;
        }

        if ($localId) {
            $stmt = Database::get()->prepare(
                "UPDATE competitions SET season_id = ?, name = ?, type = ?, football_type = ?, season = ? WHERE id = ?"
            );
            $stmt->execute([$seasonId, $name, $type, $footballType, $seasonName, $localId]);
            return self::result($localId, false, $linked);
        }

        $stmt = Database::get()->prepare(
            "INSERT INTO competitions (season_id, name, type, football_type, season)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$seasonId, $name, $type, $footballType, $seasonName]);
        return self::result((int)Database::get()->lastInsertId(), true, false);
    }

    public static function upsertField(?int $localId, array $record): array
    {
        $name = self::requiredString($record, 'name');
        $city = self::nullableString($record['city'] ?? null);
        $linked = false;

        if (!$localId) {
            $localId = self::uniqueId(
                "SELECT id FROM fields WHERE name = ? AND COALESCE(city, '') = COALESCE(?, '')",
                [$name, $city],
                'stadio'
            );
            $linked = (bool)$localId;
        }

        $values = [
            $name,
            self::nullableString($record['address'] ?? null),
            $city,
            self::nullableString($record['province'] ?? null),
            self::nullableString($record['postal_code'] ?? null),
            self::nullableString($record['country'] ?? null) ?? 'Italia',
            self::nullableDecimal($record['latitude'] ?? null),
            self::nullableDecimal($record['longitude'] ?? null),
            self::boolValue($record['can_host_11'] ?? true),
            self::boolValue($record['can_host_7'] ?? true),
            self::boolValue($record['can_host_5'] ?? true)
        ];

        if ($localId) {
            $stmt = Database::get()->prepare(
                "UPDATE fields SET
                    name = ?, address = ?, city = ?, province = ?, postal_code = ?, country = ?,
                    latitude = ?, longitude = ?,
                    geocoded_at = CASE WHEN ? IS NOT NULL AND ? IS NOT NULL THEN CURRENT_TIMESTAMP ELSE geocoded_at END,
                    can_host_11 = ?, can_host_7 = ?, can_host_5 = ?, is_active = 1
                 WHERE id = ?"
            );
            $stmt->execute([
                ...array_slice($values, 0, 8),
                $values[6], $values[7],
                ...array_slice($values, 8),
                $localId
            ]);
            return self::result($localId, false, $linked);
        }

        $stmt = Database::get()->prepare(
            "INSERT INTO fields
                (name, address, city, province, postal_code, country, latitude, longitude, geocoded_at,
                 can_host_11, can_host_7, can_host_5, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, CASE WHEN ? IS NOT NULL AND ? IS NOT NULL THEN CURRENT_TIMESTAMP ELSE NULL END, ?, ?, ?, 1)"
        );
        $stmt->execute([
            ...array_slice($values, 0, 8),
            $values[6], $values[7],
            ...array_slice($values, 8)
        ]);
        return self::result((int)Database::get()->lastInsertId(), true, false);
    }

    public static function upsertTeam(?int $localId, array $record, array $competitionIds, ?int $fieldId): array
    {
        $name = self::requiredString($record, 'name');
        $competitionIds = array_values(array_unique(array_map('intval', $competitionIds)));
        $linked = false;
        $created = false;

        if (!$localId) {
            $localId = self::uniqueId("SELECT id FROM teams WHERE name = ?", [$name], 'squadra');
            $linked = (bool)$localId;
        }

        if ($localId) {
            $existingCompetitionIds = self::teamCompetitionIds($localId);
            $competitionIds = array_values(array_unique(array_merge($existingCompetitionIds, $competitionIds)));
        }

        if (!$competitionIds) {
            throw new \RuntimeException('Nessuna competizione locale risolta per la squadra.');
        }

        if ($localId) {
            $stmt = Database::get()->prepare(
                "UPDATE teams SET name = ?, field_id = COALESCE(?, field_id), competition_id = ? WHERE id = ?"
            );
            $stmt->execute([$name, $fieldId, $competitionIds[0], $localId]);
        } else {
            $stmt = Database::get()->prepare(
                "INSERT INTO teams (name, field_id, competition_id) VALUES (?, ?, ?)"
            );
            $stmt->execute([$name, $fieldId, $competitionIds[0]]);
            $localId = (int)Database::get()->lastInsertId();
            $created = true;
        }

        $stmt = Database::get()->prepare(
            "INSERT IGNORE INTO competition_teams (competition_id, team_id) VALUES (?, ?)"
        );
        foreach ($competitionIds as $competitionId) {
            $stmt->execute([$competitionId, $localId]);
        }

        return self::result($localId, $created, $linked);
    }

    public static function upsertReferee(?int $localId, array $record): array
    {
        $name = self::requiredString($record, 'name');
        $linked = false;

        if (!$localId) {
            $localId = self::uniqueId("SELECT id FROM referees WHERE name = ?", [$name], 'arbitro');
            $linked = (bool)$localId;
        }

        if ($localId) {
            $stmt = Database::get()->prepare(
                "UPDATE referees SET
                    name = ?, city = COALESCE(?, city), country = COALESCE(?, country),
                    can_referee_11 = ?, can_referee_7 = ?, can_referee_5 = ?
                 WHERE id = ?"
            );
            $stmt->execute([
                $name,
                self::nullableString($record['city'] ?? null),
                self::nullableString($record['country'] ?? null),
                self::boolValue($record['can_referee_11'] ?? true),
                self::boolValue($record['can_referee_7'] ?? true),
                self::boolValue($record['can_referee_5'] ?? true),
                $localId
            ]);
            return self::result($localId, false, $linked);
        }

        $stmt = Database::get()->prepare(
            "INSERT INTO referees
                (name, rating, city, country, can_referee_11, can_referee_7, can_referee_5)
             VALUES (?, 3, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $name,
            self::nullableString($record['city'] ?? null),
            self::nullableString($record['country'] ?? null) ?? 'Italia',
            self::boolValue($record['can_referee_11'] ?? true),
            self::boolValue($record['can_referee_7'] ?? true),
            self::boolValue($record['can_referee_5'] ?? true)
        ]);
        return self::result((int)Database::get()->lastInsertId(), true, false);
    }

    public static function upsertMatch(?int $localId, array $record, array $relations): array
    {
        $matchDate = self::requiredString($record, 'match_date');
        $competitionId = (int)$relations['competition_id'];
        $seasonId = (int)$relations['season_id'];
        $homeTeamId = (int)$relations['home_team_id'];
        $awayTeamId = (int)$relations['away_team_id'];
        $matchDay = max(1, (int)($record['match_day'] ?? 1));
        $linked = false;

        if ($homeTeamId === $awayTeamId) {
            throw new \RuntimeException('Squadra casa e trasferta coincidono.');
        }

        if (!$localId) {
            $localId = self::uniqueId(
                "SELECT id FROM matches
                 WHERE competition_id = ? AND match_day = ? AND home_team_id = ?
                   AND away_team_id = ? AND match_date = ?",
                [$competitionId, $matchDay, $homeTeamId, $awayTeamId, $matchDate],
                'partita'
            );
            $linked = (bool)$localId;
        }

        $status = in_array(($record['status'] ?? ''), ['scheduled', 'played', 'cancelled'], true)
            ? $record['status']
            : 'scheduled';
        $resultType = in_array(($record['result_type'] ?? ''), ['played', 'walkover_home', 'walkover_away'], true)
            ? $record['result_type']
            : 'played';
        $values = [
            $seasonId,
            $competitionId,
            $matchDay,
            max(1, min(5, (int)($record['difficulty_rating'] ?? 3))),
            $homeTeamId,
            $awayTeamId,
            $relations['referee_id'] ?? null,
            $relations['field_id'] ?? null,
            $matchDate,
            self::normalizeTime($record['match_time'] ?? null),
            self::nullableInt($record['home_goals'] ?? null),
            self::nullableInt($record['away_goals'] ?? null),
            $status,
            $resultType,
            self::nullableString($record['walkover_reason'] ?? null),
            self::nullableString($record['notes'] ?? null)
        ];

        if ($localId) {
            // Difficulty and an existing local referee assignment belong to the
            // operational workflow and are not overwritten by WordPress.
            $stmt = Database::get()->prepare(
                "UPDATE matches SET
                    season_id = ?, competition_id = ?, match_day = ?,
                    difficulty_rating = COALESCE(difficulty_rating, ?),
                    home_team_id = ?, away_team_id = ?,
                    referee_id = COALESCE(referee_id, ?), field_id = ?,
                    match_date = ?, match_time = ?, home_goals = ?, away_goals = ?,
                    status = ?, result_type = ?, walkover_reason = ?, notes = ?
                 WHERE id = ?"
            );
            $stmt->execute([...$values, $localId]);
            return self::result($localId, false, $linked);
        }

        $stmt = Database::get()->prepare(
            "INSERT INTO matches
                (season_id, competition_id, match_day, difficulty_rating, home_team_id, away_team_id,
                 referee_id, field_id, match_date, match_time, home_goals, away_goals, status,
                 result_type, walkover_reason, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute($values);
        return self::result((int)Database::get()->lastInsertId(), true, false);
    }

    public static function seasonName(int $id): string
    {
        $stmt = Database::get()->prepare("SELECT name FROM seasons WHERE id = ?");
        $stmt->execute([$id]);
        $name = $stmt->fetchColumn();
        if (!$name) {
            throw new \RuntimeException('Stagione locale collegata non trovata.');
        }
        return (string)$name;
    }

    public static function competitionSeasonId(int $id): ?int
    {
        $stmt = Database::get()->prepare("SELECT season_id FROM competitions WHERE id = ?");
        $stmt->execute([$id]);
        $seasonId = $stmt->fetchColumn();

        return $seasonId === false ? null : (int)$seasonId;
    }

    private static function uniqueId(string $sql, array $params, string $label): ?int
    {
        $stmt = Database::get()->prepare($sql);
        $stmt->execute($params);
        $ids = array_map('intval', array_column($stmt->fetchAll(), 'id'));

        if (count($ids) > 1) {
            throw new \RuntimeException("Mapping ambiguo: piu record locali corrispondono a $label.");
        }

        return $ids[0] ?? null;
    }

    private static function teamCompetitionIds(int $teamId): array
    {
        $stmt = Database::get()->prepare(
            "SELECT competition_id FROM competition_teams WHERE team_id = ? ORDER BY competition_id"
        );
        $stmt->execute([$teamId]);
        return array_map('intval', array_column($stmt->fetchAll(), 'competition_id'));
    }

    private static function result(int $id, bool $created, bool $linked): array
    {
        return [
            'local_id' => $id,
            'created' => $created,
            'linked' => $linked,
            'message' => $linked ? 'Collegato a record locale esistente.' : null
        ];
    }

    private static function seasonDates(array $record, string $name): array
    {
        $startsOn = self::nullableString($record['starts_on'] ?? null);
        $endsOn = self::nullableString($record['ends_on'] ?? null);

        if ((!$startsOn || !$endsOn) && preg_match('/^(\d{4})\/(\d{4})$/', $name, $matches)) {
            $startsOn = $startsOn ?: $matches[1] . '-07-01';
            $endsOn = $endsOn ?: $matches[2] . '-06-30';
        }

        if (!$startsOn || !$endsOn) {
            throw new \RuntimeException('Date stagione mancanti e non deducibili dal nome.');
        }

        return [$startsOn, $endsOn];
    }

    private static function canonicalSeasonName(string $name): string
    {
        if (preg_match('/^(\d{4})\s*[-\/]\s*(\d{4})$/', trim($name), $matches)) {
            return $matches[1] . '/' . $matches[2];
        }

        return trim($name);
    }

    private static function requiredString(array $record, string $field): string
    {
        $value = trim((string)($record[$field] ?? ''));
        if ($value === '') {
            throw new \RuntimeException("Campo remoto obbligatorio mancante: $field.");
        }
        return $value;
    }

    private static function nullableString($value): ?string
    {
        $value = trim((string)($value ?? ''));
        return $value === '' ? null : $value;
    }

    private static function nullableDecimal($value): ?float
    {
        return $value === null || $value === '' ? null : (float)$value;
    }

    private static function nullableInt($value): ?int
    {
        return $value === null || $value === '' ? null : (int)$value;
    }

    private static function boolValue($value): int
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
    }

    private static function normalizeTime($value): ?string
    {
        $value = self::nullableString($value);
        if (!$value) {
            return null;
        }
        if (preg_match('/^\d{2}:\d{2}$/', $value)) {
            return $value . ':00';
        }
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $value)) {
            return $value;
        }
        throw new \RuntimeException('Orario partita remoto non valido.');
    }
}
