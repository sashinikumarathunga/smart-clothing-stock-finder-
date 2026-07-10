<?php

declare(strict_types=1);

function handleReservationsList(): never
{
    $user = Auth::requireRole(['sales_assistant']);
    $branchId = Auth::requireUserBranchId($user);
    $db = getDb();

    $products = $db->prepare(
        'SELECT p.* FROM products p WHERE p.branch_id = :branch_id AND p.quantity > 0 ORDER BY p.name'
    );
    $products->execute(['branch_id' => $branchId]);
    $productList = $products->fetchAll();

    foreach ($productList as &$product) {
        $product['available_qty'] = getAvailableQty($db, (int) $product['id']);
    }
    unset($product);

    $reservations = $db->prepare(
        'SELECT r.*, p.name AS product_name, p.barcode, p.style_code
         FROM reservations r
         INNER JOIN products p ON p.id = r.product_id
         WHERE r.branch_id = :branch_id AND r.status = "active"
         ORDER BY r.expires_at ASC'
    );
    $reservations->execute(['branch_id' => $branchId]);

    Response::json([
        'products' => $productList,
        'reservations' => $reservations->fetchAll(),
    ]);
}

function handleReservationsCreate(): never
{
    $user = Auth::requireRole(['sales_assistant']);
    $branchId = Auth::requireUserBranchId($user);
    $db = getDb();
    $body = requestJsonBody();

    $productId = bodyInt($body, 'product_id');
    $customerName = bodyString($body, 'customer_name');
    $customerPhone = bodyString($body, 'customer_phone');
    $qty = max(1, bodyInt($body, 'qty'));

    if ($customerName === '' || $customerPhone === '') {
        Response::error('Customer name and phone are required.');
    }

    $available = getAvailableQty($db, $productId);

    if ($available < $qty) {
        Response::error('Not enough available stock. Available: ' . $available);
    }

    $db->prepare(
        'INSERT INTO reservations
         (product_id, branch_id, sales_assistant_id, customer_name, customer_phone, qty, expires_at)
         VALUES
         (:product_id, :branch_id, :sales_assistant_id, :customer_name, :customer_phone, :qty, DATE_ADD(NOW(), INTERVAL 1 HOUR))'
    )->execute([
        'product_id' => $productId,
        'branch_id' => $branchId,
        'sales_assistant_id' => $user['id'],
        'customer_name' => $customerName,
        'customer_phone' => $customerPhone,
        'qty' => $qty,
    ]);

    Response::json(['message' => 'Item reserved for 1 hour', 'id' => (int) $db->lastInsertId()], 201);
}

function handleReservationsCancel(int $id): never
{
    $user = Auth::requireRole(['sales_assistant']);
    $branchId = Auth::requireUserBranchId($user);

    $stmt = getDb()->prepare(
        "UPDATE reservations SET status = 'cancelled'
         WHERE id = :id AND branch_id = :branch_id AND status = 'active'"
    );
    $stmt->execute(['id' => $id, 'branch_id' => $branchId]);

    if ($stmt->rowCount() === 0) {
        Response::notFound('Active reservation not found');
    }

    Response::json(['message' => 'Reservation cancelled']);
}

function handleSalesList(): never
{
    $user = Auth::requireAuth();
    $db = getDb();
    $role = $user['role'];

    if (!in_array($role, ['owner', 'branch_admin', 'cashier'], true)) {
        Response::forbidden();
    }

    $sql = 'SELECT s.id, s.total, s.payment_method, s.created_at, s.subtotal, s.discount_amount,
                   b.name AS branch_name, u.full_name AS cashier_name
            FROM sales s
            INNER JOIN branches b ON b.id = s.branch_id
            INNER JOIN users u ON u.id = s.cashier_id
            WHERE 1 = 1';
    $params = [];

    if ($role === 'branch_admin') {
        $sql .= ' AND s.branch_id = :branch_id';
        $params['branch_id'] = Auth::requireUserBranchId($user);
    }

    if ($role === 'cashier') {
        $sql .= ' AND s.branch_id = :branch_id AND s.cashier_id = :cashier_id';
        $params['branch_id'] = Auth::requireUserBranchId($user);
        $params['cashier_id'] = $user['id'];
    }

    $sql .= ' ORDER BY s.created_at DESC LIMIT 100';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    Response::json(['sales' => $stmt->fetchAll()]);
}

