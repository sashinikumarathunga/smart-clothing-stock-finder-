<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

requireRole(['owner']);

$id = (int) ($_GET['id'] ?? 0);

$stmt = getDb()->prepare('SELECT id FROM branches WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $id]);

if ($stmt->fetch() === false) {
    setFlash('danger', 'Branch not found.');
    redirect(url('branches/index.php'));
}

$delete = getDb()->prepare('DELETE FROM branches WHERE id = :id');
$delete->execute(['id' => $id]);

setFlash('success', 'Branch deleted successfully.');
redirect(url('branches/index.php'));
