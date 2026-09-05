<?php

require __DIR__ . '/../../api/v1/bootstrap.php';

use Api\Core\Database;
use Api\Models\Season;
use Api\Services\OperationalNotificationService;

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function pass(string $message): void
{
    echo "[PASS] {$message}" . PHP_EOL;
}

function firstValue(PDO $db, string $sql, array $params = [])
{
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchColumn();
}

function ensureProfile(PDO $db, string $code, string $name): int
{
    $stmt = $db->prepare("
        INSERT INTO profiles (code, name, is_system)
        VALUES (?, ?, 0)
        ON DUPLICATE KEY UPDATE name = VALUES(name)
    ");
    $stmt->execute([$code, $name]);

    return (int)firstValue($db, "SELECT id FROM profiles WHERE code = ?", [$code]);
}

function permissionId(PDO $db, string $code): int
{
    return (int)firstValue($db, "SELECT id FROM permissions WHERE code = ?", [$code]);
}

function ensureUser(PDO $db, string $username, int $profileId): int
{
    $stmt = $db->prepare("
        INSERT INTO users
            (username, password, first_name, last_name, email, profile_id)
        VALUES
            (?, ?, 'Smoke', 'Notifiche', ?, ?)
        ON DUPLICATE KEY UPDATE
            profile_id = VALUES(profile_id),
            first_name = VALUES(first_name),
            last_name = VALUES(last_name),
            email = VALUES(email)
    ");
    $stmt->execute([
        $username,
        password_hash('test123!', PASSWORD_DEFAULT),
        $username . '@example.test',
        $profileId
    ]);

    return (int)firstValue($db, "SELECT id FROM users WHERE username = ?", [$username]);
}

$db = Database::get();
$db->beginTransaction();

try {
    $seasonId = Season::currentId();
    assertTrue($seasonId > 0, 'Nessuna stagione corrente disponibile');

    foreach ([
        'operational_alert_snapshots',
        'operational_notifications',
        'operational_notification_reads'
    ] as $table) {
        $exists = (int)firstValue($db, "
            SELECT COUNT(*)
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
              AND table_name = ?
        ", [$table]);
        assertTrue($exists === 1, "Tabella {$table} non trovata: eseguire la migration notifiche");
    }

    $adminProfileId = (int)firstValue($db, "SELECT id FROM profiles WHERE code = 'admin'");
    assertTrue($adminProfileId > 0, 'Profilo admin non trovato');

    $fieldsEditPermissionId = permissionId($db, 'fields.edit');
    assertTrue($fieldsEditPermissionId > 0, 'Permesso fields.edit non trovato');

    $viewerProfileId = ensureProfile($db, 'smoke_notifications_viewer', 'Smoke notifiche viewer');
    $editorProfileId = ensureProfile($db, 'smoke_notifications_editor', 'Smoke notifiche editor');

    $db->prepare("
        INSERT IGNORE INTO profile_permissions (profile_id, permission_id)
        VALUES (?, ?)
    ")->execute([$editorProfileId, $fieldsEditPermissionId]);

    $adminUserId = ensureUser($db, 'smoke_notifications_admin', $adminProfileId);
    $viewerUserId = ensureUser($db, 'smoke_notifications_viewer', $viewerProfileId);
    $editorUserId = ensureUser($db, 'smoke_notifications_editor', $editorProfileId);

    $db->exec("DELETE FROM operational_notification_reads");
    $db->exec("DELETE FROM operational_notifications");
    $db->exec("DELETE FROM operational_alert_snapshots");

    $baseline = OperationalNotificationService::syncForSeason($seasonId);
    assertTrue($baseline['created_notifications'] === 0, 'Il primo sync non deve generare notifiche');
    pass('baseline snapshot');

    $fieldName = 'Smoke Stadio Senza Coordinate ' . uniqid();
    $stmt = $db->prepare("
        INSERT INTO fields
            (name, address, city, can_host_11, can_host_7, can_host_5, is_active, notes)
        VALUES
            (?, 'Via Smoke 1', 'Smoke City', 1, 1, 1, 1, 'Smoke notifiche')
    ");
    $stmt->execute([$fieldName]);

    $generated = OperationalNotificationService::syncForSeason($seasonId);
    assertTrue($generated['created_notifications'] === 1, 'Incremento alert non ha generato una notifica');
    assertTrue($generated['notifications'][0]['alert_code'] === 'fields_without_geocode', 'Alert generato non atteso');
    assertTrue($generated['notifications'][0]['delta'] === 1, 'Delta notifica non corretto');
    pass('generazione su incremento');

    $adminNotifications = OperationalNotificationService::forUser($adminUserId);
    assertTrue($adminNotifications['unread_count'] === 1, 'Admin non vede la notifica non letta');
    assertTrue(count($adminNotifications['notifications']) === 1, 'Admin non vede esattamente una notifica');
    pass('visibilita admin');

    $viewerNotifications = OperationalNotificationService::forUser($viewerUserId);
    assertTrue($viewerNotifications['unread_count'] === 0, 'Viewer senza permesso vede notifiche non autorizzate');
    assertTrue(count($viewerNotifications['notifications']) === 0, 'Viewer senza permesso vede la lista notifiche');
    pass('filtro permessi negativo');

    $editorNotifications = OperationalNotificationService::forUser($editorUserId);
    assertTrue($editorNotifications['unread_count'] === 1, 'Editor con fields.edit non vede la notifica');
    assertTrue(count($editorNotifications['notifications']) === 1, 'Editor con fields.edit non vede la lista corretta');
    pass('filtro permessi positivo');

    $notificationId = (int)$editorNotifications['notifications'][0]['id'];
    assertTrue(OperationalNotificationService::markRead($editorUserId, $notificationId), 'Marcatura lettura singola non riuscita');
    assertTrue(OperationalNotificationService::forUser($editorUserId)['unread_count'] === 0, 'Notifica singola ancora non letta');
    pass('mark read singola');

    assertTrue(OperationalNotificationService::markAllRead($adminUserId) === 1, 'Mark all read admin non ha aggiornato la notifica');
    assertTrue(OperationalNotificationService::forUser($adminUserId)['unread_count'] === 0, 'Admin ha ancora notifiche non lette');
    pass('mark all read');

    $secondSync = OperationalNotificationService::syncForSeason($seasonId);
    assertTrue($secondSync['created_notifications'] === 0, 'Sync senza incremento ha generato notifiche duplicate');
    pass('nessun duplicato senza incremento');

    $db->rollBack();
    echo "[OK] Operational notifications smoke test completed with rollback" . PHP_EOL;
    exit(0);
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    fwrite(STDERR, "[FAIL] " . $e->getMessage() . PHP_EOL);
    exit(1);
}