function handleSalesGet(int $id): never
{
    $user = Auth::requireAuth();
    $db = getDb();
    $role = $user['role'];

    if (!in_array($role, ['owner', 'branch_admin', 'cashier'], true)) {
        Response::forbidden();
    }

    $sql = 'SELECT s.*, b.name AS branch_name, b.location AS branch_location, u.full_name AS cashier_name,
                   c.full_name AS customer_name, c.phone AS customer_phone
            FROM sales s
            INNER JOIN branches b ON b.id = s.branch_id
            INNER JOIN users u ON u.id = s.cashier_id
            LEFT JOIN customers c ON c.id = s.customer_id
            WHERE s.id = :id';
    $params = ['id' => $id];

    if ($role === 'cashier') {
        $sql .= ' AND s.branch_id = :branch_id AND s.cashier_id = :cashier_id';
        $params['branch_id'] = Auth::requireUserBranchId($user);
        $params['cashier_id'] = $user['id'];
    } elseif ($role === 'branch_admin') {
        $sql .= ' AND s.branch_id = :branch_id';
        $params['branch_id'] = Auth::requireUserBranchId($user);
    }

    $stmt = $db->prepare($sql . ' LIMIT 1');
    $stmt->execute($params);
    $sale = $stmt->fetch();

    if ($sale === false) {
        Response::notFound('Sale not found');
    }

    $itemsStmt = $db->prepare(
        'SELECT si.*, p.name, p.barcode, p.style_code
         FROM sale_items si
         INNER JOIN products p ON p.id = si.product_id
         WHERE si.sale_id = :sale_id'
    );
    $itemsStmt->execute(['sale_id' => $id]);

    Response::json(['sale' => $sale, 'items' => $itemsStmt->fetchAll()]);
}

function handleSalesCreate(): never
{
    $user = Auth::requireRole(['cashier']);
    $branchId = Auth::requireUserBranchId($user);
    $db = getDb();
    $body = requestJsonBody();

    $paymentMethod = bodyString($body, 'payment_method');
    $customerPhone = bodyString($body, 'customer_phone');
    $redeemPoints = bodyInt($body, 'redeem_points');
    $items = $body['items'] ?? [];

    if (!is_array($items) || $items === []) {
        Response::error('Cart is empty.');
    }

    if (!in_array($paymentMethod, ['cash', 'card'], true)) {
        Response::error('Select a valid payment method.');
    }

    $cart = [];
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $productId = (int) ($item['product_id'] ?? 0);
        $qty = max(1, (int) ($item['qty'] ?? 0));
        $unitPrice = (float) ($item['unit_price'] ?? 0);
        $name = (string) ($item['name'] ?? '');

        if ($productId <= 0 || $qty <= 0) {
            continue;
        }

        $cart[] = [
            'product_id' => $productId,
            'qty' => $qty,
            'unit_price' => $unitPrice,
            'name' => $name,
        ];
    }

    if ($cart === []) {
        Response::error('Cart is empty.');
    }

    $branchSettings = getBranchSettings($db, $branchId);

    try {
        $db->beginTransaction();

        foreach ($cart as $item) {
            $available = getAvailableQty($db, (int) $item['product_id']);
            if ($available < (int) $item['qty']) {
                throw new RuntimeException('Insufficient stock for ' . $item['name'] . '. Available: ' . $available);
            }
        }

        $subtotal = 0.0;
        foreach ($cart as $item) {
            if ($item['unit_price'] <= 0) {
                $stmt = $db->prepare('SELECT price, name FROM products WHERE id = :id AND branch_id = :branch_id LIMIT 1');
                $stmt->execute(['id' => $item['product_id'], 'branch_id' => $branchId]);
                $product = $stmt->fetch();
                if ($product === false) {
                    throw new RuntimeException('Product not found: ' . $item['product_id']);
                }
                $item['unit_price'] = (float) $product['price'];
                $item['name'] = $product['name'];
            }
            $subtotal += $item['unit_price'] * $item['qty'];
        }

        $customerId = null;
        $discount = 0.0;
        $pointsRedeemed = 0;

        if ($customerPhone !== '') {
            $customer = findCustomerByPhone($db, $branchId, $customerPhone);
            if ($customer === null) {
                throw new RuntimeException('Customer not found. Register them first.');
            }
            $customerId = (int) $customer['id'];

            if ($redeemPoints > 0) {
                if ($redeemPoints > (int) $customer['loyalty_points']) {
                    throw new RuntimeException('Customer does not have enough loyalty points.');
                }
                $redeemResult = calculateLoyaltyDiscount($redeemPoints, $branchSettings, $subtotal);
                if (!$redeemResult['valid']) {
                    throw new RuntimeException($redeemResult['message']);
                }
                $discount = $redeemResult['discount'];
                $pointsRedeemed = $redeemPoints;
            }
        } elseif ($redeemPoints > 0) {
            throw new RuntimeException('Enter customer phone to redeem points.');
        }

        $total = max(0, $subtotal - $discount);
        $pointsEarned = $customerId !== null ? calculateLoyaltyEarned($total, $branchSettings) : 0;

        $saleStmt = $db->prepare(
            'INSERT INTO sales (branch_id, cashier_id, customer_id, subtotal, discount_amount, total, payment_method, loyalty_points_earned, loyalty_points_redeemed)
             VALUES (:branch_id, :cashier_id, :customer_id, :subtotal, :discount_amount, :total, :payment_method, :loyalty_points_earned, :loyalty_points_redeemed)'
        );
        $saleStmt->execute([
            'branch_id' => $branchId,
            'cashier_id' => (int) $user['id'],
            'customer_id' => $customerId,
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'total' => $total,
            'payment_method' => $paymentMethod,
            'loyalty_points_earned' => $pointsEarned,
            'loyalty_points_redeemed' => $pointsRedeemed,
        ]);

        $saleId = (int) $db->lastInsertId();

        $itemStmt = $db->prepare(
            'INSERT INTO sale_items (sale_id, product_id, qty, unit_price, line_total)
             VALUES (:sale_id, :product_id, :qty, :unit_price, :line_total)'
        );

        $stockStmt = $db->prepare(
            'UPDATE products SET quantity = quantity - :deduct_qty
             WHERE id = :product_id AND branch_id = :branch_id AND quantity >= :required_qty'
        );

        foreach ($cart as $item) {
            $lineTotal = $item['unit_price'] * $item['qty'];

            $itemStmt->execute([
                'sale_id' => $saleId,
                'product_id' => $item['product_id'],
                'qty' => $item['qty'],
                'unit_price' => $item['unit_price'],
                'line_total' => $lineTotal,
            ]);

            $stockStmt->execute([
                'deduct_qty' => $item['qty'],
                'required_qty' => $item['qty'],
                'product_id' => $item['product_id'],
                'branch_id' => $branchId,
            ]);

            if ($stockStmt->rowCount() === 0) {
                throw new RuntimeException('Stock update failed for product ID ' . $item['product_id']);
            }

            completeReservationsForProduct($db, (int) $item['product_id'], $branchId);
        }

        if ($customerId !== null) {
            $db->prepare(
                'UPDATE customers SET loyalty_points = loyalty_points - :redeemed + :earned WHERE id = :id'
            )->execute([
                'redeemed' => $pointsRedeemed,
                'earned' => $pointsEarned,
                'id' => $customerId,
            ]);
        }

        $db->commit();
    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        Response::error('Checkout failed: ' . $exception->getMessage(), 400);
    }

    handleSalesGet($saleId);
}

