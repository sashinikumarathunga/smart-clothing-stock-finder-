<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

requireRole(['sales_assistant']);

$user = currentUser();
$branchId = requireUserBranchId();
$db = getDb();
$error = '';

if (isPost()) {
    $action = postString('action');

    if ($action === 'create') {
        $productId = postInt('product_id');
        $customerName = postString('customer_name');
        $customerPhone = postString('customer_phone');
        $qty = max(1, postInt('qty'));

        if ($customerName === '' || $customerPhone === '') {
            $error = 'Customer name and phone are required.';
        } else {
            $available = getAvailableQty($db, $productId);

            if ($available < $qty) {
                $error = 'Not enough available stock. Available (after reservations): ' . $available;
            } else {
                $stmt = $db->prepare(
                    'INSERT INTO reservations
                     (product_id, branch_id, sales_assistant_id, customer_name, customer_phone, qty, expires_at)
                     VALUES
                     (:product_id, :branch_id, :sales_assistant_id, :customer_name, :customer_phone, :qty, DATE_ADD(NOW(), INTERVAL 1 HOUR))'
                );
                $stmt->execute([
                    'product_id' => $productId,
                    'branch_id' => $branchId,
                    'sales_assistant_id' => $user['id'],
                    'customer_name' => $customerName,
                    'customer_phone' => $customerPhone,
                    'qty' => $qty,
                ]);

                setFlash('success', 'Item reserved for 1 hour.');
                redirect(url('reservations/index.php'));
            }
        }
    }

    if ($action === 'cancel') {
        $reservationId = postInt('reservation_id');
        $stmt = $db->prepare(
            "UPDATE reservations SET status = 'cancelled'
             WHERE id = :id AND branch_id = :branch_id AND status = 'active'"
        );
        $stmt->execute(['id' => $reservationId, 'branch_id' => $branchId]);

        setFlash('success', 'Reservation cancelled.');
        redirect(url('reservations/index.php'));
    }
}

$products = $db->prepare(
    'SELECT p.* FROM products p WHERE p.branch_id = :branch_id AND p.quantity > 0 ORDER BY p.name'
);
$products->execute(['branch_id' => $branchId]);
$productList = $products->fetchAll();

$reservations = $db->prepare(
    'SELECT r.*, p.name AS product_name, p.barcode, p.style_code
     FROM reservations r
     INNER JOIN products p ON p.id = r.product_id
     WHERE r.branch_id = :branch_id AND r.status = "active"
     ORDER BY r.expires_at ASC'
);
$reservations->execute(['branch_id' => $branchId]);
$activeReservations = $reservations->fetchAll();

$pageTitle = 'Reservations';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="col-md-9 col-lg-10 p-4">
            <?php $flash = getFlash(); if ($flash !== null): ?>
                <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
            <?php endif; ?>

            <h1 class="h3 mb-4">Reservations</h1>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="card">
                        <div class="card-header">Reserve Item</div>
                        <div class="card-body">
                            <form method="post">
                                <input type="hidden" name="action" value="create">
                                <div class="mb-3">
                                    <label class="form-label" for="product_id">Product</label>
                                    <select class="form-select" id="product_id" name="product_id" required>
                                        <option value="">Select product</option>
                                        <?php foreach ($productList as $product): ?>
                                            <?php $available = getAvailableQty($db, (int) $product['id']); ?>
                                            <option value="<?= (int) $product['id'] ?>">
                                                <?= e($product['name']) ?> (<?= e($product['size']) ?>/<?= e($product['color']) ?>) - Avail: <?= $available ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="qty">Quantity</label>
                                    <input type="number" min="1" class="form-control" id="qty" name="qty" value="1" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="customer_name">Customer Name</label>
                                    <input class="form-control" id="customer_name" name="customer_name" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="customer_phone">Customer Phone</label>
                                    <input class="form-control" id="customer_phone" name="customer_phone" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Reserve (1 Hour)</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="card">
                        <div class="card-header">Active Reservations</div>
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Customer</th>
                                        <th>Qty</th>
                                        <th>Expires</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($activeReservations === []): ?>
                                        <tr><td colspan="5" class="text-muted">No active reservations.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($activeReservations as $reservation): ?>
                                            <tr>
                                                <td><?= e($reservation['product_name']) ?> <code><?= e($reservation['barcode']) ?></code></td>
                                                <td><?= e($reservation['customer_name']) ?><br><small><?= e($reservation['customer_phone']) ?></small></td>
                                                <td><?= (int) $reservation['qty'] ?></td>
                                                <td><?= e($reservation['expires_at']) ?></td>
                                                <td>
                                                    <form method="post">
                                                        <input type="hidden" name="action" value="cancel">
                                                        <input type="hidden" name="reservation_id" value="<?= (int) $reservation['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">Cancel</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
