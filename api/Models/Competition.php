<?php
namespace Api\Models;

use Api\Core\Database;

class Competition {

    public static function all(): array
    {
        $stmt = Database::get()->prepare(
            "
            SELECT
                c.id,
                c.season_id,
                c.name,
                c.type,
                c.football_type,
                c.season,
                s.name AS season_name,
                COUNT(ct.team_id) AS teams_count,
                c.created_at
            FROM competitions c
            LEFT JOIN seasons s ON s.id = c.season_id
            LEFT JOIN competition_teams ct ON ct.competition_id = c.id
            GROUP BY c.id, c.season_id, c.name, c.type, c.football_type, c.season, s.name, c.created_at, s.starts_on
            ORDER BY s.starts_on DESC, c.name
            "
        );
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::get()->prepare(
            "SELECT * FROM competitions WHERE id = ?"
        );
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    public static function existsByNameAndSeason(string $name, int $seasonId, ?int $excludeId = null): bool
    {
        $sql = "SELECT 1 FROM competitions WHERE name = ? AND season_id = ?";
        $params = [trim($name), $seasonId];

        if ($excludeId) {
            $sql .= " AND id <> ?";
            $params[] = $excludeId;
        }

        $stmt = Database::get()->prepare($sql);
        $stmt->execute($params);

        return (bool)$stmt->fetch();
    }

    public static function create(array $data, string $seasonName): int
    {
        $stmt = Database::get()->prepare(
            "
            INSERT INTO competitions
                (season_id, name, type, football_type, season)
            VALUES (?, ?, ?, ?, ?)
            "
        );

        $stmt->execute([
            (int)$data['season_id'],
            trim($data['name']),
            $data['type'],
            (string)$data['football_type'],
            $seasonName
        ]);

        return (int)Database::get()->lastInsertId();
    }

    public static function update(int $id, array $data, string $seasonName): bool
    {
        $stmt = Database::get()->prepare(
            "
            UPDATE competitions SET
                season_id = ?,
                name = ?,
                type = ?,
                football_type = ?,
                season = ?
            WHERE id = ?
            "
        );

        return $stmt->execute([
            (int)$data['season_id'],
            trim($data['name']),
            $data['type'],
            (string)$data['football_type'],
            $seasonName,
            $id
        ]);
    }

    public static function delete(int $id): bool
    {
        $stmt = Database::get()->prepare(
            "DELETE FROM competitions WHERE id = ?"
        );

        return $stmt->execute([$id]);
    }

    public static function usageCount(int $id): int
    {
        $stmt = Database::get()->prepare(
            "
            SELECT
                (
                    SELECT COUNT(*) FROM movements WHERE competition_id = ?
                ) + (
                    SELECT COUNT(*) FROM matches WHERE competition_id = ?
                ) + (
                    SELECT COUNT(*) FROM competition_teams WHERE competition_id = ?
                ) AS total
            "
        );
        $stmt->execute([$id, $id, $id]);

        return (int)$stmt->fetchColumn();
    }

    public static function matchCount(int $id): int
    {
        $stmt = Database::get()->prepare(
            "SELECT COUNT(*) FROM matches WHERE competition_id = ?"
        );
        $stmt->execute([$id]);

        return (int)$stmt->fetchColumn();
    }
}
