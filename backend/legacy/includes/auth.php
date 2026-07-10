<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/services.php';

try {
    expireReservations(getDb());
} catch (Throwable) {
    // Database may not be installed yet during first setup.
}

const ROLES = [
    'owner',
    'branch_admin',
    'storekeeper',
    'sales_assistant',
    'cashier',
];

function currentUser(): ?array
{
    if (!isset($_SESSION['user'])) {
        return null;
    }

    return $_SESSION['user'];
}

function requireLogin(): void
{
    if (currentUser() === null) {
        redirect(url('login.php'));
    }
}

function requireRole(array $roles): void
{
    requireLogin();

    $user = currentUser();
    if ($user === null || !in_array($user['role'], $roles, true)) {
        setFlash('danger', 'You do not have permission to access that page.');
        redirect(url('dashboard.php'));
    }
}

function loginUser(string $username, string $password): bool
{
    $stmt = getDb()->prepare(
        'SELECT u.*, b.name AS branch_name
         FROM users u
         LEFT JOIN branches b ON b.id = u.branch_id
         WHERE u.username = :username AND u.is_active = 1
         LIMIT 1'
    );
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if ($user === false || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    unset($user['password_hash']);
    $user['id'] = (int) $user['id'];
    if ($user['branch_id'] !== null) {
        $user['branch_id'] = (int) $user['branch_id'];
    }
    $_SESSION['user'] = $user;

    return true;
}

function logoutUser(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            (bool) $params['secure'],
            (bool) $params['httponly']
        );
    }

    session_destroy();
}

function userBranchId(): ?int
{
    $user = currentUser();

    if ($user === null || $user['branch_id'] === null) {
        return null;
    }

    return (int) $user['branch_id'];
}

function requireUserBranchId(): int
{
    $branchId = userBranchId();

    if ($branchId === null) {
        setFlash('danger', 'Your account is not assigned to a branch.');
        redirect(url('dashboard.php'));
    }

    return $branchId;
}

function canManageRole(string $targetRole): bool
{
    $user = currentUser();

    if ($user === null) {
        return false;
    }

    if ($user['role'] === 'owner') {
        return $targetRole === 'branch_admin';
    }

    if ($user['role'] === 'branch_admin') {
        return in_array($targetRole, ['storekeeper', 'sales_assistant', 'cashier'], true);
    }

    return false;
}
