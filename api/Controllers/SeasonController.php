<?php
namespace Api\Controllers;

use Api\Http\Request;
use Api\Models\Season;
use Api\Services\OperationalNotificationService;
use Api\Validators\SeasonValidator;
use Api\V1\Response;

class SeasonController {

    public function index(): void
    {
        Response::ok(Season::all());
    }

    public function current(): void
    {
        $season = Season::current();

        if (!$season) {
            Response::error('Stagione corrente non configurata', 404);
        }

        Response::ok([
            'id' => (int)$season['id'],
            'name' => $season['name'],
            'starts_on' => $season['starts_on'],
            'ends_on' => $season['ends_on'],
            'status' => $season['status'] ?? 'in_corso'
        ]);
    }

    public function store(): void
    {
        $payload = Request::json();

        $errors = SeasonValidator::create($payload);

        if (!empty($errors)) {
            Response::error('Validazione fallita', 422, $errors);
        }

        $id = Season::create($payload);
        OperationalNotificationService::syncCurrentSeasonSafely();

        Response::ok(['id' => $id]);
    }

    public function setCurrent(): void
    {
        $id = (int)($_GET['id'] ?? 0);

        if (!$id) {
            Response::error('ID stagione mancante', 400);
        }

        if (!Season::find($id)) {
            Response::error('Stagione non trovata', 404);
        }

        Season::setCurrent($id);
        OperationalNotificationService::syncCurrentSeasonSafely();

        Response::ok(['current' => true]);
    }

    public function setStatus(): void
    {
        $id = (int)($_GET['id'] ?? 0);

        if (!$id) {
            Response::error('ID stagione mancante', 400);
        }

        if (!Season::find($id)) {
            Response::error('Stagione non trovata', 404);
        }

        $payload = Request::json();
        $errors = SeasonValidator::status($payload);

        if (!empty($errors)) {
            Response::error('Validazione fallita', 422, $errors);
        }

        Season::setStatus($id, $payload['status']);
        OperationalNotificationService::syncCurrentSeasonSafely();

        Response::ok([
            'updated' => true,
            'status' => $payload['status']
        ]);
    }
}
