<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

requireRole(['owner', 'branch_admin']);

$user = currentUser();
$db = getDb();
$isOwner = $user['role'] === 'owner';

if ($isOwner) {
    $stmt = $db->query(
        'SELECT u.*, b.name AS branch_name
         FROM users u
         LEFT JOIN branches b ON b.id = u.branch_id
         WHERE u.role = "branch_admin"
         ORDER BY u.full_name'
    );
} else {
    $stmt = $db->prepare(
        'SELECT u.*, b.name AS branch_name
         FROM users u
         LEFT JOIN branches b ON b.id = u.branch_id
         WHERE u.branch_id = :branch_id AND u.role IN ("storekeeper", "sales_assistant", "cashier")
         ORDER BY u.full_name'
    );
    $stmt->execute(['branch_id' => requireUserBranchId()]);
}

$users = $stmt->fetchAll();
$pageTitle = $isOwner ? 'Branch Admins' : 'Staff';

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
                <h1 class="h3 mb-0"><?= e($pageTitle) ?></h1>
                <a href="<?= e(url('users/create.php')) ?>" class="btn btn-primary">Add User</a>
            </div>

            <div class="card">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Branch</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($users === []): ?>
                                <tr><td colspan="6" class="text-muted">No users found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($users as $row): ?>
                                    <tr>
                                        <td><?= e($row['full_name']) ?></td>
                                        <td><?= e($row['username']) ?></td>
                                        <td><?= e(roleLabel($row['role'])) ?></td>
                                        <td><?= e($row['branch_name'] ?? '-') ?></td>
                                        <td><?= (int) $row['is_active'] === 1 ? 'Active' : 'Inactive' ?></td>
                                        <td>
                                            <a href="<?= e(url('users/edit.php?id=' . $row['id'])) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                            <a href="<?= e(url('users/delete.php?id=' . $row['id'])) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this user?');">Delete</a>
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
