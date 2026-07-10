<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

requireRole(['storekeeper']);

$user = currentUser();
$branchId = requireUserBranchId();
$db = getDb();
$error = '';

$suppliers = $db->query('SELECT id, name FROM suppliers ORDER BY name')->fetchAll();
$products = $db->prepare('SELECT id, name, style_code, size, color FROM products WHERE branch_id = :branch_id ORDER BY name');
$products->execute(['branch_id' => $branchId]);
$productList = $products->fetchAll();

if (isPost()) {
    $supplierId = postInt('supplier_id');
    $notes = postString('notes');
    $productIds = $_POST['product_id'] ?? [];
    $qtys = $_POST['qty'] ?? [];

    if ($supplierId <= 0) {
        $error = 'Select a supplier.';
    } else {
        $items = [];
        foreach ($productIds as $index => $productId) {
            $pid = (int) $productId;
            $qty = (int) ($qtys[$index] ?? 0);
            if ($pid > 0 && $qty > 0) {
                $items[] = ['product_id' => $pid, 'qty' => $qty];
            }
        }

        if ($items === []) {
            $error = 'Add at least one product with quantity.';
        } else {
            try {
                $db->beginTransaction();

                $db->prepare(
                    'INSERT INTO purchase_orders (branch_id, supplier_id, created_by, notes)
                     VALUES (:branch_id, :supplier_id, :created_by, :notes)'
                )->execute([
                    'branch_id' => $branchId,
                    'supplier_id' => $supplierId,
                    'created_by' => $user['id'],
                    'notes' => $notes,
                ]);

                $poId = (int) $db->lastInsertId();
                $itemStmt = $db->prepare(
                    'INSERT INTO purchase_order_items (purchase_order_id, product_id, qty_ordered) VALUES (:po_id, :product_id, :qty)'
                );

                foreach ($items as $item) {
                    $itemStmt->execute([
                        'po_id' => $poId,
                        'product_id' => $item['product_id'],
                        'qty' => $item['qty'],
                    ]);
                }

                $db->commit();
                setFlash('success', 'Purchase order created.');
                redirect(url('purchase_orders/index.php'));
            } catch (Throwable $exception) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                $error = 'Failed to create order: ' . $exception->getMessage();
            }
        }
    }
}

$pageTitle = 'Create Purchase Order';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="col-md-9 col-lg-10 p-4">
            <h1 class="h3 mb-4">Create Purchase Order</h1>
            <?php if ($error !== ''): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
            <div class="card"><div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Supplier</label>
                        <select class="form-select" name="supplier_id" required>
                            <option value="">Select supplier</option>
                            <?php foreach ($suppliers as $supplier): ?>
                                <option value="<?= (int) $supplier['id'] ?>"><?= e($supplier['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <input class="form-control" name="notes">
                    </div>
                    <h2 class="h6">Order Items</h2>
                    <?php for ($i = 0; $i < 3; $i++): ?>
                        <div class="row g-2 mb-2">
                            <div class="col-md-8">
                                <select class="form-select" name="product_id[]">
                                    <option value="">Select product</option>
                                    <?php foreach ($productList as $product): ?>
                                        <option value="<?= (int) $product['id'] ?>"><?= e($product['name']) ?> (<?= e($product['style_code']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="number" min="1" class="form-control" name="qty[]" placeholder="Qty">
                            </div>
                        </div>
                    <?php endfor; ?>
                    <button type="submit" class="btn btn-primary mt-3">Create Order</button>
                    <a href="<?= e(url('purchase_orders/index.php')) ?>" class="btn btn-secondary mt-3">Cancel</a>
                </form>
            </div></div>
        </main>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
