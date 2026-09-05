<?php
namespace Api\Controllers;

use Api\Http\Request;
use Api\Middleware\JwtMiddleware;
use Api\Models\ExternalSource;
use Api\Models\ExternalSyncOverride;
use Api\Models\Season;
use Api\Models\SyncRun;
use Api\Services\FootballSyncImportService;
use Api\Validators\FootballSyncSourceValidator;
use Api\Validators\FootballSyncOverrideValidator;
use Api\V1\Response;

class FootballSyncController {

    public function source(): void
    {
        $source = ExternalSource::wordpress();
        if (!$source) {
            Response::error('Sorgente WordPress non configurata', 404);
        }

        Response::ok(ExternalSource::publicData($source));
    }

    public function updateSource(): void
    {
        $source = ExternalSource::wordpress();
        if (!$source) {
            Response::error('Sorgente WordPress non configurata', 404);
        }

        $payload = Request::json();
        $errors = FootballSyncSourceValidator::validate($payload, !empty($source['api_key_encrypted']));
        if ($errors) {
            Response::error('Validazione fallita', 422, $errors);
        }

        ExternalSource::updateWordpress($payload);
        Response::ok(ExternalSource::publicData(ExternalSource::wordpress()));
    }

    public function test(): void
    {
        $source = ExternalSource::wordpress();
        if (!$source) {
            Response::error('Sorgente WordPress non configurata', 404);
        }

        try {
            $info = FootballSyncImportService::testConnection($source);
            ExternalSource::recordConnectionStatus((int)$source['id'], 'success', 'Connessione verificata.');
            Response::ok(['connected' => true, 'info' => $info]);
        } catch (\Throwable $e) {
            ExternalSource::recordConnectionStatus((int)$source['id'], 'failed', $e->getMessage());
            Response::error($e->getMessage(), 502);
        }
    }

    public function run(): void
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }
        if (function_exists('ignore_user_abort')) {
            @ignore_user_abort(true);
        }

        $source = ExternalSource::wordpress();
        if (!$source) {
            Response::error('Sorgente WordPress non configurata', 404);
        }

        $payload = Request::json();
        $mode = (string)($payload['mode'] ?? 'incremental');
        if (!in_array($mode, ['full', 'incremental'], true)) {
            Response::error('Modalita sync non valida', 422, ['mode' => 'Valore non valido']);
        }

        $user = JwtMiddleware::user();
        try {
            Response::ok(FootballSyncImportService::run(
                $source,
                $mode,
                isset($user->uid) ? (int)$user->uid : null
            ));
        } catch (\Throwable $e) {
            Response::error($e->getMessage(), 502);
        }
    }

    public function runs(): void
    {
        Response::ok(SyncRun::all((int)($_GET['limit'] ?? 50)));
    }

    public function progress(): void
    {
        $source = ExternalSource::wordpress();
        if (!$source) {
            Response::error('Sorgente WordPress non configurata', 404);
        }

        Response::ok([
            'run' => SyncRun::activeProgress((int)$source['id'])
        ]);
    }

    public function runItems(): void
    {
        $runId = (int)($_GET['run_id'] ?? 0);
        if ($runId <= 0) {
            Response::error('ID esecuzione mancante', 400);
        }

        Response::ok(SyncRun::items($runId));
    }

    public function overrides(): void
    {
        $source = ExternalSource::wordpress();
        if (!$source) {
            Response::error('Sorgente WordPress non configurata', 404);
        }

        Response::ok([
            'items' => ExternalSyncOverride::all((int)$source['id']),
            'seasons' => Season::all()
        ]);
    }

    public function updateOverrides(): void
    {
        $source = ExternalSource::wordpress();
        if (!$source) {
            Response::error('Sorgente WordPress non configurata', 404);
        }

        $payload = Request::json();
        $errors = FootballSyncOverrideValidator::validate($payload);
        if ($errors) {
            Response::error('Validazione fallita', 422, $errors);
        }

        try {
            ExternalSyncOverride::sync((int)$source['id'], $payload['items']);
            Response::ok([
                'items' => ExternalSyncOverride::all((int)$source['id']),
                'seasons' => Season::all()
            ]);
        } catch (\Throwable $e) {
            Response::error($e->getMessage(), 422);
        }
    }
}
