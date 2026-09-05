<?php
namespace Api\Models;

use Api\Core\Database;
use Api\Services\BalanceService;

class Season {

    public static function all(): array
    {
        $stmt = Database::get()->prepare(
            "SELECT id, name, starts_on, ends_on, status, is_current, created_at
             FROM seasons
             ORDER BY starts_on DESC"
        );
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function current(): ?array
    {
        $stmt = Database::get()->prepare(
            "SELECT * FROM seasons WHERE status = 'in_corso' OR is_current = 1 ORDER BY is_current DESC LIMIT 1"
        );
        $stmt->execute();

        return $stmt->fetch() ?: null;
    }

    public static function currentId(): ?int
    {
        $season = self::current();

        return $season ? (int)$season['id'] : null;
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::get()->prepare(
            "SELECT id, name, starts_on, ends_on, status, is_current, created_at
             FROM seasons
             WHERE id = ?"
        );
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    public static function existsByName(string $name): bool
    {
        $stmt = Database::get()->prepare(
            "SELECT 1 FROM seasons WHERE name = ?"
        );
        $stmt->execute([$name]);

        return (bool)$stmt->fetch();
    }

    public static function create(array $data): int
    {
        $stmt = Database::get()->prepare(
            "INSERT INTO seasons (name, starts_on, ends_on, status, is_current)
             VALUES (?, ?, ?, 'nuovo', 0)"
        );
        $stmt->execute([
            $data['name'],
            $data['starts_on'],
            $data['ends_on']
        ]);

        return (int)Database::get()->lastInsertId();
    }

    public static function setCurrent(int $id): bool
    {
        return self::setStatus($id, 'in_corso');
    }

    public static function setStatus(int $id, string $status): bool
    {
        $db = Database::get();

        $db->beginTransaction();

        try {
            if ($status === 'in_corso') {
                $stmt = $db->prepare("SELECT id FROM seasons WHERE (status = 'in_corso' OR is_current = 1) AND id <> ?");
                $stmt->execute([$id]);
                $closedSeasonIds = array_map('intval', array_column($stmt->fetchAll(), 'id'));

                $stmt = $db->prepare("UPDATE seasons SET status = 'chiuso', is_current = 0 WHERE status = 'in_corso' OR is_current = 1");
                $stmt->execute();

                $stmt = $db->prepare("UPDATE seasons SET status = 'in_corso', is_current = 1 WHERE id = ?");
                $stmt->execute([$id]);

                foreach ($closedSeasonIds as $closedSeasonId) {
                    BalanceService::closeSeason($closedSeasonId);
                }
            } else {
                $stmt = $db->prepare("UPDATE seasons SET status = ?, is_current = 0 WHERE id = ?");
                $stmt->execute([$status, $id]);

                if ($status === 'chiuso') {
                    BalanceService::closeSeason($id);
                }
            }

            $db->commit();
            return true;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
