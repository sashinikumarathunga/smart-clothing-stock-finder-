<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

requireRole(['cashier']);

$user = currentUser();
$branchId = requireUserBranchId();
$db = getDb();
$error = '';
$sale = null;
$saleItems = [];
$branchSettings = getBranchSettings($db, $branchId);

$saleId = (int) ($_GET['sale_id'] ?? postInt('sale_id'));

if ($saleId > 0) {
    $stmt = $db->prepare(
        'SELECT * FROM sales WHERE id = :id AND branch_id = :branch_id LIMIT 1'
    );
    $stmt->execute(['id' => $saleId, 'branch_id' => $branchId]);
    $sale = $stmt->fetch();
    if ($sale === false) {
        $sale = null;
    } else {
        $itemsStmt = $db->prepare(
            'SELECT si.*, p.name, p.barcode FROM sale_items si
             INNER JOIN products p ON p.id = si.product_id
             WHERE si.sale_id = :sale_id'
        );
        $itemsStmt->execute(['sale_id' => $saleId]);
        $saleItems = $itemsStmt->fetchAll();
    }
}

if (isPost()) {
    $action = postString('action');

    if ($action === 'return') {
        $saleItemId = postInt('sale_item_id');
        $qty = max(1, postInt('qty'));
        $reason = postString('reason');
        $lookupSaleId = postInt('sale_id');

        $itemStmt = $db->prepare(
            'SELECT si.*, s.created_at AS sale_date, s.branch_id
             FROM sale_items si
             INNER JOIN sales s ON s.id = si.sale_id
             WHERE si.id = :id AND s.id = :sale_id AND s.branch_id = :branch_id
             LIMIT 1'
        );
        $itemStmt->execute(['id' => $saleItemId, 'sale_id' => $lookupSaleId, 'branch_id' => $branchId]);
        $item = $itemStmt->fetch();

        if ($item === false) {
            $error = 'Sale item not found.';
        } else {
            $returnDays = (int) $branchSettings['return_period_days'];
            $saleDate = strtotime($item['sale_date']);
            $deadline = strtotime("+{$returnDays} days", $saleDate);

            if (time() > $deadline) {
                $error = "Return period expired ({$returnDays} days from purchase).";
            } elseif ($qty > (int) $item['qty']) {
                $error = 'Return quantity exceeds purchased quantity.';
            } else {
                try {
                    $db->beginTransaction();

                    $db->prepare(
                        'INSERT INTO returns_exchanges (sale_id, sale_item_id, branch_id, processed_by, type, qty, reason)
                         VALUES (:sale_id, :sale_item_id, :branch_id, :processed_by, "return", :qty, :reason)'
                    )->execute([
                        'sale_id' => $lookupSaleId,
                        'sale_item_id' => $saleItemId,
                        'branch_id' => $branchId,
                        'processed_by' => $user['id'],
                        'qty' => $qty,
                        'reason' => $reason,
                    ]);

                    $db->prepare(
                        'UPDATE products SET quantity = quantity + :qty WHERE id = :product_id'
                    )->execute(['qty' => $qty, 'product_id' => $item['product_id']]);

                    $db->commit();
                    setFlash('success', 'Return processed. Stock restored.');
                    redirect(url('returns/index.php?sale_id=' . $lookupSaleId));
                } catch (Throwable $exception) {
                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }
                    $error = 'Return failed: ' . $exception->getMessage();
                }
            }
        }
    }

    if ($action === 'exchange') {
        $saleItemId = postInt('sale_item_id');
        $exchangeProductId = postInt('exchange_product_id');
        $qty = max(1, postInt('qty'));
        $reason = postString('reason');
        $lookupSaleId = postInt('sale_id');

        $itemStmt = $db->prepare(
            'SELECT si.*, s.created_at AS sale_date
             FROM sale_items si
             INNER JOIN sales s ON s.id = si.sale_id
             WHERE si.id = :id AND s.id = :sale_id AND s.branch_id = :branch_id LIMIT 1'
        );
        $itemStmt->execute(['id' => $saleItemId, 'sale_id' => $lookupSaleId, 'branch_id' => $branchId]);
        $item = $itemStmt->fetch();

        $newProductStmt = $db->prepare(
            'SELECT * FROM products WHERE id = :id AND branch_id = :branch_id LIMIT 1'
        );
        $newProductStmt->execute(['id' => $exchangeProductId, 'branch_id' => $branchId]);
        $newProduct = $newProductStmt->fetch();

        if ($exchangeProductId <= 0) {
            $error = 'Select a product to exchange for.';
        } elseif ($item === false || $newProduct === false) {
            $error = 'Invalid sale item or exchange product.';
        } else {
            $returnDays = (int) $branchSettings['return_period_days'];
            $deadline = strtotime('+' . $returnDays . ' days', strtotime($item['sale_date']));

            if (time() > $deadline) {
                $error = "Exchange period expired ({$returnDays} days).";
            } elseif ((int) $newProduct['quantity'] < $qty) {
                $error = 'Insufficient stock for exchange product.';
            } else {
                $oldLine = (float) $item['unit_price'] * $qty;
                $newLine = (float) $newProduct['price'] * $qty;
                $priceDiff = $newLine - $oldLine;

                try {
                    $db->beginTransaction();

                    $db->prepare(
                        'INSERT INTO returns_exchanges
                         (sale_id, sale_item_id, branch_id, processed_by, type, qty, reason, exchange_product_id, price_difference)
                         VALUES (:sale_id, :sale_item_id, :branch_id, :processed_by, "exchange", :qty, :reason, :exchange_product_id, :price_difference)'
                    )->execute([
                        'sale_id' => $lookupSaleId,
                        'sale_item_id' => $saleItemId,
                        'branch_id' => $branchId,
                        'processed_by' => $user['id'],
                        'qty' => $qty,
                        'reason' => $reason,
                        'exchange_product_id' => $exchangeProductId,
                        'price_difference' => $priceDiff,
                    ]);

                    $db->prepare('UPDATE products SET quantity = quantity + :qty WHERE id = :id')
                        ->execute(['qty' => $qty, 'id' => $item['product_id']]);

                    $deduct = $db->prepare(
                        'UPDATE products SET quantity = quantity - :qty WHERE id = :id AND quantity >= :required'
                    );
                    $deduct->execute(['qty' => $qty, 'id' => $exchangeProductId, 'required' => $qty]);

                    if ($deduct->rowCount() === 0) {
                        throw new RuntimeException('Exchange stock update failed.');
                    }

                    $db->commit();
                    $msg = 'Exchange processed.';
                    if ($priceDiff > 0) {
                        $msg .= ' Customer pays ' . formatMoney($priceDiff) . '.';
                    } elseif ($priceDiff < 0) {
                        $msg .= ' Refund ' . formatMoney(abs($priceDiff)) . ' to customer.';
                    }
                    setFlash('success', $msg);
                    redirect(url('returns/index.php?sale_id=' . $lookupSaleId));
                } catch (Throwable $exception) {
                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }
                    $error = 'Exchange failed: ' . $exception->getMessage();
                }
            }
        }
    }
}

