<?php
namespace Api\Models;

use Api\Core\Database;

class Referee {

    public static function all(): array
    {
        $stmt = Database::get()->prepare(
            "
            SELECT
                id,
                name,
                rating,
                address,
                city,
                province,
                postal_code,
                country,
                latitude,
                longitude,
                geocoded_at,
                can_referee_11,
                can_referee_7,
                can_referee_5,
                created_at
            FROM referees
            ORDER BY name
            "
        );
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::get()->prepare(
            "SELECT * FROM referees WHERE id = ?"
        );
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = Database::get()->prepare(
            "
            INSERT INTO referees
                (
                    name,
                    rating,
                    address,
                    city,
                    province,
                    postal_code,
                    country,
                    can_referee_11,
                    can_referee_7,
                    can_referee_5
                )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            "
        );

        $stmt->execute([
            trim($data['name']),
            self::ratingValue($data['rating'] ?? 3),
            self::nullableString($data['address'] ?? null),
            self::nullableString($data['city'] ?? null),
            self::nullableString($data['province'] ?? null),
            self::nullableString($data['postal_code'] ?? null),
            self::nullableString($data['country'] ?? 'Italia') ?? 'Italia',
            self::boolValue($data['can_referee_11'] ?? 0),
            self::boolValue($data['can_referee_7'] ?? 0),
            self::boolValue($data['can_referee_5'] ?? 0)
        ]);

        return (int)Database::get()->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $stmt = Database::get()->prepare(
            "
            UPDATE referees SET
                name = ?,
                rating = ?,
                address = ?,
                city = ?,
                province = ?,
                postal_code = ?,
                country = ?,
                can_referee_11 = ?,
                can_referee_7 = ?,
                can_referee_5 = ?
            WHERE id = ?
            "
        );

        return $stmt->execute([
            trim($data['name']),
            self::ratingValue($data['rating'] ?? 3),
            self::nullableString($data['address'] ?? null),
            self::nullableString($data['city'] ?? null),
            self::nullableString($data['province'] ?? null),
            self::nullableString($data['postal_code'] ?? null),
            self::nullableString($data['country'] ?? 'Italia') ?? 'Italia',
            self::boolValue($data['can_referee_11'] ?? 0),
            self::boolValue($data['can_referee_7'] ?? 0),
            self::boolValue($data['can_referee_5'] ?? 0),
            $id
        ]);
    }

    public static function delete(int $id): bool
    {
        $stmt = Database::get()->prepare(
            "DELETE FROM referees WHERE id = ?"
        );

        return $stmt->execute([$id]);
    }

    public static function updateGeocode(int $id, float $latitude, float $longitude): bool
    {
        $stmt = Database::get()->prepare(
            "
            UPDATE referees SET
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

    private static function boolValue($value): int
    {
        return (int)((int)$value === 1);
    }

    private static function ratingValue($value): int
    {
        $rating = (int)$value;

        if ($rating < 1) {
            return 1;
        }

        if ($rating > 5) {
            return 5;
        }

        return $rating;
    }

    private static function nullableString($value): ?string
    {
        $value = trim((string)($value ?? ''));

        return $value === '' ? null : $value;
    }
}
