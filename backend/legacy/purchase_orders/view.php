<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

requireRole(['storekeeper']);

$branchId = requireUserBranchId();
$id = (int) ($_GET['id'] ?? 0);
$db = getDb();

$stmt = $db->prepare(
    'SELECT po.*, s.name AS supplier_name FROM purchase_orders po
     INNER JOIN suppliers s ON s.id = po.supplier_id
     WHERE po.id = :id AND po.branch_id = :branch_id LIMIT 1'
);
$stmt->execute(['id' => $id, 'branch_id' => $branchId]);
$order = $stmt->fetch();

if ($order === false) {
    setFlash('danger', 'Order not found.');
    redirect(url('purchase_orders/index.php'));
}

$items = $db->prepare(
    'SELECT poi.*, p.name, p.style_code FROM purchase_order_items poi
     INNER JOIN products p ON p.id = poi.product_id
     WHERE poi.purchase_order_id = :po_id'
);
$items->execute(['po_id' => $id]);
$orderItems = $items->fetchAll();

$pageTitle = 'Purchase Order #' . $id;
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="col-md-9 col-lg-10 p-4">
            <h1 class="h3 mb-4">Purchase Order #<?= $id ?></h1>
            <div class="card mb-3"><div class="card-body">
                <p><strong>Supplier:</strong> <?= e($order['supplier_name']) ?></p>
                <p><strong>Status:</strong> <?= e(ucfirst($order['status'])) ?></p>
                <p><strong>Notes:</strong> <?= e($order['notes']) ?></p>
            </div></div>
            <div class="card">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead><tr><th>Product</th><th>Style</th><th>Qty Ordered</th></tr></thead>
                        <tbody>
                            <?php foreach ($orderItems as $item): ?>
                                <tr>
                                    <td><?= e($item['name']) ?></td>
                                    <td><?= e($item['style_code']) ?></td>
                                    <td><?= (int) $item['qty_ordered'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <a href="<?= e(url('purchase_orders/index.php')) ?>" class="btn btn-secondary mt-3">Back</a>
        </main>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
