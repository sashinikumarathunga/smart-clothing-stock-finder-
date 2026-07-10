<?php

declare(strict_types=1);

function handleHealth(): never
{
    $checks = [];
    $ok = true;

    try {
        $db = getDb();
        $checks[] = ['label' => 'Database connection', 'ok' => true, 'detail' => 'Connected'];

        $tables = [
            'branches', 'users', 'suppliers', 'products', 'customers', 'reservations',
            'sales', 'sale_items', 'purchase_orders', 'purchase_order_items', 'returns_exchanges', 'api_tokens',
        ];

        foreach ($tables as $table) {
            $stmt = $db->query("SHOW TABLES LIKE '{$table}'");
            $exists = $stmt->fetch() !== false;
            $checks[] = ['label' => "Table: {$table}", 'ok' => $exists, 'detail' => $exists ? 'OK' : 'Missing'];
            if (!$exists) {
                $ok = false;
            }
        }

        $userCount = (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $seedOk = $userCount > 0;
        $checks[] = ['label' => 'Seed users', 'ok' => $seedOk, 'detail' => $seedOk ? "{$userCount} users" : 'Import seed.sql'];
        if (!$seedOk) {
            $ok = false;
        }
    } catch (Throwable $exception) {
        $ok = false;
        $checks[] = ['label' => 'Database connection', 'ok' => false, 'detail' => $exception->getMessage()];
    }

    Response::json(['ok' => $ok, 'checks' => $checks], $ok ? 200 : 503);
}

function handleAuthLogin(): never
{
    $body = requestJsonBody();
    $username = bodyString($body, 'username');
    $password = bodyString($body, 'password');

    if ($username === '' || $password === '') {
        Response::error('Username and password are required.', 400);
    }

    $result = Auth::login($username, $password);

    if ($result === null) {
        Response::error('Invalid username or password.', 401);
    }

    Response::json($result);
}

function handleAuthLogout(): never
{
    Auth::requireAuth();
    Auth::logout();
    Response::json(['message' => 'Logged out']);
}

function handleAuthMe(): never
{
    $user = Auth::requireAuth();
    Response::json(['user' => $user]);
}

function handleDashboard(): never
{
    $user = Auth::requireAuth();
    $db = getDb();
    $role = $user['role'];

    $stats = [
        'branches' => 0,
        'products' => 0,
        'sales' => 0,
        'staff' => 0,
        'low_stock' => 0,
        'today_sales' => 0.0,
    ];

    $recentSales = [];
    $lowStockAlerts = [];

    $lowStockSql = 'SELECT COUNT(*) FROM products p
                    INNER JOIN branches b ON b.id = p.branch_id
                    WHERE p.low_stock_alert_enabled = 1 AND p.quantity < b.low_stock_threshold';

    if ($role === 'owner') {
        $stats['branches'] = (int) $db->query('SELECT COUNT(*) FROM branches')->fetchColumn();
        $stats['products'] = (int) $db->query('SELECT COUNT(*) FROM products')->fetchColumn();
        $stats['sales'] = (int) $db->query('SELECT COUNT(*) FROM sales')->fetchColumn();
        try {
            $stats['low_stock'] = (int) $db->query($lowStockSql)->fetchColumn();
        } catch (PDOException) {
            $stats['low_stock'] = 0;
        }
        $lowStockAlerts = getLowStockAlerts($db, null, $role);

        $recentSales = $db->query(
            'SELECT s.id, s.total, s.payment_method, s.created_at, b.name AS branch_name, u.full_name AS cashier_name
             FROM sales s
             INNER JOIN branches b ON b.id = s.branch_id
             INNER JOIN users u ON u.id = s.cashier_id
             ORDER BY s.created_at DESC
             LIMIT 5'
        )->fetchAll();
    }

    if ($role === 'branch_admin') {
        $branchId = Auth::requireUserBranchId($user);
        $stmt = $db->prepare('SELECT COUNT(*) FROM users WHERE branch_id = :branch_id AND role != "branch_admin"');
        $stmt->execute(['branch_id' => $branchId]);
        $stats['staff'] = (int) $stmt->fetchColumn();

        $stmt = $db->prepare('SELECT COUNT(*) FROM products WHERE branch_id = :branch_id');
        $stmt->execute(['branch_id' => $branchId]);
        $stats['products'] = (int) $stmt->fetchColumn();

        $stmt = $db->prepare('SELECT COUNT(*) FROM sales WHERE branch_id = :branch_id');
        $stmt->execute(['branch_id' => $branchId]);
        $stats['sales'] = (int) $stmt->fetchColumn();

        try {
            $stmt = $db->prepare($lowStockSql . ' AND p.branch_id = :branch_id');
            $stmt->execute(['branch_id' => $branchId]);
            $stats['low_stock'] = (int) $stmt->fetchColumn();
        } catch (PDOException) {
            $stats['low_stock'] = 0;
        }
        $lowStockAlerts = getLowStockAlerts($db, $branchId, $role);

        $stmt = $db->prepare(
            'SELECT s.id, s.total, s.payment_method, s.created_at, u.full_name AS cashier_name
             FROM sales s
             INNER JOIN users u ON u.id = s.cashier_id
             WHERE s.branch_id = :branch_id
             ORDER BY s.created_at DESC
             LIMIT 5'
        );
        $stmt->execute(['branch_id' => $branchId]);
        $recentSales = $stmt->fetchAll();
    }

    if ($role === 'storekeeper') {
        $branchId = Auth::requireUserBranchId($user);
        $stmt = $db->prepare('SELECT COUNT(*) FROM products WHERE branch_id = :branch_id');
        $stmt->execute(['branch_id' => $branchId]);
        $stats['products'] = (int) $stmt->fetchColumn();

        try {
            $stmt = $db->prepare($lowStockSql . ' AND p.branch_id = :branch_id');
            $stmt->execute(['branch_id' => $branchId]);
            $stats['low_stock'] = (int) $stmt->fetchColumn();
        } catch (PDOException) {
            $stats['low_stock'] = 0;
        }
        $lowStockAlerts = getLowStockAlerts($db, $branchId, $role);
    }

    if ($role === 'cashier') {
        $branchId = Auth::requireUserBranchId($user);
        $stmt = $db->prepare(
            'SELECT COUNT(*) FROM sales
             WHERE branch_id = :branch_id AND cashier_id = :cashier_id AND DATE(created_at) = CURDATE()'
        );
        $stmt->execute(['branch_id' => $branchId, 'cashier_id' => $user['id']]);
        $stats['sales'] = (int) $stmt->fetchColumn();

        $stmt = $db->prepare(
            'SELECT COALESCE(SUM(total), 0) FROM sales
             WHERE branch_id = :branch_id AND cashier_id = :cashier_id AND DATE(created_at) = CURDATE()'
        );
        $stmt->execute(['branch_id' => $branchId, 'cashier_id' => $user['id']]);
        $stats['today_sales'] = (float) $stmt->fetchColumn();
    }

    Response::json([
        'role' => $role,
        'stats' => $stats,
        'recent_sales' => $recentSales,
        'low_stock_alerts' => $lowStockAlerts,
    ]);
}
