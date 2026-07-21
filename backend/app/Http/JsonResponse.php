<?php

declare(strict_types=1);

namespace SmartStock\Http;

final class JsonResponse
{
    public function send(array $payload, int $status = 200): never
    {
        \Response::json($payload, $status);
    }

    public function notFound(string $message): never
    {
        \Response::notFound($message);
    }
}
