<?php
namespace Api\Controllers;

use Api\Http\Request;
use Api\Models\Field;
use Api\Models\Referee;
use Api\Services\DistanceService;
use Api\Services\GeocodingService;
use Api\Services\OperationalNotificationService;
use Api\Validators\RefereeValidator;
use Api\V1\Response;

class RefereeController {

    public function index(): void
    {

        Response::ok(Referee::all());
    }

    public function candidates(): void
    {
        $fieldId = (int)($_GET['field_id'] ?? 0);
        $field = null;

        if ($fieldId > 0) {
            $field = Field::find($fieldId);

            if (!$field) {
                Response::error('Stadio non trovato', 404);
            }
        }

        $referees = Referee::all();

        foreach ($referees as &$referee) {
            $referee['distance_km'] = null;

            if ($field) {
                $referee['distance_km'] = DistanceService::kilometers(
                    $referee['latitude'] ?? null,
                    $referee['longitude'] ?? null,
                    $field['latitude'] ?? null,
                    $field['longitude'] ?? null
                );
            }
        }
        unset($referee);

        usort($referees, function ($a, $b) {
            $distanceA = $a['distance_km'];
            $distanceB = $b['distance_km'];

            if ($distanceA !== null && $distanceB !== null && $distanceA !== $distanceB) {
                return $distanceA <=> $distanceB;
            }

            if ($distanceA !== null && $distanceB === null) {
                return -1;
            }

            if ($distanceA === null && $distanceB !== null) {
                return 1;
            }

            $ratingCompare = (int)($b['rating'] ?? 3) <=> (int)($a['rating'] ?? 3);

            if ($ratingCompare !== 0) {
                return $ratingCompare;
            }

            return strcmp((string)$a['name'], (string)$b['name']);
        });

        Response::ok($referees);
    }

    public function store(): void
    {
        $payload = Request::json();
        $errors = RefereeValidator::validate($payload);

        if (!empty($errors)) {
            Response::error('Validazione fallita', 422, $errors);
        }

        $id = Referee::create($payload);
        OperationalNotificationService::syncCurrentSeasonSafely();

        Response::ok(['id' => $id]);
    }

    public function update(): void
    {
        $id = (int)($_GET['id'] ?? 0);

        if (!$id) {
            Response::error('ID arbitro mancante', 400);
        }

        if (!Referee::find($id)) {
            Response::error('Arbitro non trovato', 404);
        }

        $payload = Request::json();
        $errors = RefereeValidator::validate($payload);

        if (!empty($errors)) {
            Response::error('Validazione fallita', 422, $errors);
        }

        Referee::update($id, $payload);
        OperationalNotificationService::syncCurrentSeasonSafely();

        Response::ok(['updated' => true]);
    }

    public function delete(): void
    {
        $id = (int)($_GET['id'] ?? 0);

        if (!$id) {
            Response::error('ID arbitro mancante', 400);
        }

        if (!Referee::find($id)) {
            Response::error('Arbitro non trovato', 404);
        }

        Referee::delete($id);
        OperationalNotificationService::syncCurrentSeasonSafely();

        Response::ok(['deleted' => true]);
    }

    public function geocode(): void
    {
        $id = (int)($_GET['id'] ?? 0);

        if (!$id) {
            Response::error('ID arbitro mancante', 400);
        }

        $referee = Referee::find($id);

        if (!$referee) {
            Response::error('Arbitro non trovato', 404);
        }

        try {
            $result = GeocodingService::geocode($referee);
        } catch (\RuntimeException $e) {
            Response::error($e->getMessage(), 502);
        }

        if (!$result) {
            Response::error('Indirizzo non trovato', 404);
        }

        Referee::updateGeocode(
            $id,
            (float)$result['latitude'],
            (float)$result['longitude']
        );
        OperationalNotificationService::syncCurrentSeasonSafely();

        Response::ok([
            'latitude' => $result['latitude'],
            'longitude' => $result['longitude'],
            'display_name' => $result['display_name'],
            'provider' => $result['provider']
        ]);
    }
}
