<?php

declare(strict_types=1);

namespace SmartStock\Routing;

use SmartStock\Contracts\ControllerInterface;
use SmartStock\Http\JsonResponse;
use SmartStock\Http\Request;

final class Router
{
    /** @var list<Route> */
    private array $routes = [];

    public function __construct(private readonly JsonResponse $response) {}

    public function add(string $method, string $pattern, ControllerInterface $controller): self
    {
        $this->routes[] = new Route(strtoupper($method), $pattern, $controller);
        return $this;
    }

    public function dispatch(Request $request): never
    {
        foreach ($this->routes as $route) {
            $parameters = $route->match($request->method(), $request->path());
            if ($parameters !== null) {
                ($route->controller())($request, $parameters);
            }
        }
        $this->response->notFound('Route not found: ' . $request->method() . ' ' . $request->path());
    }
}
