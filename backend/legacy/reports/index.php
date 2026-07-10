<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

requireRole(['owner', 'branch_admin']);

$user = currentUser();
$db = getDb();
$isOwner = $user['role'] === 'owner';

$reportType = (string) ($_GET['report'] ?? 'fast_moving');
$branchId = $isOwner ? (int) ($_GET['branch_id'] ?? 0) : requireUserBranchId();
$dateFrom = trim((string) ($_GET['date_from'] ?? ''));
$dateTo = trim((string) ($_GET['date_to'] ?? ''));

if ($dateFrom === '') {
    $dateFrom = date('Y-m-01');
}
if ($dateTo === '') {
    $dateTo = date('Y-m-d');
}

$branches = $isOwner ? $db->query('SELECT id, name FROM branches ORDER BY name')->fetchAll() : [];
$rows = [];
$title = 'Reports';
$reportError = '';

$params = [
    'date_from' => $dateFrom . ' 00:00:00',
    'date_to' => $dateTo . ' 23:59:59',
];

if ($branchId > 0) {
    $params['branch_id'] = $branchId;
}

$branchFilterAlias = [
    's' => $branchId > 0 ? ' AND s.branch_id = :branch_id' : '',
    'p' => $branchId > 0 ? ' AND p.branch_id = :branch_id' : '',
    'c' => $branchId > 0 ? ' AND c.branch_id = :branch_id' : '',
    're' => $branchId > 0 ? ' AND re.branch_id = :branch_id' : '',
    'po' => $branchId > 0 ? ' AND po.branch_id = :branch_id' : '',
];

switch ($reportType) {
    case 'slow_moving':
        $title = 'Slow Moving Items';
        $params['branch_filter'] = $branchId;
        unset($params['branch_id']);
        $sql = 'SELECT p.name, p.style_code, b.name AS branch_name,
                       (SELECT COALESCE(SUM(si.qty), 0)
                        FROM sale_items si
                        INNER JOIN sales s ON s.id = si.sale_id
                        WHERE si.product_id = p.id
                          AND s.created_at BETWEEN :date_from AND :date_to) AS sold_qty,
                       p.quantity AS stock_qty
                FROM products p
                INNER JOIN branches b ON b.id = p.branch_id
                WHERE (:branch_filter = 0 OR p.branch_id = :branch_filter)
                HAVING sold_qty <= 2
                ORDER BY sold_qty ASC, p.name
                LIMIT 50';
        break;

    case 'stock_levels':
        $title = 'Stock Availability by Branch';
        $sql = 'SELECT b.name AS branch_name, p.name, p.style_code, p.size, p.color, p.quantity, p.location_in_store
                FROM products p
                INNER JOIN branches b ON b.id = p.branch_id
                WHERE 1 = 1' . $branchFilterAlias['p'] . '
                ORDER BY b.name, p.name';
        if ($branchId <= 0) {
            unset($params['branch_id']);
        }
        unset($params['date_from'], $params['date_to']);
        break;

    case 'monthly_sales':
        $title = 'Sales Summary';
        $sql = 'SELECT DATE(s.created_at) AS sale_date, COUNT(*) AS transactions, SUM(s.total) AS revenue
                FROM sales s
                WHERE s.created_at BETWEEN :date_from AND :date_to' . $branchFilterAlias['s'] . '
                GROUP BY DATE(s.created_at)
                ORDER BY sale_date DESC';
        break;

    case 'returns_summary':
        $title = 'Returns & Exchanges Summary';
        $sql = 'SELECT re.type, COUNT(*) AS total_count, SUM(re.qty) AS total_qty, SUM(re.price_difference) AS total_price_diff
                FROM returns_exchanges re
                WHERE re.created_at BETWEEN :date_from AND :date_to' . $branchFilterAlias['re'] . '
                GROUP BY re.type';
        break;

    case 'loyalty_stats':
        $title = 'Customer Loyalty Statistics';
        $sql = 'SELECT c.full_name, c.phone, c.loyalty_points,
                       COALESCE(SUM(s.loyalty_points_earned), 0) AS points_earned,
                       COALESCE(SUM(s.loyalty_points_redeemed), 0) AS points_redeemed
                FROM customers c
                LEFT JOIN sales s ON s.customer_id = c.id AND s.created_at BETWEEN :date_from AND :date_to
                WHERE 1 = 1' . $branchFilterAlias['c'] . '
                GROUP BY c.id, c.full_name, c.phone, c.loyalty_points
                ORDER BY c.loyalty_points DESC';
        break;

    case 'low_stock':
        $title = 'Low Stock Report';
        $sql = 'SELECT b.name AS branch_name, p.name, p.style_code, p.quantity, b.low_stock_threshold
                FROM products p
                INNER JOIN branches b ON b.id = p.branch_id
                WHERE p.low_stock_alert_enabled = 1 AND p.quantity < b.low_stock_threshold' . $branchFilterAlias['p'] . '
                ORDER BY p.quantity ASC';
        if ($branchId <= 0) {
            unset($params['branch_id']);
        }
        unset($params['date_from'], $params['date_to']);
        break;

    case 'supplier_orders':
        $title = 'Supplier Order History';
        $sql = 'SELECT po.id, s.name AS supplier_name, b.name AS branch_name, po.status, po.created_at, po.delivered_at
                FROM purchase_orders po
                INNER JOIN suppliers s ON s.id = po.supplier_id
                INNER JOIN branches b ON b.id = po.branch_id
                WHERE po.created_at BETWEEN :date_from AND :date_to' . $branchFilterAlias['po'] . '
                ORDER BY po.created_at DESC';
        break;

    case 'fast_moving':
    default:
        $title = 'Fast Moving Items';
        $reportType = 'fast_moving';
        $sql = 'SELECT p.name, p.style_code, b.name AS branch_name, SUM(si.qty) AS sold_qty, SUM(si.line_total) AS revenue
                FROM sale_items si
                INNER JOIN sales s ON s.id = si.sale_id
                INNER JOIN products p ON p.id = si.product_id
                INNER JOIN branches b ON b.id = s.branch_id
                WHERE s.created_at BETWEEN :date_from AND :date_to' . $branchFilterAlias['s'] . '
                GROUP BY p.id, p.name, p.style_code, b.name
                ORDER BY sold_qty DESC
                LIMIT 50';
        break;
}

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
} catch (PDOException $exception) {
    $reportError = 'Could not generate report. Import sql/migration_v2.sql if upgrading. ' . $exception->getMessage();
}

