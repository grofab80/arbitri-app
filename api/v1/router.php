<?php
require_once __DIR__ . '/bootstrap.php';

use Api\V1\Response;
use Api\Middleware\JwtMiddleware;

$routes = require __DIR__ . '/routes.php';

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $path   = '/' . trim($_GET['path'] ?? '', '/');

    $key = "$method $path";

    /* ---------- ROUTE ESISTENTE? ---------- */
    if (!isset($routes[$key])) {
        Response::error('Endpoint non trovato', 404);
    }

    $route = $routes[$key];

    /* ---------- AUTH ---------- */
    if (!empty($route['auth'])) {
        JwtMiddleware::check();
    }

    /* ---------- AUTHORIZATION ---------- */
    if (!empty($route['roles'])) {
        JwtMiddleware::authorize($route['roles']);
    }

    if (!empty($route['permissions'])) {
        JwtMiddleware::authorizePermissions($route['permissions']);
    }

    /* ---------- HANDLER ---------- */
    $handler = $route['handler'];

    if (is_callable($handler)) {
        $handler();
        exit;
    }

    if (is_array($handler)) {
        [$class, $method] = $handler;
        (new $class)->$method();
        exit;
    }

    Response::error('Handler non valido', 500);
} catch (\Throwable $e) {
    error_log(sprintf(
        '[API ERROR] %s in %s:%d',
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    ));

    Response::error('Errore interno del server', 500);
}
