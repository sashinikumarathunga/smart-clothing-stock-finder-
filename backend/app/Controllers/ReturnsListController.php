<?php

declare(strict_types=1);

namespace SmartStock\Controllers;

use SmartStock\Http\Request;

final class ReturnsListController extends AbstractController
{
    public function __invoke(Request $request, array $parameters = []): never
    {
        \handleReturnsList();
    }
}
