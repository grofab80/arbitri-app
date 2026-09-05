<?php
namespace Api\Services;

use Api\Core\Database;
use Api\Models\Season;
use Api\Models\User;

class OperationalNotificationService {

    public static function syncCurrentSeasonSafely(): void
    {
        try {
            $seasonId = Season::currentId();

            if ($seasonId) {
                self::syncForSeason((int)$seasonId);
            }
        } catch (\Throwable $e) {
            error_log(sprintf(
                '[OPERATIONAL NOTIFICATIONS] %s in %s:%d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ));
        }
    }

    public static function forUser(int $userId, int $limit = 10): array
    {
        $limit = max(1, min($limit, 50));
        $notifications = self::visibleNotifications($userId, $limit);

        return [
            'unread_count' => self::unreadCount($userId),
            'notifications' => $notifications
        ];
    }

    public static function markRead(int $userId, int $notificationId): bool
    {
        if ($notificationId <= 0 || !self::canUserSeeNotification($userId, $notificationId)) {
            return false;
        }

        $stmt = Database::get()->prepare("
            INSERT INTO operational_notification_reads
                (notification_id, user_id, read_at)
            VALUES
                (?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                read_at = COALESCE(read_at, VALUES(read_at)),
                dismissed_at = NULL
        ");

        return $stmt->execute([$notificationId, $userId]);
    }

    public static function markAllRead(int $userId): int
    {
        $visible = self::visibleNotifications($userId, 1000, true);
        $updated = 0;

        foreach ($visible as $notification) {
            if (self::markRead($userId, (int)$notification['id'])) {
                $updated++;
            }
        }

        return $updated;
    }

    public static function syncForSeason(int $seasonId): array
    {
        $alerts = OperationalAlertService::forSeason($seasonId, true);
        $created = [];
        $db = Database::get();
        $ownsTransaction = !$db->inTransaction();

        try {
            if ($ownsTransaction) {
                $db->beginTransaction();
            }

            foreach ($alerts as $alert) {
                $notification = self::syncAlert($alert);

                if ($notification !== null) {
                    $created[] = $notification;
                }
            }

            if ($ownsTransaction) {
                $db->commit();
            }
        } catch (\Throwable $e) {
            if ($ownsTransaction && $db->inTransaction()) {
                $db->rollBack();
            }

            throw $e;
        }

        return [
            'checked_alerts' => count($alerts),
            'created_notifications' => count($created),
            'notifications' => $created
        ];
    }

    private static function syncAlert(array $alert): ?array
    {
        $db = Database::get();
        $code = (string)$alert['code'];
        $currentCount = (int)$alert['count'];
        $previousCount = self::snapshotCountForUpdate($code);
        $delta = $previousCount === null ? 0 : $currentCount - $previousCount;
        $notification = null;

        if ($delta > 0) {
            $notification = self::createNotification($alert, $delta, $currentCount);
        }

        $stmt = $db->prepare("
            INSERT INTO operational_alert_snapshots
                (alert_code, count_value, last_checked_at)
            VALUES
                (?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                count_value = VALUES(count_value),
                last_checked_at = VALUES(last_checked_at)
        ");
        $stmt->execute([$code, $currentCount]);

        return $notification;
    }

    private static function snapshotCountForUpdate(string $alertCode): ?int
    {
        $stmt = Database::get()->prepare("
            SELECT count_value
            FROM operational_alert_snapshots
            WHERE alert_code = ?
            FOR UPDATE
        ");
        $stmt->execute([$alertCode]);

        $value = $stmt->fetchColumn();

        return $value === false ? null : (int)$value;
    }

    private static function createNotification(array $alert, int $delta, int $currentCount): array
    {
        $permissions = array_values($alert['required_permissions'] ?? []);
        $permissionsJson = json_encode($permissions, JSON_UNESCAPED_UNICODE);
        $title = self::title($alert, $delta);
        $message = self::message($alert, $delta, $currentCount);

        $stmt = Database::get()->prepare("
            INSERT INTO operational_notifications
                (
                    alert_code,
                    title,
                    message,
                    delta,
                    count_value,
                    href,
                    required_permissions_json
                )
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $alert['code'],
            $title,
            $message,
            $delta,
            $currentCount,
            $alert['href'] ?? null,
            $permissionsJson
        ]);

        return [
            'id' => (int)Database::get()->lastInsertId(),
            'alert_code' => $alert['code'],
            'title' => $title,
            'message' => $message,
            'delta' => $delta,
            'count_value' => $currentCount,
            'href' => $alert['href'] ?? null,
            'required_permissions' => $permissions
        ];
    }

    private static function title(array $alert, int $delta): string
    {
        $label = (string)($alert['label'] ?? 'Alert operativo');

        return $delta === 1
            ? $label . ': nuovo elemento'
            : $label . ': +' . $delta . ' elementi';
    }

    private static function message(array $alert, int $delta, int $currentCount): string
    {
        $label = strtolower((string)($alert['label'] ?? 'alert operativo'));
        $prefix = $delta === 1
            ? 'E stato rilevato 1 nuovo elemento per '
            : 'Sono stati rilevati ' . $delta . ' nuovi elementi per ';

        return $prefix . $label . '. Totale attuale: ' . $currentCount . '.';
    }

    private static function visibleNotifications(int $userId, int $limit, bool $onlyUnread = false): array
    {
        $stmt = Database::get()->prepare("
            SELECT
                n.id,
                n.alert_code,
                n.title,
                n.message,
                n.delta,
                n.count_value,
                n.href,
                n.required_permissions_json,
                n.created_at,
                r.read_at,
                r.dismissed_at
            FROM operational_notifications n
            LEFT JOIN operational_notification_reads r
                ON r.notification_id = n.id
               AND r.user_id = ?
            ORDER BY n.created_at DESC, n.id DESC
            LIMIT $limit
        ");
        $stmt->execute([$userId]);

        $visible = [];
        foreach ($stmt->fetchAll() as $row) {
            if ($onlyUnread && !empty($row['read_at'])) {
                continue;
            }

            if (!self::canUserSeeRow($userId, $row)) {
                continue;
            }

            $visible[] = self::payload($row);
        }

        return $visible;
    }

    private static function unreadCount(int $userId): int
    {
        return count(self::visibleNotifications($userId, 1000, true));
    }

    private static function canUserSeeNotification(int $userId, int $notificationId): bool
    {
        $stmt = Database::get()->prepare("
            SELECT required_permissions_json
            FROM operational_notifications
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$notificationId]);

        $row = $stmt->fetch();

        return $row ? self::canUserSeeRow($userId, $row) : false;
    }

    private static function canUserSeeRow(int $userId, array $row): bool
    {
        $user = User::findById($userId);

        if (!$user) {
            return false;
        }

        if (($user['profile_code'] ?? '') === 'admin') {
            return true;
        }

        $requiredPermissions = self::decodePermissions($row['required_permissions_json'] ?? null);

        if (empty($requiredPermissions)) {
            return true;
        }

        $permissions = User::permissions($userId);

        return !empty(array_intersect($requiredPermissions, $permissions));
    }

    private static function payload(array $row): array
    {
        return [
            'id' => (int)$row['id'],
            'alert_code' => $row['alert_code'],
            'title' => $row['title'],
            'message' => $row['message'],
            'delta' => (int)$row['delta'],
            'count_value' => (int)$row['count_value'],
            'href' => $row['href'],
            'required_permissions' => self::decodePermissions($row['required_permissions_json'] ?? null),
            'created_at' => $row['created_at'],
            'read_at' => $row['read_at'] ?? null,
            'dismissed_at' => $row['dismissed_at'] ?? null,
            'is_read' => !empty($row['read_at'])
        ];
    }

    private static function decodePermissions($json): array
    {
        if (!$json) {
            return [];
        }

        $permissions = json_decode((string)$json, true);

        return is_array($permissions) ? array_values(array_filter($permissions, 'is_string')) : [];
    }
}
