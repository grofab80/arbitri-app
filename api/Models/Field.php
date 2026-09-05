<?php
namespace Api\Models;

use Api\Core\Database;

class Field {

    public static function all(): array
    {
        $stmt = Database::get()->prepare(
            "
            SELECT
                id,
                name,
                address,
                city,
                province,
                postal_code,
                country,
                latitude,
                longitude,
                geocoded_at,
                can_host_11,
                can_host_7,
                can_host_5,
                is_active,
                notes,
                created_at
            FROM fields
            ORDER BY name
            "
        );
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function active(): array
    {
        $stmt = Database::get()->prepare(
            "
            SELECT id, name, address, city, province, postal_code, country, latitude, longitude, geocoded_at, can_host_11, can_host_7, can_host_5
            FROM fields
            WHERE is_active = 1
            ORDER BY name
            "
        );
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::get()->prepare(
            "SELECT * FROM fields WHERE id = ?"
        );
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = Database::get()->prepare(
            "
            INSERT INTO fields
                (
                    name,
                    address,
                    city,
                    province,
                    postal_code,
                    country,
                    can_host_11,
                    can_host_7,
                    can_host_5,
                    is_active,
                    notes
                )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            "
        );

        $stmt->execute([
            trim($data['name']),
            self::nullableString($data['address'] ?? null),
            self::nullableString($data['city'] ?? null),
            self::nullableString($data['province'] ?? null),
            self::nullableString($data['postal_code'] ?? null),
            self::nullableString($data['country'] ?? 'Italia') ?? 'Italia',
            self::boolValue($data['can_host_11'] ?? 0),
            self::boolValue($data['can_host_7'] ?? 0),
            self::boolValue($data['can_host_5'] ?? 0),
            self::boolValue($data['is_active'] ?? 1),
            self::nullableString($data['notes'] ?? null)
        ]);

        return (int)Database::get()->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $stmt = Database::get()->prepare(
            "
            UPDATE fields SET
                name = ?,
                address = ?,
                city = ?,
                province = ?,
                postal_code = ?,
                country = ?,
                can_host_11 = ?,
                can_host_7 = ?,
                can_host_5 = ?,
                is_active = ?,
                notes = ?
            WHERE id = ?
            "
        );

        return $stmt->execute([
            trim($data['name']),
            self::nullableString($data['address'] ?? null),
            self::nullableString($data['city'] ?? null),
            self::nullableString($data['province'] ?? null),
            self::nullableString($data['postal_code'] ?? null),
            self::nullableString($data['country'] ?? 'Italia') ?? 'Italia',
            self::boolValue($data['can_host_11'] ?? 0),
            self::boolValue($data['can_host_7'] ?? 0),
            self::boolValue($data['can_host_5'] ?? 0),
            self::boolValue($data['is_active'] ?? 1),
            self::nullableString($data['notes'] ?? null),
            $id
        ]);
    }

    public static function delete(int $id): bool
    {
        $stmt = Database::get()->prepare(
            "DELETE FROM fields WHERE id = ?"
        );

        return $stmt->execute([$id]);
    }

    public static function usageCount(int $id): int
    {
        $stmt = Database::get()->prepare(
            "
            SELECT
                (
                    SELECT COUNT(*) FROM matches WHERE field_id = ?
                ) + (
                    SELECT COUNT(*) FROM teams WHERE field_id = ?
                ) AS total
            "
        );
        $stmt->execute([$id, $id]);

        return (int)$stmt->fetchColumn();
    }

    public static function updateGeocode(int $id, float $latitude, float $longitude): bool
    {
        $stmt = Database::get()->prepare(
            "
            UPDATE fields SET
                latitude = ?,
                longitude = ?,
                geocoded_at = CURRENT_TIMESTAMP
            WHERE id = ?
            "
        );

        return $stmt->execute([
            $latitude,
            $longitude,
            $id
        ]);
    }

    private static function nullableString($value): ?string
    {
        $value = trim((string)($value ?? ''));

        return $value === '' ? null : $value;
    }

    private static function boolValue($value): int
    {
        return (int)((int)$value === 1);
    }
}
