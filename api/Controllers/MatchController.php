<?php
namespace Api\Controllers;

use Api\Http\Request;
use Api\Models\MatchModel;
use Api\Models\Season;
use Api\Services\OperationalNotificationService;
use Api\Validators\MatchValidator;
use Api\V1\Response;

class MatchController {

    public function index(): void
    {
        Response::ok(MatchModel::all($_GET));
    }

    public function store(): void
    {
        if (!Season::currentId()) {
            Response::error('Stagione corrente non configurata', 500);
        }

        $payload = Request::json();
        $errors = MatchValidator::validate($payload);

        if (!empty($errors)) {
            Response::error('Validazione fallita', 422, $errors);
        }

        $id = MatchModel::create($payload);
        OperationalNotificationService::syncCurrentSeasonSafely();

        Response::ok(['id' => $id]);
    }

    public function update(): void
    {
        $id = (int)($_GET['id'] ?? 0);

        if (!$id) {
            Response::error('ID partita mancante', 400);
        }

        if (!MatchModel::find($id)) {
            Response::error('Partita non trovata', 404);
        }

        $payload = Request::json();
        $errors = MatchValidator::validate($payload);

        if (!empty($errors)) {
            Response::error('Validazione fallita', 422, $errors);
        }

        MatchModel::update($id, $payload);
        OperationalNotificationService::syncCurrentSeasonSafely();

        Response::ok(['updated' => true]);
    }

    public function delete(): void
    {
        $id = (int)($_GET['id'] ?? 0);

        if (!$id) {
            Response::error('ID partita mancante', 400);
        }

        if (!MatchModel::find($id)) {
            Response::error('Partita non trovata', 404);
        }

        MatchModel::delete($id);
        OperationalNotificationService::syncCurrentSeasonSafely();

        Response::ok(['deleted' => true]);
    }
}
