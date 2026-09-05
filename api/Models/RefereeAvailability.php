<?php
namespace Api\Models;

use Api\Core\Database;

class RefereeAvailability {

    public static function all(?int $refereeId = null): array
    {
        $where = '';
        $params = [];

        if ($refereeId !== null) {
            $where = 'WHERE a.referee_id = ?';
            $params[] = $refereeId;
        }

        $stmt = Database::get()->prepare(
            "
            SELECT
                a.id,
                a.referee_id,
                r.name AS referee_name,
                a.type,
                a.weekday,
                a.available_date,
                a.start_time,
                a.end_time,
                a.is_available,
                a.notes,
                a.created_at,
                a.updated_at
            FROM referee_availabilities a
            JOIN referees r ON r.id = a.referee_id
            $where
            ORDER BY r.name, a.type, a.available_date, a.weekday, a.start_time
            "
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function forReferee(int $refereeId): array
    {
        return self::all($refereeId);
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::get()->prepare(
            "
            SELECT
                a.*,
                r.name AS referee_name
            FROM referee_availabilities a
            JOIN referees r ON r.id = a.referee_id
            WHERE a.id = ?
            "
        );
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    public static function forSlot(int $refereeId, string $date, string $time): array
    {
        $weekday = (int)(new \DateTimeImmutable($date))->format('N');

        $stmt = Database::get()->prepare(
            "
            SELECT *
            FROM referee_availabilities
            WHERE referee_id = ?
              AND (
                  (type = 'specific' AND available_date = ?)
                  OR
                  (type = 'recurring' AND weekday = ?)
              )
              AND start_time <= ?
              AND end_time >= ?
            ORDER BY
                CASE WHEN type = 'specific' THEN 0 ELSE 1 END,
                is_available ASC,
                start_time
            "
        );
        $stmt->execute([
            $refereeId,
            $date,
            $weekday,
            self::normalizeTime($time),
            self::normalizeTime($time)
        ]);

        return $stmt->fetchAll();
    }

    public static function isAvailableForSlot(int $refereeId, string $date, string $time): bool
    {
        $time = self::normalizeTime($time);

        $specificRows = self::matchingRows($refereeId, 'specific', $date, null, $time);

        if (!empty($specificRows)) {
            return self::hasAvailableRow($specificRows);
        }

        $weekday = (int)(new \DateTimeImmutable($date))->format('N');
        $recurringRows = self::matchingRows($refereeId, 'recurring', null, $weekday, $time);

        return self::hasAvailableRow($recurringRows);
    }

    public static function create(array $data): int
    {
        $stmt = Database::get()->prepare(
            "
            INSERT INTO referee_availabilities
                (referee_id, type, weekday, available_date, start_time, end_time, is_available, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            "
        );

        $stmt->execute(self::params($data));

        return (int)Database::get()->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $stmt = Database::get()->prepare(
            "
            UPDATE referee_availabilities SET
                referee_id = ?,
                type = ?,
                weekday = ?,
                available_date = ?,
                start_time = ?,
                end_time = ?,
                is_available = ?,
                notes = ?
            WHERE id = ?
            "
        );

        return $stmt->execute([...self::params($data), $id]);
    }

    public static function delete(int $id): bool
    {
        $stmt = Database::get()->prepare(
            "DELETE FROM referee_availabilities WHERE id = ?"
        );

        return $stmt->execute([$id]);
    }

    private static function params(array $data): array
    {
        $type = $data['type'] ?? 'recurring';

        return [
            (int)$data['referee_id'],
            $type,
            $type === 'recurring' ? (int)$data['weekday'] : null,
            $type === 'specific' ? $data['available_date'] : null,
            self::normalizeTime($data['start_time']),
            self::normalizeTime($data['end_time']),
            (int)((int)($data['is_available'] ?? 1) === 1),
            self::nullableString($data['notes'] ?? null)
        ];
    }

    private static function matchingRows(
        int $refereeId,
        string $type,
        ?string $date,
        ?int $weekday,
        string $time
    ): array {
        $where = $type === 'specific'
            ? 'available_date = ?'
            : 'weekday = ?';

        $value = $type === 'specific' ? $date : $weekday;

        $stmt = Database::get()->prepare(
            "
            SELECT *
            FROM referee_availabilities
            WHERE referee_id = ?
              AND type = ?
              AND $where
              AND start_time <= ?
              AND end_time >= ?
            ORDER BY is_available ASC, start_time
            "
        );
        $stmt->execute([
            $refereeId,
            $type,
            $value,
            $time,
            $time
        ]);

        return $stmt->fetchAll();
    }

    private static function hasAvailableRow(array $rows): bool
    {
        foreach ($rows as $row) {
            if ((int)($row['is_available'] ?? 0) === 1) {
                return true;
            }
        }

        return false;
    }

    private static function normalizeTime(string $value): string
    {
        return strlen($value) === 5 ? $value . ':00' : $value;
    }

    private static function nullableString($value): ?string
    {
        $value = trim((string)($value ?? ''));

        return $value === '' ? null : $value;
    }
}
