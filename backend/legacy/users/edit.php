<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

requireRole(['owner', 'branch_admin']);

$user = currentUser();
$db = getDb();
$isOwner = $user['role'] === 'owner';
$id = (int) ($_GET['id'] ?? 0);
$error = '';

$stmt = $db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $id]);
$targetUser = $stmt->fetch();

if ($targetUser === false) {
    setFlash('danger', 'User not found.');
    redirect(url('users/index.php'));
}

if ($isOwner && $targetUser['role'] !== 'branch_admin') {
    setFlash('danger', 'You can only edit branch admins.');
    redirect(url('users/index.php'));
}

if (!$isOwner) {
    if ((int) $targetUser['branch_id'] !== requireUserBranchId()) {
        setFlash('danger', 'You cannot edit users from another branch.');
        redirect(url('users/index.php'));
    }

    if (!in_array($targetUser['role'], ['storekeeper', 'sales_assistant', 'cashier'], true)) {
        setFlash('danger', 'You cannot edit this user.');
        redirect(url('users/index.php'));
    }
}

$branches = $isOwner
    ? $db->query('SELECT id, name FROM branches ORDER BY name')->fetchAll()
    : [];

if (isPost()) {
    $fullName = postString('full_name');
    $username = postString('username');
    $password = postString('password');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $branchId = $isOwner ? postInt('branch_id') : (int) $targetUser['branch_id'];

    if ($fullName === '' || $username === '') {
        $error = 'Name and username are required.';
    } elseif ($branchId <= 0) {
        $error = 'Branch is required.';
    } else {
        try {
            if ($password !== '') {
                $update = $db->prepare(
                    'UPDATE users
                     SET full_name = :full_name, username = :username, branch_id = :branch_id,
                         password_hash = :password_hash, is_active = :is_active
                     WHERE id = :id'
                );
                $update->execute([
                    'full_name' => $fullName,
                    'username' => $username,
                    'branch_id' => $branchId,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'is_active' => $isActive,
                    'id' => $id,
                ]);
            } else {
                $update = $db->prepare(
                    'UPDATE users
                     SET full_name = :full_name, username = :username, branch_id = :branch_id, is_active = :is_active
                     WHERE id = :id'
                );
                $update->execute([
                    'full_name' => $fullName,
                    'username' => $username,
                    'branch_id' => $branchId,
                    'is_active' => $isActive,
                    'id' => $id,
                ]);
            }

            setFlash('success', 'User updated successfully.');
            redirect(url('users/index.php'));
        } catch (PDOException) {
            $error = 'Username already exists.';
        }
    }
}

$pageTitle = 'Edit User';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="col-md-9 col-lg-10 p-4">
            <h1 class="h3 mb-4">Edit User</h1>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label" for="full_name">Full Name</label>
                            <input class="form-control" id="full_name" name="full_name" value="<?= e($targetUser['full_name']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="username">Username</label>
                            <input class="form-control" id="username" name="username" value="<?= e($targetUser['username']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="password">New Password (optional)</label>
                            <input type="password" class="form-control" id="password" name="password">
                        </div>
                        <?php if ($isOwner): ?>
                            <div class="mb-3">
                                <label class="form-label" for="branch_id">Branch</label>
                                <select class="form-select" id="branch_id" name="branch_id" required>
                                    <?php foreach ($branches as $branch): ?>
                                        <option value="<?= (int) $branch['id'] ?>" <?= (int) $targetUser['branch_id'] === (int) $branch['id'] ? 'selected' : '' ?>>
                                            <?= e($branch['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" <?= (int) $targetUser['is_active'] === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                        <button type="submit" class="btn btn-primary">Update User</button>
                        <a href="<?= e(url('users/index.php')) ?>" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