$exchangeProducts = $db->prepare('SELECT id, name, style_code, price, quantity FROM products WHERE branch_id = :branch_id ORDER BY name');
$exchangeProducts->execute(['branch_id' => $branchId]);
$productOptions = $exchangeProducts->fetchAll();

$pageTitle = 'Returns & Exchanges';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="col-md-9 col-lg-10 p-4">
            <?php $flash = getFlash(); if ($flash !== null): ?>
                <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
            <?php endif; ?>

            <h1 class="h3 mb-4">Returns & Exchanges</h1>
            <p class="text-muted">Return period: <?= (int) $branchSettings['return_period_days'] ?> days from purchase.</p>

            <?php if ($error !== ''): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

            <div class="card mb-4">
                <div class="card-body">
                    <form method="get" class="row g-2">
                        <div class="col-md-4">
                            <input type="number" class="form-control" name="sale_id" placeholder="Sale / Invoice ID" value="<?= $saleId > 0 ? $saleId : '' ?>" required>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Lookup</button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (is_array($sale)): ?>
                <div class="card mb-4">
                    <div class="card-header">Sale #<?= (int) $sale['id'] ?> - <?= e($sale['created_at']) ?> - Total: <?= e(formatMoney((float) $sale['total'])) ?></div>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead><tr><th>Product</th><th>Barcode</th><th>Qty</th><th>Unit Price</th><th>Return</th><th>Exchange</th></tr></thead>
                            <tbody>
                                <?php foreach ($saleItems as $item): ?>
                                    <tr>
                                        <td><?= e($item['name']) ?></td>
                                        <td><code><?= e($item['barcode']) ?></code></td>
                                        <td><?= (int) $item['qty'] ?></td>
                                        <td><?= e(formatMoney((float) $item['unit_price'])) ?></td>
                                        <td>
                                            <form method="post" class="d-flex gap-1">
                                                <input type="hidden" name="action" value="return">
                                                <input type="hidden" name="sale_id" value="<?= (int) $sale['id'] ?>">
                                                <input type="hidden" name="sale_item_id" value="<?= (int) $item['id'] ?>">
                                                <input type="number" name="qty" value="1" min="1" max="<?= (int) $item['qty'] ?>" class="form-control form-control-sm" style="width:70px">
                                                <input type="text" name="reason" placeholder="Reason" class="form-control form-control-sm">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Return</button>
                                            </form>
                                        </td>
                                        <td>
                                            <form method="post" class="d-flex gap-1">
                                                <input type="hidden" name="action" value="exchange">
                                                <input type="hidden" name="sale_id" value="<?= (int) $sale['id'] ?>">
                                                <input type="hidden" name="sale_item_id" value="<?= (int) $item['id'] ?>">
                                                <input type="number" name="qty" value="1" min="1" max="<?= (int) $item['qty'] ?>" class="form-control form-control-sm" style="width:70px">
                                                <select name="exchange_product_id" class="form-select form-select-sm">
                                                    <option value="">New item</option>
                                                    <?php foreach ($productOptions as $product): ?>
                                                        <option value="<?= (int) $product['id'] ?>"><?= e($product['name']) ?> (<?= e(formatMoney((float) $product['price'])) ?>)</option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-outline-primary">Exchange</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php elseif ($saleId > 0): ?>
                <div class="alert alert-warning">Sale not found at your branch.</div>
            <?php endif; ?>
        </main>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
