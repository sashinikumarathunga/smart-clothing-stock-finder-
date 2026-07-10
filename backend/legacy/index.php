<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (currentUser() !== null) {
    redirect(url('dashboard.php'));
}

redirect(url('login.php'));
