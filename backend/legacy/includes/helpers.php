<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function formatMoney(float|int|string $amount): string
{
    return 'Rs. ' . number_format((float) $amount, 2);
}

function roleLabel(string $role): string
{
    return match ($role) {
        'owner' => 'Owner',
        'branch_admin' => 'Branch Admin',
        'storekeeper' => 'Storekeeper',
        'sales_assistant' => 'Sales Assistant',
        'cashier' => 'Cashier',
        default => ucfirst(str_replace('_', ' ', $role)),
    };
}

function appBasePath(): string
{
    static $base = null;

    if ($base !== null) {
        return $base;
    }

    $docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '');
    $projectRoot = str_replace('\\', '/', realpath(dirname(__DIR__)) ?: '');

    if ($docRoot !== '' && $projectRoot !== '' && str_starts_with($projectRoot, $docRoot)) {
        $base = rtrim(substr($projectRoot, strlen($docRoot)), '/');
        return $base;
    }

    $base = '';
    return $base;
}

function url(string $path = ''): string
{
    $base = appBasePath();

    if ($path === '') {
        return $base === '' ? '/' : $base . '/';
    }

    return ($base === '' ? '' : $base) . '/' . ltrim($path, '/');
}

function isPost(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function postString(string $key): string
{
    return trim((string) ($_POST[$key] ?? ''));
}

function postInt(string $key): int
{
    return (int) ($_POST[$key] ?? 0);
}

function postFloat(string $key): float
{
    return (float) ($_POST[$key] ?? 0);
}
