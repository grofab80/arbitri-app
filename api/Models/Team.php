<?php
namespace Api\Models;

use Api\Core\Database;

class Team {

    public static function all(): array
    {
        $stmt = Database::get()->prepare(
            "
            SELECT
                t.id,
                t.name,
                t.field_id,
                f.name AS field_name,
                t.created_at,
                GROUP_CONCAT(c.id ORDER BY c.name SEPARATOR ',') AS competition_ids,
                GROUP_CONCAT(c.name ORDER BY c.name SEPARATOR ', ') AS competitions
            FROM teams t
            LEFT JOIN fields f ON f.id = t.field_id
            LEFT JOIN competition_teams ct ON ct.team_id = t.id
            LEFT JOIN competitions c ON c.id = ct.competition_id
            GROUP BY t.id, t.name, t.field_id, f.name, t.created_at
            ORDER BY t.name
            "
        );
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function byCompetition(int $competitionId): array
    {
        $stmt = Database::get()->prepare(
            "SELECT t.id, t.name
             FROM teams t
             JOIN competition_teams ct ON ct.team_id = t.id
             WHERE ct.competition_id = ?
             ORDER BY t.name"
        );
        $stmt->execute([$competitionId]);

        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::get()->prepare(
            "SELECT * FROM teams WHERE id = ?"
        );
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::get();
        $competitionIds = self::competitionIds($data['competition_ids'] ?? []);
        $legacyCompetitionId = $competitionIds[0];

        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO teams (name, field_id, competition_id) VALUES (?, ?, ?)"
            );
            $stmt->execute([
                trim($data['name']),
                self::nullableId($data['field_id'] ?? null),
                $legacyCompetitionId
            ]);

            $id = (int)$pdo->lastInsertId();
            self::syncCompetitions($id, $competitionIds);

            $pdo->commit();

            return $id;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function update(int $id, array $data): bool
    {
        $pdo = Database::get();
        $competitionIds = self::competitionIds($data['competition_ids'] ?? []);
        $legacyCompetitionId = $competitionIds[0];

        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                "UPDATE teams SET name = ?, field_id = ?, competition_id = ? WHERE id = ?"
            );
            $stmt->execute([
                trim($data['name']),
                self::nullableId($data['field_id'] ?? null),
                $legacyCompetitionId,
                $id
            ]);

            self::syncCompetitions($id, $competitionIds);

            $pdo->commit();

            return true;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function delete(int $id): bool
    {
        $pdo = Database::get();

        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                "DELETE FROM competition_teams WHERE team_id = ?"
            );
            $stmt->execute([$id]);

            $stmt = $pdo->prepare(
                "DELETE FROM teams WHERE id = ?"
            );
            $stmt->execute([$id]);

            $pdo->commit();

            return true;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function usageCount(int $id): int
    {
        $stmt = Database::get()->prepare(
            "
            SELECT
                (
                    SELECT COUNT(*) FROM movements WHERE team_id = ?
                ) + (
                    SELECT COUNT(*) FROM matches WHERE home_team_id = ? OR away_team_id = ?
                ) AS total
            "
        );
        $stmt->execute([$id, $id, $id]);

        return (int)$stmt->fetchColumn();
    }

    private static function competitionIds(array $values): array
    {
        return array_values(array_unique(array_map('intval', $values)));
    }

    private static function nullableId($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int)$value;
    }

    private static function syncCompetitions(int $teamId, array $competitionIds): void
    {
        $pdo = Database::get();

        $stmt = $pdo->prepare(
            "DELETE FROM competition_teams WHERE team_id = ?"
        );
        $stmt->execute([$teamId]);

        $stmt = $pdo->prepare(
            "INSERT INTO competition_teams (competition_id, team_id) VALUES (?, ?)"
        );

        foreach ($competitionIds as $competitionId) {
            $stmt->execute([$competitionId, $teamId]);
        }
    }
}
