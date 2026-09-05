<?php
namespace Api\Controllers;

use Api\Http\Request;
use Api\Models\Team;
use Api\Services\OperationalNotificationService;
use Api\Validators\TeamValidator;
use Api\V1\Response;

class TeamController {

    public function index(): void
    {

        $competitionId = $_GET['competition_id'] ?? null;

        if ($competitionId) {
            Response::ok(Team::byCompetition((int)$competitionId));
        }

        Response::ok(Team::all());
    }

    public function store(): void
    {
        $payload = Request::json();
        $errors = TeamValidator::validate($payload);

        if (!empty($errors)) {
            Response::error('Validazione fallita', 422, $errors);
        }

        $id = Team::create($payload);
        OperationalNotificationService::syncCurrentSeasonSafely();

        Response::ok(['id' => $id]);
    }

    public function update(): void
    {
        $id = (int)($_GET['id'] ?? 0);

        if (!$id) {
            Response::error('ID squadra mancante', 400);
        }

        if (!Team::find($id)) {
            Response::error('Squadra non trovata', 404);
        }

        $payload = Request::json();
        $errors = TeamValidator::validate($payload);

        if (!empty($errors)) {
            Response::error('Validazione fallita', 422, $errors);
        }

        Team::update($id, $payload);
        OperationalNotificationService::syncCurrentSeasonSafely();

        Response::ok(['updated' => true]);
    }

    public function delete(): void
    {
        $id = (int)($_GET['id'] ?? 0);

        if (!$id) {
            Response::error('ID squadra mancante', 400);
        }

        if (!Team::find($id)) {
            Response::error('Squadra non trovata', 404);
        }

        if (Team::usageCount($id) > 0) {
            Response::error('Squadra già utilizzata in movimenti o partite', 409);
        }

        Team::delete($id);
        OperationalNotificationService::syncCurrentSeasonSafely();

        Response::ok(['deleted' => true]);
    }
}
