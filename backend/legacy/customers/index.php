<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

requireRole(['cashier']);

$user = currentUser();
$branchId = requireUserBranchId();
$db = getDb();
$error = '';

if (isPost()) {
    $fullName = postString('full_name');
    $phone = postString('phone');
    $email = postString('email');

    if ($fullName === '' || $phone === '') {
        $error = 'Name and phone are required.';
    } else {
        try {
            $db->prepare(
                'INSERT INTO customers (branch_id, full_name, phone, email) VALUES (:branch_id, :full_name, :phone, :email)'
            )->execute([
                'branch_id' => $branchId,
                'full_name' => $fullName,
                'phone' => $phone,
                'email' => $email,
            ]);
            setFlash('success', 'Customer registered for loyalty programme.');
            redirect(url('customers/index.php'));
        } catch (PDOException) {
            $error = 'Customer with this phone already exists at this branch.';
        }
    }
}

$customers = $db->prepare(
    'SELECT * FROM customers WHERE branch_id = :branch_id ORDER BY full_name'
);
$customers->execute(['branch_id' => $branchId]);
$customerList = $customers->fetchAll();

$pageTitle = 'Customers';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="col-md-9 col-lg-10 p-4">
            <?php $flash = getFlash(); if ($flash !== null): ?>
                <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
            <?php endif; ?>

            <h1 class="h3 mb-4">Loyalty Customers</h1>
            <?php if ($error !== ''): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">Register Customer</div>
                        <div class="card-body">
                            <form method="post">
                                <div class="mb-3"><label class="form-label">Full Name</label><input class="form-control" name="full_name" required></div>
                                <div class="mb-3"><label class="form-label">Phone</label><input class="form-control" name="phone" required></div>
                                <div class="mb-3"><label class="form-label">Email</label><input type="email" class="form-control" name="email"></div>
                                <button type="submit" class="btn btn-primary w-100">Register</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">Customer List</div>
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead><tr><th>Name</th><th>Phone</th><th>Email</th><th>Points</th></tr></thead>
                                <tbody>
                                    <?php if ($customerList === []): ?>
                                        <tr><td colspan="4" class="text-muted">No customers yet.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($customerList as $customer): ?>
                                            <tr>
                                                <td><?= e($customer['full_name']) ?></td>
                                                <td><?= e($customer['phone']) ?></td>
                                                <td><?= e($customer['email']) ?></td>
                                                <td><?= (int) $customer['loyalty_points'] ?></td>
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
