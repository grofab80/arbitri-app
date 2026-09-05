<?php
namespace Api\Models;

use Api\Core\Database;

class Permission {

    public static function profiles(): array
    {
        $stmt = Database::get()->prepare(
            "SELECT id, code, name, is_system, created_at
             FROM profiles
             ORDER BY code"
        );
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function profile(int $id): ?array
    {
        $stmt = Database::get()->prepare(
            "SELECT id, code, name, is_system
             FROM profiles
             WHERE id = ?"
        );
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    public static function permissions(): array
    {
        $stmt = Database::get()->prepare(
            "SELECT id, code, description, scope, active, created_at
             FROM permissions
             ORDER BY scope, code"
        );
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function assignments(): array
    {
        $stmt = Database::get()->prepare(
            "SELECT profile_id, permission_id
             FROM profile_permissions
             ORDER BY profile_id, permission_id"
        );
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function permissionIdsExist(array $permissionIds): bool
    {
        $permissionIds = array_values(array_unique(array_map('intval', $permissionIds)));

        if (empty($permissionIds)) {
            return true;
        }

        $placeholders = implode(',', array_fill(0, count($permissionIds), '?'));
        $stmt = Database::get()->prepare(
            "SELECT COUNT(*)
             FROM permissions
             WHERE id IN ($placeholders)"
        );
        $stmt->execute($permissionIds);

        return (int)$stmt->fetchColumn() === count($permissionIds);
    }

    public static function containsDeletePermission(array $permissionIds): bool
    {
        $permissionIds = array_values(array_unique(array_map('intval', $permissionIds)));

        if (empty($permissionIds)) {
            return false;
        }

        $placeholders = implode(',', array_fill(0, count($permissionIds), '?'));
        $stmt = Database::get()->prepare(
            "SELECT COUNT(*)
             FROM permissions
             WHERE id IN ($placeholders)
               AND code LIKE '%.delete'"
        );
        $stmt->execute($permissionIds);

        return (int)$stmt->fetchColumn() > 0;
    }

    public static function syncProfilePermissions(int $profileId, array $permissionIds): bool
    {
        $permissionIds = array_values(array_unique(array_map('intval', $permissionIds)));
        $db = Database::get();

        $db->beginTransaction();

        try {
            $stmt = $db->prepare("DELETE FROM profile_permissions WHERE profile_id = ?");
            $stmt->execute([$profileId]);

            if (!empty($permissionIds)) {
                $stmt = $db->prepare(
                    "INSERT INTO profile_permissions (profile_id, permission_id)
                     VALUES (?, ?)"
                );

                foreach ($permissionIds as $permissionId) {
                    $stmt->execute([$profileId, $permissionId]);
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
