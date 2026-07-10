<?php

declare(strict_types=1);

function expireReservations(PDO $db): void
{
    try {
        $db->exec(
            "UPDATE reservations SET status = 'expired'
             WHERE status = 'active' AND expires_at <= NOW()"
        );
    } catch (PDOException) {
        // Table may not exist before migration.
    }
}

function getBranchSettings(PDO $db, ?int $branchId): array
{
    $defaults = [
        'loyalty_spend_per_point' => 5000.0,
        'loyalty_point_value' => 100.0,
        'loyalty_min_redeem' => 5,
        'return_period_days' => 7,
        'low_stock_threshold' => 5,
    ];

    if ($branchId === null || $branchId <= 0) {
        return $defaults;
    }

    try {
        $stmt = $db->prepare('SELECT * FROM branches WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $branchId]);
        $branch = $stmt->fetch();
    } catch (PDOException) {
        return $defaults;
    }

    if ($branch === false) {
        return $defaults;
    }

    return $branch;
}

function getReservedQty(PDO $db, int $productId): int
{
    expireReservations($db);

    try {
        $stmt = $db->prepare(
            "SELECT COALESCE(SUM(qty), 0) FROM reservations
             WHERE product_id = :product_id AND status = 'active'"
        );
        $stmt->execute(['product_id' => $productId]);

        return (int) $stmt->fetchColumn();
    } catch (PDOException) {
        return 0;
    }
}

function getAvailableQty(PDO $db, int $productId): int
{
    $stmt = $db->prepare('SELECT quantity FROM products WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $productId]);
    $qty = $stmt->fetchColumn();

    if ($qty === false) {
        return 0;
    }

    return max(0, (int) $qty - getReservedQty($db, $productId));
}

function getLowStockAlerts(PDO $db, ?int $branchId, string $role): array
{
    try {
        $sql = 'SELECT p.*, b.name AS branch_name, b.low_stock_threshold
                FROM products p
                INNER JOIN branches b ON b.id = p.branch_id
                WHERE p.low_stock_alert_enabled = 1
                  AND p.quantity < b.low_stock_threshold';
        $params = [];

        if ($branchId !== null && $branchId > 0) {
            $sql .= ' AND p.branch_id = :branch_id';
            $params['branch_id'] = $branchId;
        }

        $sql .= ' ORDER BY p.quantity ASC, b.name, p.name LIMIT 20';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    } catch (PDOException) {
        return [];
    }
}

function findCustomerByPhone(PDO $db, int $branchId, string $phone): ?array
{
    $stmt = $db->prepare(
        'SELECT * FROM customers WHERE branch_id = :branch_id AND phone = :phone LIMIT 1'
    );
    $stmt->execute(['branch_id' => $branchId, 'phone' => $phone]);
    $customer = $stmt->fetch();

    return $customer === false ? null : $customer;
}

function calculateLoyaltyEarned(float $subtotal, array $branchSettings): int
{
    $spendPerPoint = (float) ($branchSettings['loyalty_spend_per_point'] ?? 5000);

    if ($spendPerPoint <= 0) {
        return 0;
    }

    return (int) floor($subtotal / $spendPerPoint);
}

function calculateLoyaltyDiscount(int $pointsToRedeem, array $branchSettings, float $subtotal): array
{
    $minRedeem = (int) ($branchSettings['loyalty_min_redeem'] ?? 5);
    $pointValue = (float) ($branchSettings['loyalty_point_value'] ?? 100);

    if ($pointsToRedeem < $minRedeem) {
        return ['valid' => false, 'message' => "Minimum {$minRedeem} points required to redeem.", 'discount' => 0.0];
    }

    $discount = $pointsToRedeem * $pointValue;

    if ($discount > $subtotal) {
        $discount = $subtotal;
    }

    return ['valid' => true, 'message' => '', 'discount' => $discount];
}

function formatReportCell(mixed $value): string
{
    if ($value === null) {
        return '';
    }

    if (is_int($value) || is_float($value)) {
        return str_contains((string) $value, '.') ? number_format((float) $value, 2) : (string) $value;
    }

    return (string) $value;
}

function completeReservationsForProduct(PDO $db, int $productId, int $branchId): void
{
    try {
        $db->prepare(
            "UPDATE reservations SET status = 'completed'
             WHERE product_id = :product_id AND branch_id = :branch_id AND status = 'active'"
        )->execute(['product_id' => $productId, 'branch_id' => $branchId]);
    } catch (PDOException) {
        // reservations table may not exist on older schema.
    }
}
