<?php
namespace Api\Validators;

use Api\Models\Permission;

class PermissionValidator {

    public static function syncProfile(array $payload, array $profile): array
    {
        $errors = [];

        if (!isset($payload['permission_ids']) || !is_array($payload['permission_ids'])) {
            $errors['permission_ids'] = 'Elenco permessi obbligatorio';
            return $errors;
        }

        foreach ($payload['permission_ids'] as $permissionId) {
            if (!is_numeric($permissionId) || (int)$permissionId <= 0) {
                $errors['permission_ids'] = 'Permesso non valido';
                break;
            }
        }

        if (empty($errors) && !Permission::permissionIdsExist($payload['permission_ids'])) {
            $errors['permission_ids'] = 'Uno o piu permessi non esistono';
        }

        if (
            empty($errors)
            && ($profile['code'] ?? '') !== 'admin'
            && Permission::containsDeletePermission($payload['permission_ids'])
        ) {
            $errors['permission_ids'] = 'I permessi di eliminazione possono essere assegnati solo al profilo admin';
        }

        return $errors;
    }
}
