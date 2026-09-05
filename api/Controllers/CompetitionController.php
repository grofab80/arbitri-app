<?php
namespace Api\Controllers;

use Api\Http\Request;
use Api\Models\Competition;
use Api\Models\Season;
use Api\Services\OperationalNotificationService;
use Api\Validators\CompetitionValidator;
use Api\V1\Response;

class CompetitionController {

    public function index(): void
    {

        Response::ok(Competition::all());
    }

    public function store(): void
    {
        $payload = Request::json();
        $errors = CompetitionValidator::validate($payload);

        $season = null;
        if (empty($errors['season_id'])) {
            $season = Season::find((int)$payload['season_id']);
            if (!$season) {
                $errors['season_id'] = 'Stagione non trovata';
            }
        }

        if (
            empty($errors['name'])
            && empty($errors['season_id'])
            && Competition::existsByNameAndSeason($payload['name'], (int)$payload['season_id'])
        ) {
            $errors['name'] = 'Competizione già presente per questa stagione';
        }

        if (!empty($errors)) {
            Response::error('Validazione fallita', 422, $errors);
        }

        $id = Competition::create($payload, $season['name']);
        OperationalNotificationService::syncCurrentSeasonSafely();

        Response::ok(['id' => $id]);
    }

    public function update(): void
    {
        $id = (int)($_GET['id'] ?? 0);

        if (!$id) {
            Response::error('ID competizione mancante', 400);
        }

        $competition = Competition::find($id);
        if (!$competition) {
            Response::error('Competizione non trovata', 404);
        }

        $payload = Request::json();
        $errors = CompetitionValidator::validate($payload);

        $season = null;
        if (empty($errors['season_id'])) {
            $season = Season::find((int)$payload['season_id']);
            if (!$season) {
                $errors['season_id'] = 'Stagione non trovata';
            }
        }

        if (
            empty($errors['name'])
            && empty($errors['season_id'])
            && Competition::existsByNameAndSeason($payload['name'], (int)$payload['season_id'], $id)
        ) {
            $errors['name'] = 'Competizione già presente per questa stagione';
        }

        if (
            empty($errors['season_id'])
            && (int)$competition['season_id'] !== (int)$payload['season_id']
            && Competition::matchCount($id) > 0
        ) {
            $errors['season_id'] = 'Non puoi cambiare stagione a una competizione con partite collegate';
        }

        if (!empty($errors)) {
            Response::error('Validazione fallita', 422, $errors);
        }

        Competition::update($id, $payload, $season['name']);
        OperationalNotificationService::syncCurrentSeasonSafely();

        Response::ok(['updated' => true]);
    }

    public function delete(): void
    {
        $id = (int)($_GET['id'] ?? 0);

        if (!$id) {
            Response::error('ID competizione mancante', 400);
        }

        if (!Competition::find($id)) {
            Response::error('Competizione non trovata', 404);
        }

        if (Competition::usageCount($id) > 0) {
            Response::error('Competizione già utilizzata in squadre, movimenti o partite', 409);
        }

        Competition::delete($id);
        OperationalNotificationService::syncCurrentSeasonSafely();

        Response::ok(['deleted' => true]);
    }
}
