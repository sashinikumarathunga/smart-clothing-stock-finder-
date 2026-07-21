<?php

declare(strict_types=1);

namespace SmartStock\Controllers;

use SmartStock\Contracts\ControllerInterface;
use SmartStock\Http\JsonResponse;

abstract class AbstractController implements ControllerInterface
{
    public function __construct(protected readonly JsonResponse $response) {}

    protected function id(array $parameters, string $key = 'id'): int
    {
        return (int)($parameters[$key] ?? 0);
    }
}
