<?php
namespace Api\Controllers;

use Api\Http\Request;
use Api\Models\Permission;
use Api\Validators\PermissionValidator;
use Api\V1\Response;

class PermissionController {

    public function index(): void
    {
        Response::ok([
            'profiles' => Permission::profiles(),
            'permissions' => Permission::permissions(),
            'assignments' => Permission::assignments()
        ]);
    }

    public function syncProfile(): void
    {
        $profileId = (int)($_GET['profile_id'] ?? 0);

        if (!$profileId) {
            Response::error('ID profilo mancante', 400);
        }

        $profile = Permission::profile($profileId);

        if (!$profile) {
            Response::error('Profilo non trovato', 404);
        }

        if (($profile['code'] ?? '') === 'admin') {
            Response::error('Il profilo admin ha sempre tutti i permessi e non puo essere modificato', 409);
        }

        $payload = Request::json();
        $errors = PermissionValidator::syncProfile($payload, $profile);

        if (!empty($errors)) {
            Response::error('Validazione fallita', 422, $errors);
        }

        Permission::syncProfilePermissions($profileId, $payload['permission_ids']);

        Response::ok(['updated' => true]);
    }
}
