<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

requireRole(['storekeeper']);

$user = currentUser();
$branchId = requireUserBranchId();
$db = getDb();

$stmt = $db->prepare(
    'SELECT po.*, s.name AS supplier_name, u.full_name AS created_by_name
     FROM purchase_orders po
     INNER JOIN suppliers s ON s.id = po.supplier_id
     INNER JOIN users u ON u.id = po.created_by
     WHERE po.branch_id = :branch_id
     ORDER BY po.created_at DESC'
);
$stmt->execute(['branch_id' => $branchId]);
$orders = $stmt->fetchAll();

$pageTitle = 'Purchase Orders';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="col-md-9 col-lg-10 p-4">
            <?php $flash = getFlash(); if ($flash !== null): ?>
                <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Purchase Orders</h1>
                <a href="<?= e(url('purchase_orders/create.php')) ?>" class="btn btn-primary">Create Order</a>
            </div>

            <div class="card">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Supplier</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($orders === []): ?>
                                <tr><td colspan="6" class="text-muted">No purchase orders.</td></tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>#<?= (int) $order['id'] ?></td>
                                        <td><?= e($order['supplier_name']) ?></td>
                                        <td><span class="badge bg-<?= $order['status'] === 'delivered' ? 'success' : ($order['status'] === 'cancelled' ? 'secondary' : 'warning') ?>"><?= e(ucfirst($order['status'])) ?></span></td>
                                        <td><?= e($order['created_by_name']) ?></td>
                                        <td><?= e($order['created_at']) ?></td>
                                        <td>
                                            <a href="<?= e(url('purchase_orders/view.php?id=' . $order['id'])) ?>" class="btn btn-sm btn-outline-primary">View</a>
                                            <?php if ($order['status'] === 'pending'): ?>
                                                <a href="<?= e(url('purchase_orders/deliver.php?id=' . $order['id'])) ?>" class="btn btn-sm btn-outline-success" onclick="return confirm('Confirm delivery and update stock?');">Deliver</a>
                                            <?php endif; ?>
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
