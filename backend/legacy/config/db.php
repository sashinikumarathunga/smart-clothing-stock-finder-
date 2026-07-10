<?php

declare(strict_types=1);

$dbHost = 'localhost';
$dbName = 'smart_stock_finder';
$dbUser = 'root';
$dbPass = '';

function getDb(): PDO
{
    global $dbHost, $dbName, $dbUser, $dbPass;

    static $pdo = null;

    if ($pdo === null) {
        $pdo = new PDO(
            sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $dbHost, $dbName),
            $dbUser,
            $dbPass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => true,
            ]
        );
    }

    return $pdo;
}
