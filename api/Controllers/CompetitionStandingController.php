<?php
namespace Api\Controllers;

use Api\Http\Request;
use Api\Models\Competition;
use Api\Models\CompetitionStanding;
use Api\Validators\CompetitionStandingValidator;
use Api\V1\Response;

class CompetitionStandingController {

    public function index(): void
    {
        $competition = $this->leagueCompetition();

        Response::ok([
            'competition' => [
                'id' => (int)$competition['id'],
                'name' => $competition['name'],
                'type' => $competition['type'],
                'football_type' => $competition['football_type'],
                'season_id' => (int)$competition['season_id'],
                'season' => $competition['season']
            ],
            'standings' => CompetitionStanding::allForCompetition((int)$competition['id'])
        ]);
    }

    public function update(): void
    {
        $competition = $this->leagueCompetition();
        $competitionId = (int)$competition['id'];
        $payload = Request::json();
        $errors = CompetitionStandingValidator::validate($competitionId, $payload);

        if (!empty($errors)) {
            Response::error('Validazione fallita', 422, $errors);
        }

        CompetitionStanding::updateMany($competitionId, $payload['standings']);

        Response::ok([
            'updated' => true,
            'standings' => CompetitionStanding::allForCompetition($competitionId)
        ]);
    }

    public function recalculate(): void
    {
        $competition = $this->leagueCompetition();
        $competitionId = (int)$competition['id'];

        CompetitionStanding::recalculateFromMatches($competitionId);

        Response::ok([
            'recalculated' => true,
            'standings' => CompetitionStanding::allForCompetition($competitionId)
        ]);
    }

    private function leagueCompetition(): array
    {
        $competitionId = (int)($_GET['competition_id'] ?? 0);

        if (!$competitionId) {
            Response::error('ID competizione mancante', 400);
        }

        $competition = Competition::find($competitionId);

        if (!$competition) {
            Response::error('Competizione non trovata', 404);
        }

        if (($competition['type'] ?? '') !== 'campionato') {
            Response::error('Classifica disponibile solo per competizioni di tipo campionato', 409);
        }

        return $competition;
    }
}
