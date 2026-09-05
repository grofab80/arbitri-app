<?php
namespace Api\Controllers;

use Api\Middleware\JwtMiddleware;
use Api\Services\BalanceService;
use Api\V1\Response;

class BalanceController {

    public function index(): void
    {
        Response::ok(BalanceService::summary($_GET));
    }

    public function seasonComparison(): void
    {
        Response::ok(BalanceService::seasonComparison());
    }

    public function seasonAnalysis(): void
    {
        Response::ok(BalanceService::seasonAnalysis($_GET));
    }

    public function export(): void
    {
        $export = BalanceService::csvExport($_GET);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $export['filename'] . '"');

        echo $export['content'];
        exit;
    }

    public function approveClosure(): void
    {
        $seasonId = (int)($_GET['season_id'] ?? 0);

        if ($seasonId <= 0) {
            Response::error('ID stagione mancante', 400);
        }

        $user = JwtMiddleware::user();
        $userId = (int)($user->uid ?? 0);

        if ($userId <= 0) {
            Response::error('Utente non valido', 401);
        }

        $result = BalanceService::approveClosure($seasonId, $userId);

        if (empty($result['success'])) {
            Response::error($result['message'] ?? 'Approvazione non riuscita', $result['status'] ?? 422);
        }

        Response::ok([
            'message' => $result['message'],
            'closure' => $result['closure']
        ]);
    }

    public function archiveClosureMovements(): void
    {
        $seasonId = (int)($_GET['season_id'] ?? 0);

        if ($seasonId <= 0) {
            Response::error('ID stagione mancante', 400);
        }

        $result = BalanceService::archiveClosureMovements($seasonId);

        if (empty($result['success'])) {
            Response::error($result['message'] ?? 'Storicizzazione non riuscita', $result['status'] ?? 422);
        }

        Response::ok([
            'message' => $result['message'],
            'closure' => $result['closure']
        ]);
    }
}
