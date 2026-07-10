<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

logoutUser();
redirect(url('login.php'));
