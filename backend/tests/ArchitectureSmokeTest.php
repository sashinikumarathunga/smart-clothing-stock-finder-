<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';
require_once dirname(__DIR__) . '/bootstrap/autoload.php';
require_once dirname(__DIR__) . '/bootstrap/legacy_handlers.php';

$contracts = [
    SmartStock\Contracts\ControllerInterface::class,
    SmartStock\Contracts\ConnectionInterface::class,
];
$classes = [
    SmartStock\Application::class,
    SmartStock\Routing\Router::class,
    SmartStock\Routing\Route::class,
    SmartStock\Http\Request::class,
    SmartStock\Controllers\DashboardController::class,
];
foreach ($contracts as $contract) {
    assert(interface_exists($contract), "Missing interface: {$contract}");
}
foreach ($classes as $class) {
    assert(class_exists($class), "Missing class: {$class}");
}
echo "Architecture smoke test passed.\n";
