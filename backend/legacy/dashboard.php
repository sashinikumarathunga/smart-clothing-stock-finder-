<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

requireLogin();

$user = currentUser();
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
    $branchId = requireUserBranchId();
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
    $branchId = requireUserBranchId();
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
    $branchId = requireUserBranchId();
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

$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
        <main class="col-md-9 col-lg-10 p-4">
            <?php $flash = getFlash(); if ($flash !== null): ?>
                <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
            <?php endif; ?>

            <h1 class="h3 mb-4">Dashboard</h1>

            <?php if ($role === 'owner'): ?>
                <div class="row g-3 mb-4">
                    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><div class="text-muted">Branches</div><div class="h4 mb-0"><?= $stats['branches'] ?></div></div></div></div>
                    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><div class="text-muted">Products</div><div class="h4 mb-0"><?= $stats['products'] ?></div></div></div></div>
                    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><div class="text-muted">Sales</div><div class="h4 mb-0"><?= $stats['sales'] ?></div></div></div></div>
                    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><div class="text-muted">Low Stock Items</div><div class="h4 mb-0 text-danger"><?= $stats['low_stock'] ?></div></div></div></div>
                </div>
            <?php endif; ?>

            <?php if ($role === 'branch_admin'): ?>
                <div class="row g-3 mb-4">
                    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><div class="text-muted">Staff</div><div class="h4 mb-0"><?= $stats['staff'] ?></div></div></div></div>
                    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><div class="text-muted">Products</div><div class="h4 mb-0"><?= $stats['products'] ?></div></div></div></div>
                    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><div class="text-muted">Sales</div><div class="h4 mb-0"><?= $stats['sales'] ?></div></div></div></div>
                    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><div class="text-muted">Low Stock</div><div class="h4 mb-0 text-danger"><?= $stats['low_stock'] ?></div></div></div></div>
                </div>
            <?php endif; ?>

            <?php if ($role === 'storekeeper'): ?>
                <div class="row g-3 mb-4">
                    <div class="col-md-4"><div class="card stat-card"><div class="card-body"><div class="text-muted">Branch Products</div><div class="h4 mb-0"><?= $stats['products'] ?></div></div></div></div>
                    <div class="col-md-4"><div class="card stat-card"><div class="card-body"><div class="text-muted">Low Stock Items</div><div class="h4 mb-0 text-danger"><?= $stats['low_stock'] ?></div></div></div></div>
                </div>
            <?php endif; ?>

            <?php if ($role === 'sales_assistant'): ?>
                <div class="alert alert-info">
                    Use <strong>Stock Search</strong> to find items across all branches and suggest alternatives when out of stock.
                </div>
                <a href="<?= e(url('search/index.php')) ?>" class="btn btn-primary me-2">Open Stock Search</a>
                <a href="<?= e(url('reservations/index.php')) ?>" class="btn btn-outline-primary">Manage Reservations</a>
            <?php endif; ?>

            <?php if ($role === 'cashier'): ?>
                <div class="row g-3 mb-4">
                    <div class="col-md-4"><div class="card stat-card"><div class="card-body"><div class="text-muted">Today's Sales</div><div class="h4 mb-0"><?= $stats['sales'] ?></div></div></div></div>
                    <div class="col-md-4"><div class="card stat-card"><div class="card-body"><div class="text-muted">Today's Revenue</div><div class="h4 mb-0"><?= e(formatMoney($stats['today_sales'])) ?></div></div></div></div>
                </div>
                <a href="<?= e(url('pos/index.php')) ?>" class="btn btn-primary">Open POS</a>
            <?php endif; ?>

            <?php if ($lowStockAlerts !== [] && in_array($role, ['owner', 'branch_admin', 'storekeeper'], true)): ?>
                <div class="alert alert-warning">
                    <strong>Low Stock Alert:</strong> <?= count($lowStockAlerts) ?> item(s) below threshold.
                </div>
                <div class="card mb-4">
                    <div class="card-header">Low Stock Items</div>
                    <div class="table-responsive">
                        <table class="table mb-0 table-sm">
                            <thead><tr><th>Branch</th><th>Product</th><th>Style</th><th>Qty</th><th>Threshold</th></tr></thead>
                            <tbody>
                                <?php foreach ($lowStockAlerts as $alert): ?>
                                    <tr>
                                        <td><?= e($alert['branch_name']) ?></td>
                                        <td><?= e($alert['name']) ?></td>
                                        <td><?= e($alert['style_code']) ?></td>
                                        <td class="text-danger fw-semibold"><?= (int) $alert['quantity'] ?></td>
                                        <td><?= (int) $alert['low_stock_threshold'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($recentSales !== []): ?>
                <div class="card mt-4">
                    <div class="card-header">Recent Sales</div>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <?php if ($role === 'owner'): ?><th>Branch</th><?php endif; ?>
                                    <th>Cashier</th>
                                    <th>Total</th>
                                    <th>Payment</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentSales as $sale): ?>
                                    <tr>
                                        <td>#<?= (int) $sale['id'] ?></td>
                                        <?php if ($role === 'owner'): ?><td><?= e($sale['branch_name']) ?></td><?php endif; ?>
                                        <td><?= e($sale['cashier_name']) ?></td>
                                        <td><?= e(formatMoney((float) $sale['total'])) ?></td>
                                        <td><?= e(ucfirst($sale['payment_method'])) ?></td>
                                        <td><?= e($sale['created_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
