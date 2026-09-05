<?php
namespace Api\Controllers;

use Api\Http\Request;
use Api\Middleware\JwtMiddleware;
use Api\Models\Designation;
use Api\Services\OperationalNotificationService;
use Api\V1\Response;

class DesignationController {

    public function index(): void
    {
        $footballType = (string)($_GET['football_type'] ?? '');
        $referees = in_array($footballType, ['11', '7', '5'], true)
            ? Designation::refereeOptions($footballType)
            : [];
        $matches = Designation::attachAvailability(
            Designation::matches($_GET),
            $referees
        );

        Response::ok([
            'matches' => $matches,
            'referees' => $referees
        ]);
    }

    public function assign(): void
    {
        $payload = Request::json();
        $matchId = (int)($payload['match_id'] ?? 0);
        $refereeId = $payload['referee_id'] ?? null;

        if ($matchId <= 0) {
            Response::error('ID partita mancante', 400);
        }

        if ($refereeId === '' || $refereeId === null) {
            $refereeId = null;
        } else {
            $refereeId = (int)$refereeId;
            if ($refereeId <= 0) {
                Response::error('ID arbitro non valido', 422);
            }
        }

        $user = JwtMiddleware::user();
        $userId = (int)($user->uid ?? 0);

        if ($userId <= 0) {
            Response::error('Utente non valido', 401);
        }

        $result = Designation::assignManual($matchId, $refereeId, $userId);

        if (empty($result['success'])) {
            Response::error($result['message'] ?? 'Designazione non salvata', $result['status'] ?? 422);
        }

        OperationalNotificationService::syncCurrentSeasonSafely();

        Response::ok($result['designation']);
    }

    public function summary(): void
    {
        Response::ok(Designation::summary($_GET));
    }

    public function generate(): void
    {
        $payload = Request::json();
        $user = JwtMiddleware::user();
        $userId = (int)($user->uid ?? 0);

        if ($userId <= 0) {
            Response::error('Utente non valido', 401);
        }

        $filters = [
            'football_type' => $payload['football_type'] ?? null,
            'competition_id' => $payload['competition_id'] ?? null,
            'match_day' => $payload['match_day'] ?? null
        ];

        $result = Designation::generateProposals($filters, $userId);
        OperationalNotificationService::syncCurrentSeasonSafely();

        Response::ok($result);
    }

    public function regenerate(): void
    {
        $payload = Request::json();
        $user = JwtMiddleware::user();
        $userId = (int)($user->uid ?? 0);

        if ($userId <= 0) {
            Response::error('Utente non valido', 401);
        }

        $result = Designation::regenerateUnconfirmed($this->filtersFromPayload($payload), $userId);
        OperationalNotificationService::syncCurrentSeasonSafely();

        Response::ok($result);
    }

    public function confirmFiltered(): void
    {
        $payload = Request::json();
        $user = JwtMiddleware::user();
        $userId = (int)($user->uid ?? 0);

        if ($userId <= 0) {
            Response::error('Utente non valido', 401);
        }

        $result = Designation::confirmFilteredProposals($this->filtersFromPayload($payload), $userId);
        OperationalNotificationService::syncCurrentSeasonSafely();

        Response::ok($result);
    }

    public function clearAutomatic(): void
    {
        $payload = Request::json();
        $result = Designation::clearAutomaticProposals($this->filtersFromPayload($payload));
        OperationalNotificationService::syncCurrentSeasonSafely();

        Response::ok($result);
    }

    public function confirm(): void
    {
        $payload = Request::json();
        $matchId = (int)($payload['match_id'] ?? 0);

        if ($matchId <= 0) {
            Response::error('ID partita mancante', 400);
        }

        $user = JwtMiddleware::user();
        $userId = (int)($user->uid ?? 0);

        if ($userId <= 0) {
            Response::error('Utente non valido', 401);
        }

        $result = Designation::confirm($matchId, $userId);

        if (empty($result['success'])) {
            Response::error($result['message'] ?? 'Designazione non confermata', $result['status'] ?? 422);
        }

        OperationalNotificationService::syncCurrentSeasonSafely();

        Response::ok($result['designation']);
    }

    public function blacklist(): void
    {
        Response::ok(Designation::blacklist());
    }

    public function storeBlacklist(): void
    {
        $payload = Request::json();
        $refereeId = (int)($payload['referee_id'] ?? 0);
        $teamId = (int)($payload['team_id'] ?? 0);
        $reason = $payload['reason'] ?? null;

        if ($refereeId <= 0) {
            Response::error('Arbitro mancante', 422);
        }

        if ($teamId <= 0) {
            Response::error('Squadra mancante', 422);
        }

        $id = Designation::addBlacklist($refereeId, $teamId, $reason);

        Response::ok(['id' => $id]);
    }

    public function deleteBlacklist(): void
    {
        $id = (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            Response::error('ID blacklist mancante', 400);
        }

        if (!Designation::removeBlacklist($id)) {
            Response::error('Regola blacklist non trovata', 404);
        }

        Response::ok(['deleted' => true]);
    }

    private function filtersFromPayload(array $payload): array
    {
        return [
            'football_type' => $payload['football_type'] ?? null,
            'competition_id' => $payload['competition_id'] ?? null,
            'match_day' => $payload['match_day'] ?? null
        ];
    }
}
