<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

requireRole(['owner', 'branch_admin']);

$user = currentUser();
$db = getDb();
$isOwner = $user['role'] === 'owner';
$id = (int) ($_GET['id'] ?? 0);

$stmt = $db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $id]);
$targetUser = $stmt->fetch();

if ($targetUser === false) {
    setFlash('danger', 'User not found.');
    redirect(url('users/index.php'));
}

if ((int) $targetUser['id'] === (int) $user['id']) {
    setFlash('danger', 'You cannot delete your own account.');
    redirect(url('users/index.php'));
}

if ($isOwner && $targetUser['role'] !== 'branch_admin') {
    setFlash('danger', 'You can only delete branch admins.');
    redirect(url('users/index.php'));
}

if (!$isOwner) {
    if ((int) $targetUser['branch_id'] !== requireUserBranchId()) {
        setFlash('danger', 'You cannot delete users from another branch.');
        redirect(url('users/index.php'));
    }

    if (!in_array($targetUser['role'], ['storekeeper', 'sales_assistant', 'cashier'], true)) {
        setFlash('danger', 'You cannot delete this user.');
        redirect(url('users/index.php'));
    }
}

$delete = $db->prepare('DELETE FROM users WHERE id = :id');
$delete->execute(['id' => $id]);

setFlash('success', 'User deleted successfully.');
redirect(url('users/index.php'));
