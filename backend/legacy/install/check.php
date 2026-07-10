<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

$checks = [];
$ok = true;

try {
    $db = getDb();
    $checks[] = ['Database connection', true, 'Connected successfully'];

    $tables = [
        'branches', 'users', 'suppliers', 'products', 'customers', 'reservations',
        'sales', 'sale_items', 'purchase_orders', 'purchase_order_items', 'returns_exchanges',
    ];

    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '{$table}'");
        $exists = $stmt->fetch() !== false;
        $checks[] = ["Table: {$table}", $exists, $exists ? 'OK' : 'Missing — import sql/schema.sql'];
        if (!$exists) {
            $ok = false;
        }
    }

    $userCount = (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $checks[] = ['Seed users', $userCount > 0, $userCount > 0 ? "{$userCount} users found" : 'Import sql/seed.sql'];
    if ($userCount === 0) {
        $ok = false;
    }
} catch (Throwable $exception) {
    $ok = false;
    $checks[] = ['Database connection', false, $exception->getMessage()];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Setup Check</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width: 720px;">
    <h1 class="h3 mb-4">Smart Stock Finder — Setup Check</h1>
    <div class="alert alert-<?= $ok ? 'success' : 'danger' ?>">
        <?= $ok ? 'All checks passed. You can open login.php and sign in.' : 'Setup incomplete. Fix the issues below.' ?>
    </div>
    <div class="card">
        <ul class="list-group list-group-flush">
            <?php foreach ($checks as [$label, $passed, $detail]): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="badge bg-<?= $passed ? 'success' : 'danger' ?>"><?= htmlspecialchars($detail, ENT_QUOTES, 'UTF-8') ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="mt-3">
        <a href="../login.php" class="btn btn-primary">Go to Login</a>
    </div>
</div>
</body>
</html>
