<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

requireRole(['owner', 'branch_admin', 'storekeeper']);

$suppliers = getDb()->query('SELECT * FROM suppliers ORDER BY name')->fetchAll();

$pageTitle = 'Suppliers';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="col-md-9 col-lg-10 p-4">
            <?php $flash = getFlash(); if ($flash !== null): ?>
                <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Suppliers</h1>
                <?php if (in_array(currentUser()['role'], ['owner', 'branch_admin', 'storekeeper'], true)): ?>
                    <a href="<?= e(url('suppliers/create.php')) ?>" class="btn btn-primary">Add Supplier</a>
                <?php endif; ?>
            </div>

            <div class="card">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Contact</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Address</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($suppliers === []): ?>
                                <tr><td colspan="6" class="text-muted">No suppliers found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <tr>
                                        <td><?= e($supplier['name']) ?></td>
                                        <td><?= e($supplier['contact_person']) ?></td>
                                        <td><?= e($supplier['phone']) ?></td>
                                        <td><?= e($supplier['email']) ?></td>
                                        <td><?= e($supplier['address']) ?></td>
                                        <td>
                                            <a href="<?= e(url('suppliers/edit.php?id=' . $supplier['id'])) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
