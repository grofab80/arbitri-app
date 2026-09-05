<?php
namespace Api\Controllers;

use Api\Http\Request;
use Api\Models\Field;
use Api\Services\GeocodingService;
use Api\Services\OperationalNotificationService;
use Api\Validators\FieldValidator;
use Api\V1\Response;

class FieldController {

    public function index(): void
    {
        if (!empty($_GET['active'])) {
            Response::ok(Field::active());
        }

        Response::ok(Field::all());
    }

    public function store(): void
    {
        $payload = Request::json();
        $errors = FieldValidator::validate($payload);

        if (!empty($errors)) {
            Response::error('Validazione fallita', 422, $errors);
        }

        $id = Field::create($payload);
        OperationalNotificationService::syncCurrentSeasonSafely();

        Response::ok(['id' => $id]);
    }

    public function update(): void
    {
        $id = (int)($_GET['id'] ?? 0);

        if (!$id) {
            Response::error('ID stadio mancante', 400);
        }

        if (!Field::find($id)) {
            Response::error('Stadio non trovato', 404);
        }

        $payload = Request::json();
        $errors = FieldValidator::validate($payload);

        if (!empty($errors)) {
            Response::error('Validazione fallita', 422, $errors);
        }

        Field::update($id, $payload);
        OperationalNotificationService::syncCurrentSeasonSafely();

        Response::ok(['updated' => true]);
    }

    public function delete(): void
    {
        $id = (int)($_GET['id'] ?? 0);

        if (!$id) {
            Response::error('ID stadio mancante', 400);
        }

        if (!Field::find($id)) {
            Response::error('Stadio non trovato', 404);
        }

        if (Field::usageCount($id) > 0) {
            Response::error('Stadio già utilizzato da squadre o partite', 409);
        }

        Field::delete($id);
        OperationalNotificationService::syncCurrentSeasonSafely();

        Response::ok(['deleted' => true]);
    }

    public function geocode(): void
    {
        $id = (int)($_GET['id'] ?? 0);

        if (!$id) {
            Response::error('ID stadio mancante', 400);
        }

        $field = Field::find($id);

        if (!$field) {
            Response::error('Stadio non trovato', 404);
        }

        $address = [
            'address' => $field['address'] ?? '',
            'city' => $field['city'] ?? '',
            'province' => $field['province'] ?? '',
            'postal_code' => $field['postal_code'] ?? '',
            'country' => $field['country'] ?? 'Italia'
        ];

        try {
            $result = GeocodingService::geocode($address);
        } catch (\RuntimeException $e) {
            Response::error($e->getMessage(), 502);
        }

        if (!$result) {
            Response::error('Indirizzo non trovato', 404);
        }

        Field::updateGeocode(
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
