<?php

declare(strict_types=1);

namespace SmartStock\Http;

final class Request
{
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query,
        private readonly array $server
    ) {}

    public static function capture(): self
    {
        return new self(
            \requestMethod(),
            \requestPath(),
            $_GET,
            $_SERVER
        );
    }

    public function method(): string { return $this->method; }
    public function path(): string { return $this->path; }
    public function query(string $key, mixed $default = null): mixed { return $this->query[$key] ?? $default; }
    public function server(string $key, mixed $default = null): mixed { return $this->server[$key] ?? $default; }
}
