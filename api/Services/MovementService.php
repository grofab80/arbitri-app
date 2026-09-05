<?php
namespace Api\Services;

use Api\Models\Movement;
use Api\Models\Season;
use Api\Validators\MovementValidator;

class MovementService {

    public static function all(array $filters = []): array
    {
        return Movement::all($filters);
    }

    public static function find(int $id): ?array
    {
        return Movement::find($id);
    }

    public static function kpi(): array
    {
        return Movement::kpi();
    }

    public static function create(array $payload): array
    {
        if (!Season::currentId()) {
            return [
                'success' => false,
                'reason' => 'missing_current_season'
            ];
        }

        $errors = MovementValidator::validate($payload);

        if (!empty($errors)) {
            return [
                'success' => false,
                'reason' => 'validation',
                'errors' => $errors
            ];
        }

        return [
            'success' => true,
            'id' => Movement::create($payload)
        ];
    }

    public static function update(int $id, array $payload): array
    {
        if (!Movement::find($id)) {
            return [
                'success' => false,
                'reason' => 'not_found'
            ];
        }

        $errors = MovementValidator::validate($payload);

        if (!empty($errors)) {
            return [
                'success' => false,
                'reason' => 'validation',
                'errors' => $errors
            ];
        }

        Movement::update($id, $payload);

        return ['success' => true];
    }

    public static function delete(int $id): array
    {
        if (!Movement::find($id)) {
            return [
                'success' => false,
                'reason' => 'not_found'
            ];
        }

        Movement::delete($id);

        return ['success' => true];
    }
}
