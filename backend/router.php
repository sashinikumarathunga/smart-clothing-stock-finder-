<?php

declare(strict_types=1);

// PHP built-in server router: php -S localhost:8000 router.php
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = __DIR__ . '/public' . $uri;

if ($uri !== '/' && is_file($file)) {
    return false;
}

require __DIR__ . '/public/index.php';
