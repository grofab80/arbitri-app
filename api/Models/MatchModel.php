<?php
namespace Api\Models;

use Api\Core\Database;

class MatchModel {

    public static function all(array $filters = []): array
    {
        $seasonId = Season::currentId();
        $params = [$seasonId];
        $where = ["m.season_id = ?"];

        if (!empty($filters['from'])) {
            $where[] = "m.match_date >= ?";
            $params[] = $filters['from'];
        }

        if (!empty($filters['to'])) {
            $where[] = "m.match_date <= ?";
            $params[] = $filters['to'];
        }

        if (!empty($filters['competition_id'])) {
            $where[] = "m.competition_id = ?";
            $params[] = (int)$filters['competition_id'];
        }

        if (!empty($filters['team_id'])) {
            $where[] = "(m.home_team_id = ? OR m.away_team_id = ?)";
            $params[] = (int)$filters['team_id'];
            $params[] = (int)$filters['team_id'];
        }

        if (!empty($filters['referee_id'])) {
            $where[] = "m.referee_id = ?";
            $params[] = (int)$filters['referee_id'];
        }

        $stmt = Database::get()->prepare("
            SELECT
                m.*,
                c.name AS competition_name,
                ht.name AS home_team_name,
                at.name AS away_team_name,
                r.name AS referee_name,
                f.name AS field_name
            FROM matches m
            JOIN competitions c ON c.id = m.competition_id
            JOIN teams ht ON ht.id = m.home_team_id
            JOIN teams at ON at.id = m.away_team_id
            LEFT JOIN referees r ON r.id = m.referee_id
            LEFT JOIN fields f ON f.id = m.field_id
            WHERE " . implode(" AND ", $where) . "
            ORDER BY m.match_date DESC, m.match_time DESC
        ");
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::get()->prepare(
            "SELECT * FROM matches WHERE id = ?"
        );
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $seasonId = Season::currentId();

        $stmt = Database::get()->prepare("
            INSERT INTO matches
            (
                season_id, competition_id, match_day, difficulty_rating, home_team_id, away_team_id, referee_id, field_id,
                match_date, match_time, home_goals, away_goals, status,
                result_type, walkover_reason, notes
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $seasonId,
            $data['competition_id'],
            (int)$data['match_day'],
            self::difficultyRating($data['difficulty_rating'] ?? null),
            $data['home_team_id'],
            $data['away_team_id'],
            self::nullableId($data['referee_id'] ?? null),
            self::nullableId($data['field_id'] ?? null),
            $data['match_date'],
            $data['match_time'] ?? null,
            self::nullableGoal($data['home_goals'] ?? null),
            self::nullableGoal($data['away_goals'] ?? null),
            $data['status'] ?? 'scheduled',
            $data['result_type'] ?? 'played',
            $data['walkover_reason'] ?? null,
            $data['notes'] ?? null
        ]);

        return (int)Database::get()->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $stmt = Database::get()->prepare("
            UPDATE matches SET
                competition_id = ?,
                match_day = ?,
                difficulty_rating = ?,
                home_team_id = ?,
                away_team_id = ?,
                referee_id = ?,
                field_id = ?,
                match_date = ?,
                match_time = ?,
                home_goals = ?,
                away_goals = ?,
                status = ?,
                result_type = ?,
                walkover_reason = ?,
                notes = ?
            WHERE id = ?
        ");

        return $stmt->execute([
            $data['competition_id'],
            (int)$data['match_day'],
            self::difficultyRating($data['difficulty_rating'] ?? null),
            $data['home_team_id'],
            $data['away_team_id'],
            self::nullableId($data['referee_id'] ?? null),
            self::nullableId($data['field_id'] ?? null),
            $data['match_date'],
            $data['match_time'] ?? null,
            self::nullableGoal($data['home_goals'] ?? null),
            self::nullableGoal($data['away_goals'] ?? null),
            $data['status'] ?? 'scheduled',
            $data['result_type'] ?? 'played',
            $data['walkover_reason'] ?? null,
            $data['notes'] ?? null,
            $id
        ]);
    }

    public static function delete(int $id): bool
    {
        $stmt = Database::get()->prepare(
            "DELETE FROM matches WHERE id = ?"
        );

        return $stmt->execute([$id]);
    }

    private static function nullableGoal($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int)$value;
    }

    private static function difficultyRating($value): int
    {
        if ($value === null || $value === '') {
            return 3;
        }

        return max(1, min(5, (int)$value));
    }

    private static function nullableId($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int)$value;
    }
}
