<?php
namespace Api\Models;

use Api\Core\Database;
use PDOException;

class User {

    public static function all(): array
    {
        $stmt = Database::get()->prepare(
            "SELECT
                u.id,
                u.username,
                u.profile_id,
                p.code AS profile_code,
                p.name AS profile_name,
                u.first_name,
                u.last_name,
                u.email,
                u.created_at
             FROM users u
             LEFT JOIN profiles p ON p.id = u.profile_id
             ORDER BY u.username"
        );
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function findByUsername(string $username): ?array
    {
        $stmt = Database::get()->prepare(
            "SELECT
                u.*,
                p.code AS profile_code,
                p.name AS profile_name
             FROM users u
             LEFT JOIN profiles p ON p.id = u.profile_id
             WHERE u.username = ?"
        );
        $stmt->execute([$username]);

        return $stmt->fetch() ?: null;
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::get()->prepare(
            "SELECT
                u.*,
                p.code AS profile_code,
                p.name AS profile_name
             FROM users u
             LEFT JOIN profiles p ON p.id = u.profile_id
             WHERE u.id = ?"
        );
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    public static function usernameExists(string $username, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM users WHERE username = ?";
        $params = [$username];

        if ($excludeId) {
            $sql .= " AND id <> ?";
            $params[] = $excludeId;
        }

        $stmt = Database::get()->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn() > 0;
    }

    public static function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM users WHERE email = ?";
        $params = [$email];

        if ($excludeId) {
            $sql .= " AND id <> ?";
            $params[] = $excludeId;
        }

        $stmt = Database::get()->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn() > 0;
    }

    public static function permissions(int $userId): array
    {
        $user = self::findById($userId);

        if (!$user) {
            return [];
        }

        try {
            if (($user['profile_code'] ?? '') === 'admin') {
                $stmt = Database::get()->query(
                    "SELECT code
                     FROM permissions
                     WHERE active = 1
                     ORDER BY code"
                );

                return array_column($stmt->fetchAll(), 'code');
            }

            $stmt = Database::get()->prepare(
                "SELECT pe.code
                 FROM users u
                 INNER JOIN profile_permissions pp ON pp.profile_id = u.profile_id
                 INNER JOIN permissions pe ON pe.id = pp.permission_id
                 WHERE u.id = ?
                   AND pe.active = 1
                 ORDER BY pe.code"
            );
            $stmt->execute([$userId]);

            return array_column($stmt->fetchAll(), 'code');
        } catch (PDOException $e) {
            return [];
        }
    }

    public static function hasPermissions(int $userId, array $requiredPermissions): bool
    {
        if (empty($requiredPermissions)) {
            return true;
        }

        $user = self::findById($userId);

        if (!$user) {
            return false;
        }

        if (($user['profile_code'] ?? '') === 'admin') {
            return true;
        }

        $permissions = self::permissions($userId);

        return empty(array_diff($requiredPermissions, $permissions));
    }

    public static function updateProfile(int $id, int $profileId): bool
    {
        $stmt = Database::get()->prepare(
            "UPDATE users
             SET profile_id = ?
             WHERE id = ?"
        );

        return $stmt->execute([$profileId, $id]);
    }

    public static function create(array $data): int
    {
        $stmt = Database::get()->prepare(
            "INSERT INTO users
                (username, password, first_name, last_name, email, profile_id)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            trim($data['username']),
            password_hash((string)$data['password'], PASSWORD_DEFAULT),
            self::nullableString($data['first_name'] ?? null),
            self::nullableString($data['last_name'] ?? null),
            self::nullableString($data['email'] ?? null),
            (int)$data['profile_id']
        ]);

        return (int)Database::get()->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $password = trim((string)($data['password'] ?? ''));

        if ($password !== '') {
            $stmt = Database::get()->prepare(
                "UPDATE users SET
                    username = ?,
                    password = ?,
                    first_name = ?,
                    last_name = ?,
                    email = ?,
                    profile_id = ?
                 WHERE id = ?"
            );

            return $stmt->execute([
                trim($data['username']),
                password_hash($password, PASSWORD_DEFAULT),
                self::nullableString($data['first_name'] ?? null),
                self::nullableString($data['last_name'] ?? null),
                self::nullableString($data['email'] ?? null),
                (int)$data['profile_id'],
                $id
            ]);
        }

        $stmt = Database::get()->prepare(
            "UPDATE users SET
                username = ?,
                first_name = ?,
                last_name = ?,
                email = ?,
                profile_id = ?
             WHERE id = ?"
        );

        return $stmt->execute([
            trim($data['username']),
            self::nullableString($data['first_name'] ?? null),
            self::nullableString($data['last_name'] ?? null),
            self::nullableString($data['email'] ?? null),
            (int)$data['profile_id'],
            $id
        ]);
    }

    public static function delete(int $id): bool
    {
        $stmt = Database::get()->prepare(
            "DELETE FROM users WHERE id = ?"
        );

        return $stmt->execute([$id]);
    }

    public static function adminCount(): int
    {
        $stmt = Database::get()->prepare(
            "SELECT COUNT(*)
             FROM users u
             INNER JOIN profiles p ON p.id = u.profile_id
             WHERE p.code = 'admin'"
        );
        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }

    private static function nullableString($value): ?string
    {
        $value = trim((string)($value ?? ''));

        return $value === '' ? null : $value;
    }
}
