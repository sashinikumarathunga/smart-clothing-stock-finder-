<?php

declare(strict_types=1);

namespace SmartStock\Controllers;

use SmartStock\Http\Request;

final class SettingsUpdateController extends AbstractController
{
    public function __invoke(Request $request, array $parameters = []): never
    {
        \handleSettingsUpdate();
    }
}
