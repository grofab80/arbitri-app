<?php
namespace Api\V1;

class Response {

    public static function ok($data = []) {
        self::json([
            'success' => true,
            'data' => $data
        ]);
    }

    public static function error(
        string $msg,
        int $code = 400,
        array $errors = []
    ) {
        http_response_code($code);

        $response = [
            'success' => false,
            'error'   => $msg
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        self::json($response);
    }

    private static function json(array $response): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