function handleCustomersList(): never
{
    $user = Auth::requireRole(['cashier']);
    $branchId = Auth::requireUserBranchId($user);

    $stmt = getDb()->prepare(
        'SELECT * FROM customers WHERE branch_id = :branch_id ORDER BY full_name'
    );
    $stmt->execute(['branch_id' => $branchId]);

    Response::json(['customers' => $stmt->fetchAll()]);
}

function handleCustomersCreate(): never
{
    $user = Auth::requireRole(['cashier']);
    $branchId = Auth::requireUserBranchId($user);
    $body = requestJsonBody();

    $fullName = bodyString($body, 'full_name');
    $phone = bodyString($body, 'phone');
    $email = bodyString($body, 'email');

    if ($fullName === '' || $phone === '') {
        Response::error('Name and phone are required.');
    }

    try {
        getDb()->prepare(
            'INSERT INTO customers (branch_id, full_name, phone, email) VALUES (:branch_id, :full_name, :phone, :email)'
        )->execute([
            'branch_id' => $branchId,
            'full_name' => $fullName,
            'phone' => $phone,
            'email' => $email,
        ]);
    } catch (PDOException) {
        Response::error('Customer with this phone already exists at this branch.', 409);
    }

    Response::json(['message' => 'Customer registered', 'id' => (int) getDb()->lastInsertId()], 201);
}

