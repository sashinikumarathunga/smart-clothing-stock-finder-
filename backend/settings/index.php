<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

requireRole(['branch_admin']);

$branchId = requireUserBranchId();
$db = getDb();
$error = '';

$branch = getBranchSettings($db, $branchId);

if (isPost()) {
    $action = postString('action');

    $spendPerPoint = postFloat('loyalty_spend_per_point');
    $pointValue = postFloat('loyalty_point_value');
    $minRedeem = postInt('loyalty_min_redeem');
    $returnDays = postInt('return_period_days');
    $lowStockThreshold = postInt('low_stock_threshold');

    if ($spendPerPoint <= 0 || $pointValue <= 0 || $minRedeem < 1 || $returnDays < 1 || $lowStockThreshold < 1) {
        $error = 'All settings must be valid positive values.';
    } else {
        $db->prepare(
            'UPDATE branches SET
                loyalty_spend_per_point = :spend,
                loyalty_point_value = :value,
                loyalty_min_redeem = :min_redeem,
                return_period_days = :return_days,
                low_stock_threshold = :threshold
             WHERE id = :id'
        )->execute([
            'spend' => $spendPerPoint,
            'value' => $pointValue,
            'min_redeem' => $minRedeem,
            'return_days' => $returnDays,
            'threshold' => $lowStockThreshold,
            'id' => $branchId,
        ]);

        setFlash('success', 'Branch settings updated.');
        redirect(url('settings/index.php'));
    }
}

$pageTitle = 'Branch Settings';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="col-md-9 col-lg-10 p-4">
            <?php $flash = getFlash(); if ($flash !== null): ?>
                <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
            <?php endif; ?>

            <h1 class="h3 mb-4">Branch Settings</h1>
            <?php if ($error !== ''): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

            <div class="card mb-4">
                <div class="card-header">Loyalty & Returns</div>
                <div class="card-body">
                    <form method="post">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Spend per Loyalty Point (Rs.)</label>
                                <input type="number" step="0.01" class="form-control" name="loyalty_spend_per_point" value="<?= e((string) $branch['loyalty_spend_per_point']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Point Redemption Value (Rs.)</label>
                                <input type="number" step="0.01" class="form-control" name="loyalty_point_value" value="<?= e((string) $branch['loyalty_point_value']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Minimum Points to Redeem</label>
                                <input type="number" min="1" class="form-control" name="loyalty_min_redeem" value="<?= (int) $branch['loyalty_min_redeem'] ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Return Period (days)</label>
                                <input type="number" min="1" class="form-control" name="return_period_days" value="<?= (int) $branch['return_period_days'] ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Low Stock Threshold</label>
                                <input type="number" min="1" class="form-control" name="low_stock_threshold" value="<?= (int) $branch['low_stock_threshold'] ?>" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3">Save Settings</button>
                    </form>
                </div>
            </div>

        </main>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
