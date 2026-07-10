<?php

declare(strict_types=1);

$user = currentUser();

if ($user === null) {
    return;
}

$role = $user['role'];
$current = basename($_SERVER['PHP_SELF'] ?? '');
$currentDir = basename(dirname($_SERVER['PHP_SELF'] ?? ''));

function navActive(string $dir, string $currentDir): string
{
    return $dir === $currentDir ? 'active' : '';
}
?>
<div class="col-md-3 col-lg-2 sidebar p-3">
    <div class="list-group">
        <a href="<?= e(url('dashboard.php')) ?>" class="list-group-item list-group-item-action <?= $current === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>

        <?php if ($role === 'owner'): ?>
            <a href="<?= e(url('branches/index.php')) ?>" class="list-group-item list-group-item-action <?= navActive('branches', $currentDir) ?>">Branches</a>
            <a href="<?= e(url('users/index.php')) ?>" class="list-group-item list-group-item-action <?= navActive('users', $currentDir) ?>">Branch Admins</a>
            <a href="<?= e(url('inventory/index.php')) ?>" class="list-group-item list-group-item-action <?= navActive('inventory', $currentDir) ?>">All Inventory</a>
            <a href="<?= e(url('suppliers/index.php')) ?>" class="list-group-item list-group-item-action <?= navActive('suppliers', $currentDir) ?>">Suppliers</a>
            <a href="<?= e(url('sales/index.php')) ?>" class="list-group-item list-group-item-action <?= navActive('sales', $currentDir) ?>">All Sales</a>
            <a href="<?= e(url('reports/index.php')) ?>" class="list-group-item list-group-item-action <?= navActive('reports', $currentDir) ?>">Reports</a>
        <?php endif; ?>

        <?php if ($role === 'branch_admin'): ?>
            <a href="<?= e(url('users/index.php')) ?>" class="list-group-item list-group-item-action <?= navActive('users', $currentDir) ?>">Staff</a>
            <a href="<?= e(url('inventory/index.php')) ?>" class="list-group-item list-group-item-action <?= navActive('inventory', $currentDir) ?>">Branch Inventory</a>
            <a href="<?= e(url('suppliers/index.php')) ?>" class="list-group-item list-group-item-action <?= navActive('suppliers', $currentDir) ?>">Suppliers</a>
            <a href="<?= e(url('settings/index.php')) ?>" class="list-group-item list-group-item-action <?= navActive('settings', $currentDir) ?>">Branch Settings</a>
            <a href="<?= e(url('sales/index.php')) ?>" class="list-group-item list-group-item-action <?= navActive('sales', $currentDir) ?>">Branch Sales</a>
            <a href="<?= e(url('reports/index.php')) ?>" class="list-group-item list-group-item-action <?= navActive('reports', $currentDir) ?>">Reports</a>
        <?php endif; ?>

        <?php if ($role === 'storekeeper'): ?>
            <a href="<?= e(url('inventory/index.php')) ?>" class="list-group-item list-group-item-action <?= navActive('inventory', $currentDir) ?>">Inventory</a>
            <a href="<?= e(url('suppliers/index.php')) ?>" class="list-group-item list-group-item-action <?= navActive('suppliers', $currentDir) ?>">Suppliers</a>
            <a href="<?= e(url('purchase_orders/index.php')) ?>" class="list-group-item list-group-item-action <?= navActive('purchase_orders', $currentDir) ?>">Purchase Orders</a>
        <?php endif; ?>

        <?php if ($role === 'sales_assistant'): ?>
            <a href="<?= e(url('search/index.php')) ?>" class="list-group-item list-group-item-action <?= navActive('search', $currentDir) ?>">Stock Search</a>
            <a href="<?= e(url('reservations/index.php')) ?>" class="list-group-item list-group-item-action <?= navActive('reservations', $currentDir) ?>">Reservations</a>
        <?php endif; ?>

        <?php if ($role === 'cashier'): ?>
            <a href="<?= e(url('pos/index.php')) ?>" class="list-group-item list-group-item-action <?= navActive('pos', $currentDir) ?>">Point of Sale</a>
            <a href="<?= e(url('customers/index.php')) ?>" class="list-group-item list-group-item-action <?= navActive('customers', $currentDir) ?>">Customers</a>
            <a href="<?= e(url('returns/index.php')) ?>" class="list-group-item list-group-item-action <?= navActive('returns', $currentDir) ?>">Returns & Exchanges</a>
            <a href="<?= e(url('sales/index.php')) ?>" class="list-group-item list-group-item-action <?= navActive('sales', $currentDir) ?>">My Sales</a>
        <?php endif; ?>
    </div>
</div>