function handleReturnsCreate(): never
{
    $user = Auth::requireRole(['cashier']);
    $branchId = Auth::requireUserBranchId($user);
    $db = getDb();
    $body = requestJsonBody();
    $branchSettings = getBranchSettings($db, $branchId);

    $type = bodyString($body, 'type');
    $saleId = bodyInt($body, 'sale_id');
    $saleItemId = bodyInt($body, 'sale_item_id');
    $qty = max(1, bodyInt($body, 'qty'));
    $reason = bodyString($body, 'reason');
    $exchangeProductId = bodyInt($body, 'exchange_product_id');

    if (!in_array($type, ['return', 'exchange'], true)) {
        Response::error('type must be return or exchange.');
    }

    if ($type === 'return') {
        $itemStmt = $db->prepare(
            'SELECT si.*, s.created_at AS sale_date, s.branch_id
             FROM sale_items si
             INNER JOIN sales s ON s.id = si.sale_id
             WHERE si.id = :id AND s.id = :sale_id AND s.branch_id = :branch_id LIMIT 1'
        );
        $itemStmt->execute(['id' => $saleItemId, 'sale_id' => $saleId, 'branch_id' => $branchId]);
        $item = $itemStmt->fetch();

        if ($item === false) {
            Response::notFound('Sale item not found');
        }

        $returnDays = (int) $branchSettings['return_period_days'];
        $deadline = strtotime('+' . $returnDays . ' days', strtotime($item['sale_date']));

        if (time() > $deadline) {
            Response::error("Return period expired ({$returnDays} days from purchase).");
        }

        if ($qty > (int) $item['qty']) {
            Response::error('Return quantity exceeds purchased quantity.');
        }

        try {
            $db->beginTransaction();

            $db->prepare(
                'INSERT INTO returns_exchanges (sale_id, sale_item_id, branch_id, processed_by, type, qty, reason)
                 VALUES (:sale_id, :sale_item_id, :branch_id, :processed_by, "return", :qty, :reason)'
            )->execute([
                'sale_id' => $saleId,
                'sale_item_id' => $saleItemId,
                'branch_id' => $branchId,
                'processed_by' => $user['id'],
                'qty' => $qty,
                'reason' => $reason,
            ]);

            $db->prepare('UPDATE products SET quantity = quantity + :qty WHERE id = :product_id')
                ->execute(['qty' => $qty, 'product_id' => $item['product_id']]);

            $db->commit();
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Response::error('Return failed: ' . $exception->getMessage(), 500);
        }

        Response::json(['message' => 'Return processed. Stock restored.']);
    }

    if ($exchangeProductId <= 0) {
        Response::error('Select a product to exchange for.');
    }

    $itemStmt = $db->prepare(
        'SELECT si.*, s.created_at AS sale_date
         FROM sale_items si
         INNER JOIN sales s ON s.id = si.sale_id
         WHERE si.id = :id AND s.id = :sale_id AND s.branch_id = :branch_id LIMIT 1'
    );
    $itemStmt->execute(['id' => $saleItemId, 'sale_id' => $saleId, 'branch_id' => $branchId]);
    $item = $itemStmt->fetch();

    $newProductStmt = $db->prepare(
        'SELECT * FROM products WHERE id = :id AND branch_id = :branch_id LIMIT 1'
    );
    $newProductStmt->execute(['id' => $exchangeProductId, 'branch_id' => $branchId]);
    $newProduct = $newProductStmt->fetch();

    if ($item === false || $newProduct === false) {
        Response::error('Invalid sale item or exchange product.');
    }

    $returnDays = (int) $branchSettings['return_period_days'];
    $deadline = strtotime('+' . $returnDays . ' days', strtotime($item['sale_date']));

    if (time() > $deadline) {
        Response::error("Exchange period expired ({$returnDays} days).");
    }

    if ((int) $newProduct['quantity'] < $qty) {
        Response::error('Insufficient stock for exchange product.');
    }

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
            'sale_id' => $saleId,
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
    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        Response::error('Exchange failed: ' . $exception->getMessage(), 500);
    }

    $message = 'Exchange processed.';
    if ($priceDiff > 0) {
        $message .= ' Customer pays ' . formatMoney($priceDiff) . '.';
    } elseif ($priceDiff < 0) {
        $message .= ' Refund ' . formatMoney(abs($priceDiff)) . ' to customer.';
    }

    Response::json(['message' => $message, 'price_difference' => $priceDiff]);
}

function handleReturnsSaleLookup(): never
{
    $user = Auth::requireRole(['cashier']);
    $branchId = Auth::requireUserBranchId($user);
    $db = getDb();
    $saleId = queryInt('sale_id');

    if ($saleId <= 0) {
        Response::error('sale_id query parameter is required.');
    }

    $stmt = $db->prepare('SELECT * FROM sales WHERE id = :id AND branch_id = :branch_id LIMIT 1');
    $stmt->execute(['id' => $saleId, 'branch_id' => $branchId]);
    $sale = $stmt->fetch();

    if ($sale === false) {
        Response::notFound('Sale not found at your branch');
    }

    $itemsStmt = $db->prepare(
        'SELECT si.*, p.name, p.barcode FROM sale_items si
         INNER JOIN products p ON p.id = si.product_id
         WHERE si.sale_id = :sale_id'
    );
    $itemsStmt->execute(['sale_id' => $saleId]);

    $products = $db->prepare(
        'SELECT id, name, style_code, price, quantity FROM products WHERE branch_id = :branch_id ORDER BY name'
    );
    $products->execute(['branch_id' => $branchId]);

    Response::json([
        'sale' => $sale,
        'items' => $itemsStmt->fetchAll(),
        'exchange_products' => $products->fetchAll(),
        'return_period_days' => (int) getBranchSettings($db, $branchId)['return_period_days'],
    ]);
}
