<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

requireRole(['owner', 'branch_admin', 'cashier']);

$user = currentUser();
$db = getDb();

$sql = 'SELECT s.id, s.total, s.payment_method, s.created_at, b.name AS branch_name, u.full_name AS cashier_name
        FROM sales s
        INNER JOIN branches b ON b.id = s.branch_id
        INNER JOIN users u ON u.id = s.cashier_id
        WHERE 1 = 1';
$params = [];

if ($user['role'] === 'branch_admin') {
    $sql .= ' AND s.branch_id = :branch_id';
    $params['branch_id'] = requireUserBranchId();
}

if ($user['role'] === 'cashier') {
    $sql .= ' AND s.branch_id = :branch_id AND s.cashier_id = :cashier_id';
    $params['branch_id'] = requireUserBranchId();
    $params['cashier_id'] = (int) $user['id'];
}

$sql .= ' ORDER BY s.created_at DESC LIMIT 100';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$sales = $stmt->fetchAll();

$pageTitle = match ($user['role']) {
    'owner' => 'All Sales',
    'branch_admin' => 'Branch Sales',
    default => 'My Sales',
};

require_once __DIR__ . '/../includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="col-md-9 col-lg-10 p-4">
            <h1 class="h3 mb-4"><?= e($pageTitle) ?></h1>

            <div class="card">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <?php if ($user['role'] === 'owner'): ?><th>Branch</th><?php endif; ?>
                                <th>Cashier</th>
                                <th>Total</th>
                                <th>Payment</th>
                                <th>Date</th>
                                <th>Invoice</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($sales === []): ?>
                                <tr><td colspan="<?= $user['role'] === 'owner' ? 7 : 6 ?>" class="text-muted">No sales found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($sales as $sale): ?>
                                    <tr>
                                        <td>#<?= (int) $sale['id'] ?></td>
                                        <?php if ($user['role'] === 'owner'): ?><td><?= e($sale['branch_name']) ?></td><?php endif; ?>
                                        <td><?= e($sale['cashier_name']) ?></td>
                                        <td><?= e(formatMoney((float) $sale['total'])) ?></td>
                                        <td><?= e(ucfirst($sale['payment_method'])) ?></td>
                                        <td><?= e($sale['created_at']) ?></td>
                                        <td>
                                            <a href="<?= e(url('pos/invoice.php?id=' . $sale['id'])) ?>" class="btn btn-sm btn-outline-primary">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
