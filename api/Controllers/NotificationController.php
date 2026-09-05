<?php
namespace Api\Controllers;

use Api\Http\Request;
use Api\Middleware\JwtMiddleware;
use Api\Models\Season;
use Api\Services\OperationalNotificationService;
use Api\V1\Response;

class NotificationController {

    public function index(): void
    {
        $userId = $this->userId();
        $seasonId = Season::currentId();

        if ($seasonId) {
            OperationalNotificationService::syncForSeason((int)$seasonId);
        }

        Response::ok(OperationalNotificationService::forUser($userId));
    }

    public function markRead(): void
    {
        $userId = $this->userId();
        $payload = Request::json();
        $notificationId = (int)($payload['notification_id'] ?? 0);

        if ($notificationId <= 0) {
            Response::error('ID notifica mancante', 400);
        }

        if (!OperationalNotificationService::markRead($userId, $notificationId)) {
            Response::error('Notifica non trovata o non autorizzata', 404);
        }

        Response::ok(['updated' => true]);
    }

    public function markAllRead(): void
    {
        $userId = $this->userId();

        Response::ok([
            'updated' => OperationalNotificationService::markAllRead($userId)
        ]);
    }

    private function userId(): int
    {
        $payload = JwtMiddleware::user();
        $userId = (int)($payload->uid ?? 0);

        if ($userId <= 0) {
            Response::error('Utente non valido', 401);
        }

        return $userId;
    }
}
