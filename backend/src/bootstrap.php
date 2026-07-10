<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';
require_once __DIR__ . '/Response.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/services.php';
require_once __DIR__ . '/Auth.php';

$config = require dirname(__DIR__) . '/config/app.php';
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if ($origin !== '' && in_array($origin, $config['cors_origins'], true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
    header('Vary: Origin');
}

header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

if (requestMethod() === 'OPTIONS') {
    http_response_code(204);
    exit;
}

set_exception_handler(static function (Throwable $exception): void {
    $message = $exception->getMessage();
    $status = 500;

    if ($exception instanceof InvalidArgumentException) {
        $status = 400;
    }

    Response::json(['error' => $message], $status);
});

try {
    expireReservations(getDb());
} catch (Throwable) {
    // Database may not be installed yet.
}
