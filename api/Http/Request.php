<?php
namespace Api\Http;

use Api\V1\Response;

class Request {

    public static function json(): array
    {
        $raw = file_get_contents('php://input');

        if (trim($raw) === '') {
            return [];
        }

        $data = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            Response::error('JSON non valido', 400);
        }

        return $data;
    }
}
