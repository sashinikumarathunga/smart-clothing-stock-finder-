<?php

declare(strict_types=1);

function requireOwnerBranchId(PDO $db, int $branchId): int
{
    if ($branchId < 1) {
        Response::error('Please select a branch.');
    }

    $stmt = $db->prepare('SELECT id FROM branches WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $branchId]);

    if ($stmt->fetchColumn() === false) {
        Response::notFound('Branch not found');
    }

    return $branchId;
}

function handleSettingsGet(): never
{
    Auth::requireRole(['owner']);
    $db = getDb();
    $branchId = requireOwnerBranchId($db, queryInt('branch_id'));
    $branch = getBranchSettings($db, $branchId);

    $branches = $db->query(
        'SELECT id, name, location
         FROM branches
         WHERE is_active = 1
         ORDER BY name'
    )->fetchAll();

    Response::json([
        'selected_branch_id' => $branchId,
        'branches' => $branches,
        'settings' => [
            'loyalty_spend_per_point' => (float) $branch['loyalty_spend_per_point'],
            'loyalty_point_value' => (float) $branch['loyalty_point_value'],
            'loyalty_min_redeem' => (int) $branch['loyalty_min_redeem'],
            'return_period_days' => (int) $branch['return_period_days'],
            'low_stock_threshold' => (int) $branch['low_stock_threshold'],
        ],
    ]);
}

function handleSettingsUpdate(): never
{
    Auth::requireRole(['owner']);
    $db = getDb();
    $body = requestJsonBody();
    $branchId = requireOwnerBranchId($db, bodyInt($body, 'branch_id'));

    $spendPerPoint = bodyFloat($body, 'loyalty_spend_per_point');
    $pointValue = bodyFloat($body, 'loyalty_point_value');
    $minRedeem = bodyInt($body, 'loyalty_min_redeem');
    $returnDays = bodyInt($body, 'return_period_days');
    $lowStockThreshold = bodyInt($body, 'low_stock_threshold');

    if ($spendPerPoint <= 0 || $pointValue <= 0 || $minRedeem < 1 || $returnDays < 1 || $lowStockThreshold < 1) {
        Response::error('All settings must be valid positive values.');
    }

    $db->prepare(
        'UPDATE branches SET
            loyalty_spend_per_point = :spend,
            loyalty_point_value = :value,
            loyalty_min_redeem = :min_redeem,
            return_period_days = :return_days,
            low_stock_threshold = :threshold
         WHERE id = :id AND is_active = 1'
    )->execute([
        'spend' => $spendPerPoint,
        'value' => $pointValue,
        'min_redeem' => $minRedeem,
        'return_days' => $returnDays,
        'threshold' => $lowStockThreshold,
        'id' => $branchId,
    ]);

    Response::json(['message' => 'Branch settings updated']);
}

function handleLowStockAlertsGet(): never
{
    $user = Auth::requireRole(['branch_admin']);
    $db = getDb();
    $branchId = Auth::requireUserBranchId($user);

    $branchStatement = $db->prepare(
        'SELECT id, name, location, low_stock_threshold
         FROM branches
         WHERE id = :id AND is_active = 1
         LIMIT 1'
    );
    $branchStatement->execute(['id' => $branchId]);
    $branch = $branchStatement->fetch();

    if (!$branch) {
        Response::notFound('Your assigned branch is not available.');
    }

    $products = $db->prepare(
        'SELECT id, name, style_code, quantity, low_stock_alert_enabled
         FROM products
         WHERE branch_id = :branch_id AND is_active = 1
         ORDER BY name, style_code'
    );
    $products->execute(['branch_id' => $branchId]);

    Response::json([
        'branch' => [
            'id' => (int) $branch['id'],
            'name' => $branch['name'],
            'location' => $branch['location'],
            'low_stock_threshold' => (int) $branch['low_stock_threshold'],
        ],
        'products' => $products->fetchAll(),
    ]);
}

function handleLowStockAlertsUpdate(): never
{
    $user = Auth::requireRole(['branch_admin']);
    $db = getDb();
    $branchId = Auth::requireUserBranchId($user);
    $body = requestJsonBody();
    $productId = bodyInt($body, 'product_id');
    $enabled = bodyInt($body, 'low_stock_alert_enabled') === 1 ? 1 : 0;

    if ($productId < 1) {
        Response::error('A valid product is required.');
    }

    $statement = $db->prepare(
        'UPDATE products
         SET low_stock_alert_enabled = :enabled
         WHERE id = :id
           AND branch_id = :branch_id
           AND is_active = 1'
    );
    $statement->execute([
        'enabled' => $enabled,
        'id' => $productId,
        'branch_id' => $branchId,
    ]);

    if ($statement->rowCount() === 0) {
        $check = $db->prepare(
            'SELECT id
             FROM products
             WHERE id = :id AND branch_id = :branch_id AND is_active = 1
             LIMIT 1'
        );
        $check->execute(['id' => $productId, 'branch_id' => $branchId]);

        if ($check->fetchColumn() === false) {
            Response::notFound('Product not found in your branch.');
        }
    }

    Response::json(['message' => 'Low stock alert updated']);
}

function handleReports(): never
{
    $user = Auth::requireRole(['owner', 'branch_admin']);
    $db = getDb();
    $isOwner = $user['role'] === 'owner';

    $reportType = queryString('type', 'fast_moving');
    $branchId = $isOwner ? queryInt('branch_id') : Auth::requireUserBranchId($user);
    $dateFrom = queryString('date_from', date('Y-m-01'));
    $dateTo = queryString('date_to', date('Y-m-d'));

    $branches = $isOwner ? $db->query('SELECT id, name FROM branches ORDER BY name')->fetchAll() : [];

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

    $title = 'Reports';

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
            $title = 'Sales Detailed Report';
            $sql = 'SELECT
                        s.id AS invoice_id,
                        s.created_at AS sale_date,
                        b.name AS branch,
                        u.full_name AS cashier,
                        COALESCE(c.full_name, "Walk-in Customer") AS customer,
                        COALESCE(c.phone, "-") AS customer_phone,
                        p.name AS product,
                        p.style_code,
                        p.brand,
                        p.size,
                        p.color,
                        si.qty,
                        si.unit_price,
                        si.line_total,
                        s.subtotal,
                        s.discount_amount AS discount,
                        s.total AS invoice_total,
                        s.payment_method,
                        s.loyalty_points_earned,
                        s.loyalty_points_redeemed
                    FROM sales s
                    INNER JOIN sale_items si ON si.sale_id = s.id
                    INNER JOIN products p ON p.id = si.product_id
                    INNER JOIN branches b ON b.id = s.branch_id
                    INNER JOIN users u ON u.id = s.cashier_id
                    LEFT JOIN customers c ON c.id = s.customer_id
                    WHERE s.created_at BETWEEN :date_from AND :date_to' . $branchFilterAlias['s'] . '
                    ORDER BY s.created_at DESC, s.id DESC, si.id ASC';
            break;

        case 'returns_summary':
            $title = 'Returns & Exchanges Detailed Report';
            $sql = 'SELECT
                        re.id AS transaction_id,
                        re.created_at AS date,
                        re.sale_id AS invoice_id,
                        b.name AS branch,
                        re.type,
                        rp.name AS returned_product,
                        rp.style_code AS returned_style_code,
                        rp.size AS returned_size,
                        rp.color AS returned_color,
                        re.qty,
                        si.unit_price AS original_unit_price,
                        CAST((si.unit_price * re.qty) AS DECIMAL(10,2)) AS original_value,
                        COALESCE((
                            SELECT GROUP_CONCAT(CONCAT(eip.name, CHAR(32,120), ei.qty) ORDER BY ei.id SEPARATOR \', \')
                            FROM exchange_items ei
                            INNER JOIN products eip ON eip.id = ei.product_id
                            WHERE ei.return_exchange_id = re.id
                        ), ep.name, "-") AS exchange_product,
                        COALESCE(ep.style_code, "-") AS exchange_style_code,
                        COALESCE(ep.size, "-") AS exchange_size,
                        COALESCE(ep.color, "-") AS exchange_color,
                        CASE
                            WHEN re.exchange_product_id IS NULL THEN NULL
                            ELSE CAST((si.unit_price + (re.price_difference / NULLIF(re.qty, 0))) AS DECIMAL(10,2))
                        END AS exchange_unit_price,
                        re.price_difference,
                        re.reason,
                        u.full_name AS processed_by
                    FROM returns_exchanges re
                    INNER JOIN sale_items si ON si.id = re.sale_item_id
                    INNER JOIN products rp ON rp.id = si.product_id
                    LEFT JOIN products ep ON ep.id = re.exchange_product_id
                    INNER JOIN branches b ON b.id = re.branch_id
                    INNER JOIN users u ON u.id = re.processed_by
                    WHERE re.created_at BETWEEN :date_from AND :date_to' . $branchFilterAlias['re'] . '
                    ORDER BY re.created_at DESC, re.id DESC';
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
        Response::error('Could not generate report: ' . $exception->getMessage(), 500);
    }

    Response::json([
        'title' => $title,
        'type' => $reportType,
        'branch_id' => $branchId,
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
        'branches' => $branches,
        'rows' => $rows,
    ]);
}
