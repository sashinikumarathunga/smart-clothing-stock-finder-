<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

requireRole(['owner']);

$db = getDb();
$branches = $db->query('SELECT * FROM branches ORDER BY name')->fetchAll();

$pageTitle = 'Branches';
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
                <h1 class="h3 mb-0">Branches</h1>
                <a href="<?= e(url('branches/create.php')) ?>" class="btn btn-primary">Add Branch</a>
            </div>

            <div class="card">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Location</th>
                                <th>Phone</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($branches === []): ?>
                                <tr><td colspan="4" class="text-muted">No branches found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($branches as $branch): ?>
                                    <tr>
                                        <td><?= e($branch['name']) ?></td>
                                        <td><?= e($branch['location']) ?></td>
                                        <td><?= e($branch['phone']) ?></td>
                                        <td>
                                            <a href="<?= e(url('branches/edit.php?id=' . $branch['id'])) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                            <a href="<?= e(url('branches/delete.php?id=' . $branch['id'])) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this branch?');">Delete</a>
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
