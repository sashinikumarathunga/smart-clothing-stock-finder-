<?php

declare(strict_types=1);

const ROLES = [
    'owner',
    'branch_admin',
    'storekeeper',
    'sales_assistant',
    'cashier',
];

final class Auth
{
    private static ?array $currentUser = null;
    private static bool $resolved = false;

    public static function currentUser(): ?array
    {
        if (!self::$resolved) {
            self::$resolved = true;
            self::$currentUser = self::resolveUserFromToken();
        }

        return self::$currentUser;
    }

    public static function requireAuth(): array
    {
        $user = self::currentUser();

        if ($user === null) {
            Response::unauthorized();
        }

        return $user;
    }

    public static function requireRole(array $roles): array
    {
        $user = self::requireAuth();

        if (!in_array($user['role'], $roles, true)) {
            Response::forbidden('You do not have permission to access this resource.');
        }

        return $user;
    }

    public static function userBranchId(?array $user = null): ?int
    {
        $user ??= self::currentUser();

        if ($user === null || $user['branch_id'] === null) {
            return null;
        }

        return (int) $user['branch_id'];
    }

    public static function requireUserBranchId(?array $user = null): int
    {
        $branchId = self::userBranchId($user);

        if ($branchId === null) {
            Response::forbidden('Your account is not assigned to a branch.');
        }

        return $branchId;
    }

    public static function canManageRole(array $user, string $targetRole): bool
    {
        if ($user['role'] === 'owner') {
            return $targetRole === 'branch_admin';
        }

        if ($user['role'] === 'branch_admin') {
            return in_array($targetRole, ['storekeeper', 'sales_assistant', 'cashier'], true);
        }

        return false;
    }

    public static function login(string $username, string $password): ?array
    {
        $db = getDb();
        $stmt = $db->prepare(
            'SELECT u.*, b.name AS branch_name
             FROM users u
             LEFT JOIN branches b ON b.id = u.branch_id
             WHERE u.username = :username AND u.is_active = 1
             LIMIT 1'
        );
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user === false || !password_verify($password, $user['password_hash'])) {
            return null;
        }

        $user = normalizeUserRow($user);
        $token = self::issueToken((int) $user['id']);

        return ['token' => $token, 'user' => $user];
    }

    public static function logout(?string $token = null): void
    {
        $token ??= self::extractBearerToken();

        if ($token === null || $token === '') {
            return;
        }

        $db = getDb();
        $db->prepare('DELETE FROM api_tokens WHERE token = :token')->execute(['token' => $token]);
    }

    private static function issueToken(int $userId): string
    {
        $config = require dirname(__DIR__) . '/config/app.php';
        $ttlHours = (int) ($config['token_ttl_hours'] ?? 24);
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + ($ttlHours * 3600));

        $db = getDb();
        $db->prepare(
            'INSERT INTO api_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)'
        )->execute([
            'user_id' => $userId,
            'token' => $token,
            'expires_at' => $expiresAt,
        ]);

        return $token;
    }

    private static function extractBearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';

        if ($header === '' && function_exists('getallheaders')) {
            $headers = getallheaders();
            $header = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }

        if (preg_match('/^Bearer\s+(\S+)$/i', $header, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    private static function resolveUserFromToken(): ?array
    {
        $token = self::extractBearerToken();

        if ($token === null || $token === '') {
            return null;
        }

        $db = getDb();

        try {
            $stmt = $db->prepare(
                'SELECT u.*, b.name AS branch_name
                 FROM api_tokens t
                 INNER JOIN users u ON u.id = t.user_id
                 LEFT JOIN branches b ON b.id = u.branch_id
                 WHERE t.token = :token AND t.expires_at > NOW() AND u.is_active = 1
                 LIMIT 1'
            );
            $stmt->execute(['token' => $token]);
            $user = $stmt->fetch();
        } catch (PDOException) {
            return null;
        }

        if ($user === false) {
            return null;
        }

        return normalizeUserRow($user);
    }
}