$pageTitle = 'Reports';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="col-md-9 col-lg-10 p-4">
            <h1 class="h3 mb-4">Reports</h1>

            <?php if ($reportError !== ''): ?>
                <div class="alert alert-danger"><?= e($reportError) ?></div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-body">
                    <form method="get" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Report</label>
                            <select class="form-select" name="report">
                                <option value="fast_moving" <?= $reportType === 'fast_moving' ? 'selected' : '' ?>>Fast Moving Items</option>
                                <option value="slow_moving" <?= $reportType === 'slow_moving' ? 'selected' : '' ?>>Slow Moving Items</option>
                                <option value="stock_levels" <?= $reportType === 'stock_levels' ? 'selected' : '' ?>>Stock by Branch</option>
                                <option value="low_stock" <?= $reportType === 'low_stock' ? 'selected' : '' ?>>Low Stock</option>
                                <option value="monthly_sales" <?= $reportType === 'monthly_sales' ? 'selected' : '' ?>>Sales Summary</option>
                                <option value="returns_summary" <?= $reportType === 'returns_summary' ? 'selected' : '' ?>>Returns & Exchanges</option>
                                <option value="loyalty_stats" <?= $reportType === 'loyalty_stats' ? 'selected' : '' ?>>Loyalty Statistics</option>
                                <option value="supplier_orders" <?= $reportType === 'supplier_orders' ? 'selected' : '' ?>>Supplier Orders</option>
                            </select>
                        </div>
                        <?php if ($isOwner): ?>
                            <div class="col-md-2">
                                <label class="form-label">Branch</label>
                                <select class="form-select" name="branch_id">
                                    <option value="0">All Branches</option>
                                    <?php foreach ($branches as $branch): ?>
                                        <option value="<?= (int) $branch['id'] ?>" <?= $branchId === (int) $branch['id'] ? 'selected' : '' ?>><?= e($branch['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        <div class="col-md-2">
                            <label class="form-label">From</label>
                            <input type="date" class="form-control" name="date_from" value="<?= e($dateFrom) ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">To</label>
                            <input type="date" class="form-control" name="date_to" value="<?= e($dateTo) ?>">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Generate</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <span><?= e($title) ?></span>
                    <button onclick="window.print()" class="btn btn-sm btn-outline-secondary no-print">Print / Download</button>
                </div>
                <div class="table-responsive">
                    <?php if ($rows === []): ?>
                        <p class="text-muted p-3 mb-0">No data for selected filters.</p>
                    <?php else: ?>
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <?php foreach (array_keys($rows[0]) as $column): ?>
                                        <th><?= e(ucwords(str_replace('_', ' ', $column))) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <?php foreach ($row as $value): ?>
                                            <td><?= e(formatReportCell($value)) ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
