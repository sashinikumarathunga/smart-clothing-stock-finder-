<?php

declare(strict_types=1);

namespace SmartStock\Contracts;

use SmartStock\Http\Request;

interface ControllerInterface
{
    public function __invoke(Request $request, array $parameters = []): never;
}
