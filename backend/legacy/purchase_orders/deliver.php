<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

requireRole(['storekeeper']);

$branchId = requireUserBranchId();
$id = (int) ($_GET['id'] ?? 0);
$db = getDb();

$stmt = $db->prepare(
    'SELECT * FROM purchase_orders WHERE id = :id AND branch_id = :branch_id AND status = "pending" LIMIT 1'
);
$stmt->execute(['id' => $id, 'branch_id' => $branchId]);
$order = $stmt->fetch();

if ($order === false) {
    setFlash('danger', 'Pending order not found.');
    redirect(url('purchase_orders/index.php'));
}

try {
    $db->beginTransaction();

    $items = $db->prepare(
        'SELECT product_id, qty_ordered FROM purchase_order_items WHERE purchase_order_id = :po_id'
    );
    $items->execute(['po_id' => $id]);
    $orderItems = $items->fetchAll();

    $stockStmt = $db->prepare(
        'UPDATE products SET quantity = quantity + :qty WHERE id = :product_id AND branch_id = :branch_id'
    );

    foreach ($orderItems as $item) {
        $stockStmt->execute([
            'qty' => $item['qty_ordered'],
            'product_id' => $item['product_id'],
            'branch_id' => $branchId,
        ]);
    }

    $db->prepare(
        "UPDATE purchase_orders SET status = 'delivered', delivered_at = NOW() WHERE id = :id"
    )->execute(['id' => $id]);

    $db->commit();
    setFlash('success', 'Delivery confirmed. Stock updated.');
} catch (Throwable $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    setFlash('danger', 'Delivery failed: ' . $exception->getMessage());
}

redirect(url('purchase_orders/index.php'));
