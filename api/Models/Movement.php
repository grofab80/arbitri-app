<?php
namespace Api\Models;

use Api\Core\Database;

class Movement {

    public static function all(array $filters = []) {
        $seasonId = Season::currentId();

        $sql = "SELECT
                    m.*,
                    c1.name AS type_name,
                    c2.name AS category_name,
                    c3.name AS subcategory_name,
                    c4.name AS detail_name
                FROM movements m
                JOIN categories c4 ON c4.id = m.category_id
                LEFT JOIN categories c3 ON c3.id = c4.parent_id
                LEFT JOIN categories c2 ON c2.id = c3.parent_id
                LEFT JOIN categories c1 ON c1.id = c2.parent_id
                WHERE m.season_id = ?";

        $params = [$seasonId];

        if (!empty($filters['from'])) {
            $sql .= " AND m.movement_date >= ?";
            $params[] = $filters['from'];
        }

        if (!empty($filters['to'])) {
            $sql .= " AND m.movement_date <= ?";
            $params[] = $filters['to'];
        }

        if (!empty($filters['competition_id'])) {
            $sql .= " AND m.competition_id = ?";
            $params[] = $filters['competition_id'];
        }

        $sql .= " ORDER BY m.movement_date DESC";

        $stmt = Database::get()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function kpi(): array
    {
        $seasonId = Season::currentId();

        $stmt = Database::get()->prepare("
            SELECT
                COALESCE(SUM(CASE WHEN c1.name = 'Entrate' THEN m.amount ELSE 0 END), 0) AS income,
                COALESCE(SUM(CASE WHEN c1.name = 'Uscite' THEN m.amount ELSE 0 END), 0) AS expenses,
                COUNT(*) AS total_movements
            FROM movements m
            JOIN categories c4 ON c4.id = m.category_id
            LEFT JOIN categories c3 ON c3.id = c4.parent_id
            LEFT JOIN categories c2 ON c2.id = c3.parent_id
            LEFT JOIN categories c1 ON c1.id = c2.parent_id
            WHERE m.season_id = ?
        ");
        $stmt->execute([$seasonId]);

        $kpi = $stmt->fetch() ?: [
            'income' => 0,
            'expenses' => 0,
            'total_movements' => 0
        ];

        $income = (float)$kpi['income'];
        $expenses = (float)$kpi['expenses'];

        return [
            'income' => $income,
            'expenses' => $expenses,
            'profit' => $income - $expenses,
            'total_movements' => (int)$kpi['total_movements']
        ];
    }

    public static function create(array $data) {
        $seasonId = Season::currentId();

        $stmt = Database::get()->prepare("
            INSERT INTO movements
            (season_id, category_id, amount, movement_date, description, competition_id, team_id, referee_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $seasonId,
            $data['category_id'],
            $data['amount'],
            $data['movement_date'],
            $data['description'] ?? null,
            $data['competition_id'] ?? null,
            $data['team_id'] ?? null,
            $data['referee_id'] ?? null
        ]);

        return Database::get()->lastInsertId();
    }

    public static function update(int $id, array $data) {
        $stmt = Database::get()->prepare("
            UPDATE movements SET
              category_id = ?, amount = ?, movement_date = ?, description = ?,
              competition_id = ?, team_id = ?, referee_id = ?
            WHERE id = ?
        ");

        return $stmt->execute([
            $data['category_id'],
            $data['amount'],
            $data['movement_date'],
            $data['description'] ?? null,
            $data['competition_id'] ?? null,
            $data['team_id'] ?? null,
            $data['referee_id'] ?? null,
            $id
        ]);
    }

    public static function delete(int $id) {
        $stmt = Database::get()->prepare(
            "DELETE FROM movements WHERE id = ?"
        );
        return $stmt->execute([$id]);
    }


    public static function find(int $id): ?array
    {
        $stmt = Database::get()->prepare(
            "SELECT * FROM movements WHERE id = ?"
        );
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

}
