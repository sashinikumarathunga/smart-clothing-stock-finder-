<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

requireRole(['owner']);

$id = (int) ($_GET['id'] ?? 0);

$stmt = getDb()->prepare('SELECT id FROM branches WHERE id = :id AND is_active = 1 LIMIT 1');
$stmt->execute(['id' => $id]);

if ($stmt->fetch() === false) {
    setFlash('danger', 'Branch not found.');
    redirect(url('branches/index.php'));
}

$deactivate = getDb()->prepare('UPDATE branches SET is_active = 0 WHERE id = :id AND is_active = 1');
$deactivate->execute(['id' => $id]);

setFlash('success', 'Branch deactivated successfully.');
redirect(url('branches/index.php'));
