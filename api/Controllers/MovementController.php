<?php
namespace Api\Controllers;

use Api\Http\Request;
use Api\V1\Response;
use Api\Services\MovementService;

class MovementController {

    // GET /movements
    public function index(): void
    {
        $id = (int)($_GET['id'] ?? 0);

        if ($id) {
            $movement = MovementService::find($id);

            if (!$movement) {
                Response::error('Movimento non trovato', 404);
            }

            Response::ok($movement);
        }

        Response::ok(
            MovementService::all($_GET)
        );
    }

    public function kpi(): void
    {
        Response::ok(MovementService::kpi());
    }

    // POST /movements
    public function store(): void
    {
        $payload = Request::json();

        $result = MovementService::create($payload);

        if (!$result['success'] && $result['reason'] === 'missing_current_season') {
            Response::error('Stagione corrente non configurata', 500);
        }

        if (!$result['success']) {
            Response::error('Validazione fallita', 422, $result['errors'] ?? []);
        }

        Response::ok(['id' => $result['id']]);
    }

    public function update(): void
    {
        parse_str($_SERVER['QUERY_STRING'], $qs);
        $id = (int)($qs['id'] ?? 0);

        if (!$id) {
            Response::error('ID mancante', 400);
        }

        $payload = Request::json();

        $result = MovementService::update($id, $payload);

        if (!$result['success'] && $result['reason'] === 'not_found') {
            Response::error('Movimento non trovato', 404);
        }

        if (!$result['success'] && $result['reason'] === 'validation') {
            Response::error('Validazione fallita', 422, $result['errors'] ?? []);
        }

        Response::ok(['updated' => true]);
    }

    public function delete(): void
    {
        parse_str($_SERVER['QUERY_STRING'], $qs);
        $id = (int)($qs['id'] ?? 0);

        if (!$id) {
            Response::error('ID mancante', 400);
        }

        $result = MovementService::delete($id);

        if (!$result['success']) {
            Response::error('Movimento non trovato', 404);
        }

        Response::ok(['deleted' => true]);
    }

}
