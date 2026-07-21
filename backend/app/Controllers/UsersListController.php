<?php

declare(strict_types=1);

namespace SmartStock\Controllers;

use SmartStock\Http\Request;

final class UsersListController extends AbstractController
{
    public function __invoke(Request $request, array $parameters = []): never
    {
        \handleUsersList();
    }
}
