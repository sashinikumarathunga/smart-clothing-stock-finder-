<?php

declare(strict_types=1);

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

function requestMethod(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function requestPath(): string
{
    $path = $_SERVER['PATH_INFO'] ?? '';

    if ($path === '' || $path === '/') {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $path = $uri;

        // Strip the front-controller subdirectory only when the server exposes a
        // real .php entry point (e.g. Apache at /app/backend/public/index.php).
        // The PHP built-in server rewrites SCRIPT_NAME to the requested path, so
        // we must not strip in that case.
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

        if (str_ends_with($script, '.php')) {
            $base = rtrim(dirname($script), '/');

            if ($base !== '' && $base !== '/' && str_starts_with($uri, $base . '/')) {
                $path = substr($uri, strlen($base));
            }
        }
    }

    $path = '/' . trim($path, '/');

    return $path === '/' ? '/' : rtrim($path, '/');
}

function requestJsonBody(): array
{
    $raw = file_get_contents('php://input');

    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $decoded = json_decode($raw, true);

    if (!is_array($decoded)) {
        Response::error('Invalid JSON body', 400);
    }

    return $decoded;
}

function bodyString(array $body, string $key): string
{
    return trim((string) ($body[$key] ?? ''));
}

function bodyInt(array $body, string $key): int
{
    return (int) ($body[$key] ?? 0);
}

function bodyFloat(array $body, string $key): float
{
    return (float) ($body[$key] ?? 0);
}

function queryString(string $key, string $default = ''): string
{
    return trim((string) ($_GET[$key] ?? $default));
}

function queryInt(string $key, int $default = 0): int
{
    return isset($_GET[$key]) ? (int) $_GET[$key] : $default;
}

function normalizeUserRow(array $user): array
{
    unset($user['password_hash']);
    $user['id'] = (int) $user['id'];

    if (array_key_exists('branch_id', $user) && $user['branch_id'] !== null) {
        $user['branch_id'] = (int) $user['branch_id'];
    }

    if (isset($user['is_active'])) {
        $user['is_active'] = (int) $user['is_active'];
    }

    return $user;
}

function formatReportCell(mixed $value): string
{
    if ($value === null) {
        return '';
    }

    if (is_int($value) || is_float($value)) {
        return str_contains((string) $value, '.') ? number_format((float) $value, 2) : (string) $value;
    }

    return (string) $value;
}
