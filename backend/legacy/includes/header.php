<?php

declare(strict_types=1);

$user = currentUser();
$pageTitle = $pageTitle ?? 'Smart Clothing Stock Finder';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body>
<?php if ($user !== null): ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand fw-semibold" href="<?= e(url('dashboard.php')) ?>">Smart Stock Finder</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="topNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <span class="navbar-text text-white-50 me-3">
                        <?= e($user['full_name']) ?> (<?= e(roleLabel($user['role'])) ?>)
                    </span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= e(url('logout.php')) ?>">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<?php endif; ?>
