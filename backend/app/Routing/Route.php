<?php

declare(strict_types=1);

namespace SmartStock\Routing;

use SmartStock\Contracts\ControllerInterface;

final class Route
{
    public function __construct(
        private readonly string $method,
        private readonly string $pattern,
        private readonly ControllerInterface $controller
    ) {}

    public function match(string $method, string $path): ?array
    {
        if ($method !== $this->method) return null;
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>\d+)', $this->pattern);
        if (preg_match('#^' . $regex . '$#', $path, $matches) !== 1) return null;
        return array_filter($matches, static fn($key) => is_string($key), ARRAY_FILTER_USE_KEY);
    }

    public function controller(): ControllerInterface { return $this->controller; }
}
