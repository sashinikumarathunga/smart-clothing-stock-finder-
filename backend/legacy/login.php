<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (currentUser() !== null) {
    redirect(url('dashboard.php'));
}

$error = '';

if (isPost()) {
    $username = postString('username');
    $password = postString('password');

    if ($username === '' || $password === '') {
        $error = 'Username and password are required.';
    } elseif (!loginUser($username, $password)) {
        $error = 'Invalid username or password.';
    } else {
        redirect(url('dashboard.php'));
    }
}

$pageTitle = 'Login';
require_once __DIR__ . '/includes/header.php';
?>
<div class="login-wrapper">
    <div class="card shadow-sm" style="width: 100%; max-width: 420px;">
        <div class="card-body p-4">
            <h1 class="h4 mb-1">Smart Clothing Stock Finder</h1>
            <p class="text-muted mb-4">Sign in to continue</p>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" action="">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required autofocus>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
