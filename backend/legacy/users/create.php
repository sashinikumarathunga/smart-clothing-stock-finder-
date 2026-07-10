<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

requireRole(['owner', 'branch_admin']);

$user = currentUser();
$db = getDb();
$isOwner = $user['role'] === 'owner';
$error = '';

$branches = $isOwner
    ? $db->query('SELECT id, name FROM branches ORDER BY name')->fetchAll()
    : [];

$allowedRoles = $isOwner
    ? ['branch_admin']
    : ['storekeeper', 'sales_assistant', 'cashier'];

if (isPost()) {
    $fullName = postString('full_name');
    $username = postString('username');
    $password = postString('password');
    $role = postString('role');
    $branchId = $isOwner ? postInt('branch_id') : requireUserBranchId();

    if ($fullName === '' || $username === '' || $password === '' || !in_array($role, $allowedRoles, true)) {
        $error = 'Please fill all required fields correctly.';
    } elseif ($branchId <= 0) {
        $error = 'Branch is required.';
    } elseif (!canManageRole($role)) {
        $error = 'You cannot create this role.';
    } else {
        try {
            $stmt = $db->prepare(
                'INSERT INTO users (branch_id, role, full_name, username, password_hash, is_active)
                 VALUES (:branch_id, :role, :full_name, :username, :password_hash, 1)'
            );
            $stmt->execute([
                'branch_id' => $branchId,
                'role' => $role,
                'full_name' => $fullName,
                'username' => $username,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);

            setFlash('success', 'User created successfully.');
            redirect(url('users/index.php'));
        } catch (PDOException) {
            $error = 'Username already exists.';
        }
    }
}

$pageTitle = 'Add User';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="col-md-9 col-lg-10 p-4">
            <h1 class="h3 mb-4">Add User</h1>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label" for="full_name">Full Name</label>
                            <input class="form-control" id="full_name" name="full_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="username">Username</label>
                            <input class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="password">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="role">Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <?php foreach ($allowedRoles as $roleOption): ?>
                                    <option value="<?= e($roleOption) ?>"><?= e(roleLabel($roleOption)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if ($isOwner): ?>
                            <div class="mb-3">
                                <label class="form-label" for="branch_id">Branch</label>
                                <select class="form-select" id="branch_id" name="branch_id" required>
                                    <option value="">Select branch</option>
                                    <?php foreach ($branches as $branch): ?>
                                        <option value="<?= (int) $branch['id'] ?>"><?= e($branch['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-primary">Save User</button>
                        <a href="<?= e(url('users/index.php')) ?>" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
