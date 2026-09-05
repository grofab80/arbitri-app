<?php
namespace Api\Models;

use Api\Core\Database;
use PDO;

class Category {

    public static function roots(): array
    {
        $stmt = Database::get()->prepare(
            "SELECT id, name FROM categories WHERE level = 1 ORDER BY name"
        );
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function children(int $parentId): array
    {
        $stmt = Database::get()->prepare(
            "SELECT id, name FROM categories WHERE parent_id = ? ORDER BY name"
        );
        $stmt->execute([$parentId]);

        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::get()->prepare("
            SELECT id, name, parent_id,
                allow_competition,
                allow_team,
                allow_referee
            FROM categories
            WHERE id = ?
        ");
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    public static function tree(): array
    {
        $stmt = Database::get()->prepare("
            SELECT
                id,
                name,
                parent_id,
                allow_competition,
                allow_team,
                allow_referee
            FROM categories
            ORDER BY parent_id, name
        ");
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function isLevel4(int $id): bool
    {
        $stmt = Database::get()->prepare(
            "SELECT 1 FROM categories WHERE id = ? AND level = 4"
        );
        $stmt->execute([$id]);

        return (bool)$stmt->fetch();
    }

    public static function rules(int $id): ?array
    {
        $stmt = Database::get()->prepare("
            SELECT allow_competition, allow_team, allow_referee
            FROM categories
            WHERE id = ?
        ");
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }
}
