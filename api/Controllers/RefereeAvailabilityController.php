<?php
namespace Api\Controllers;

use Api\Http\Request;
use Api\Models\Referee;
use Api\Models\RefereeAvailability;
use Api\Validators\RefereeAvailabilityValidator;
use Api\V1\Response;

class RefereeAvailabilityController {

    public function index(): void
    {
        $refereeId = isset($_GET['referee_id']) && $_GET['referee_id'] !== ''
            ? (int)$_GET['referee_id']
            : null;

        if ($refereeId !== null && $refereeId <= 0) {
            Response::error('ID arbitro non valido', 400);
        }

        if ($refereeId !== null && !Referee::find($refereeId)) {
            Response::error('Arbitro non trovato', 404);
        }

        Response::ok(RefereeAvailability::all($refereeId));
    }

    public function store(): void
    {
        $payload = Request::json();
        $errors = RefereeAvailabilityValidator::validate($payload);

        if (!empty($errors)) {
            Response::error('Validazione fallita', 422, $errors);
        }

        if (!Referee::find((int)$payload['referee_id'])) {
            Response::error('Arbitro non trovato', 404);
        }

        $id = RefereeAvailability::create($payload);

        Response::ok(['id' => $id]);
    }

    public function update(): void
    {
        $id = (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            Response::error('ID disponibilita mancante', 400);
        }

        if (!RefereeAvailability::find($id)) {
            Response::error('Disponibilita non trovata', 404);
        }

        $payload = Request::json();
        $errors = RefereeAvailabilityValidator::validate($payload);

        if (!empty($errors)) {
            Response::error('Validazione fallita', 422, $errors);
        }

        if (!Referee::find((int)$payload['referee_id'])) {
            Response::error('Arbitro non trovato', 404);
        }

        RefereeAvailability::update($id, $payload);

        Response::ok(['updated' => true]);
    }

    public function delete(): void
    {
        $id = (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            Response::error('ID disponibilita mancante', 400);
        }

        if (!RefereeAvailability::find($id)) {
            Response::error('Disponibilita non trovata', 404);
        }

        RefereeAvailability::delete($id);

        Response::ok(['deleted' => true]);
    }
}
