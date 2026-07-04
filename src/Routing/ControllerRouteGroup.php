<?php

namespace Whis\Routing;

use Closure;
use InvalidArgumentException;
use ReflectionFunction;
use Whis\Http\HttpMethod;

class ControllerRouteGroup
{
    public function __construct(
        protected Router $router,
        protected string $controller
    ) {
    }

    public function get(
        string|array $uri,
        string|array|null $action = null,
        array|string $middlewares = []
    ): Route|array {
        return $this->register(HttpMethod::GET, $uri, $action, $middlewares);
    }

    public function post(
        string|array $uri,
        string|array|null $action = null,
        array|string $middlewares = []
    ): Route|array {
        return $this->register(HttpMethod::POST, $uri, $action, $middlewares);
    }

    public function put(
        string|array $uri,
        string|array|null $action = null,
        array|string $middlewares = []
    ): Route|array {
        return $this->register(HttpMethod::PUT, $uri, $action, $middlewares);
    }

    public function patch(
        string|array $uri,
        string|array|null $action = null,
        array|string $middlewares = []
    ): Route|array {
        return $this->register(HttpMethod::PATCH, $uri, $action, $middlewares);
    }

    public function delete(
        string|array $uri,
        string|array|null $action = null,
        array|string $middlewares = []
    ): Route|array {
        return $this->register(HttpMethod::DELETE, $uri, $action, $middlewares);
    }

    public function routes(array $definitions): array
    {
        $registered = [];

        foreach ($definitions as $method => $routes) {
            if (!is_string($method)) {
                continue;
            }

            if (!is_array($routes)) {
                throw new InvalidArgumentException(
                    "Controller routes for method [$method] must be an array."
                );
            }

            $method = strtoupper($method);

            $result = match ($method) {
                'GET'    => $this->get($routes),
                'POST'   => $this->post($routes),
                'PUT'    => $this->put($routes),
                'PATCH'  => $this->patch($routes),
                'DELETE' => $this->delete($routes),
                default  => throw new InvalidArgumentException(
                    "Unsupported controller route method [$method]."
                ),
            };

            $registered = array_merge(
                $registered,
                is_array($result) ? $result : [$result]
            );
        }

        return $registered;
    }

    public function group(
        string $prefix,
        Closure $callback,
        array|string $middlewares = []
    ): void {
        $this->router->group($prefix, function () use ($callback) {
            $this->callCallback($callback);
        }, $middlewares);
    }

    protected function register(
        HttpMethod $method,
        string|array $uri,
        string|array|null $action = null,
        array|string $middlewares = []
    ): Route|array {
        /*
         * Forma:
         *
         * $route->get([
         *     '' => 'create',
         *     '/profile' => 'profile',
         * ]);
         *
         * O:
         *
         * get([
         *     '' => 'create',
         *     '/profile' => 'profile',
         * ]);
         */
        if (is_array($uri)) {
            return $this->registerMany($method, $uri, $action);
        }

        if ($action === null) {
            throw new InvalidArgumentException(
                "{$method->value} controller route action is required."
            );
        }

        [$methodName, $routeMiddlewares] = $this->parseRouteDefinition($action);

        $route = $this->registerSingle(
            $method,
            $uri,
            $this->normalizeAction($methodName)
        );

        $middlewares = array_merge(
            $this->middlewaresFromMixed($middlewares),
            $routeMiddlewares
        );

        if (!empty($middlewares)) {
            $route->middleware($middlewares);
        }

        return $route;
    }

    protected function registerSingle(
        HttpMethod $method,
        string $uri,
        array $action
    ): Route {
        return match ($method) {
            HttpMethod::GET    => $this->router->get($uri, $action),
            HttpMethod::POST   => $this->router->post($uri, $action),
            HttpMethod::PUT    => $this->router->put($uri, $action),
            HttpMethod::PATCH  => $this->router->patch($uri, $action),
            HttpMethod::DELETE => $this->router->delete($uri, $action),
        };
    }

    protected function registerMany(
        HttpMethod $method,
        array $routes,
        array|string|null $commonMiddlewares = null
    ): array {
        $registered = [];

        $commonMiddlewares = $this->middlewaresFromMixed($commonMiddlewares);

        /*
         * Forma asociativa:
         *
         * [
         *     '' => 'create',
         *     '/profile' => 'profile',
         *     '/admin-only' => [
         *         'method' => 'adminOnly',
         *         'middlewares' => [AdminMiddleware::class],
         *     ],
         * ]
         */
        if ($this->isAssociativeArray($routes)) {
            foreach ($routes as $uri => $definition) {
                if (!is_string($uri)) {
                    throw new InvalidArgumentException(
                        'Controller route URI must be a string.'
                    );
                }

                [$methodName, $routeMiddlewares] = $this->parseRouteDefinition($definition);

                $route = $this->registerSingle(
                    $method,
                    $uri,
                    $this->normalizeAction($methodName)
                );

                $middlewares = array_merge(
                    $commonMiddlewares,
                    $routeMiddlewares
                );

                if (!empty($middlewares)) {
                    $route->middleware($middlewares);
                }

                $registered[] = $route;
            }

            return $registered;
        }

        /*
         * Forma por pares:
         *
         * [
         *     '',
         *     'create',
         *
         *     '/profile',
         *     'profile',
         * ]
         */
        for ($i = 0; $i < count($routes); $i += 2) {
            $uri = $routes[$i] ?? null;
            $definition = $routes[$i + 1] ?? null;

            if (!is_string($uri)) {
                throw new InvalidArgumentException(
                    'Controller route URI must be a string.'
                );
            }

            [$methodName, $routeMiddlewares] = $this->parseRouteDefinition($definition);

            $route = $this->registerSingle(
                $method,
                $uri,
                $this->normalizeAction($methodName)
            );

            $middlewares = array_merge(
                $commonMiddlewares,
                $routeMiddlewares
            );

            if (!empty($middlewares)) {
                $route->middleware($middlewares);
            }

            $registered[] = $route;
        }

        return $registered;
    }

    protected function normalizeAction(string $method): array
    {
        if ($method === '') {
            throw new InvalidArgumentException(
                'Controller route method cannot be empty.'
            );
        }

        return [$this->controller, $method];
    }

    protected function parseRouteDefinition(mixed $definition): array
    {
        /*
         * Forma simple:
         *
         * '/profile' => 'profile'
         */
        if (is_string($definition)) {
            return [$definition, []];
        }

        /*
         * Forma avanzada:
         *
         * '/admin-only' => [
         *     'method' => 'adminOnly',
         *     'middlewares' => [AdminMiddleware::class],
         * ]
         */
        if (is_array($definition) && $this->isRouteConfigArray($definition)) {
            $method = $definition['method']
                ?? $definition['uses']
                ?? $definition['action']
                ?? null;

            if (!is_string($method) || $method === '') {
                throw new InvalidArgumentException(
                    'Controller route config requires a valid string method, uses or action.'
                );
            }

            $middlewares = $definition['middleware']
                ?? $definition['middlewares']
                ?? [];

            return [
                $method,
                $this->middlewaresFromMixed($middlewares),
            ];
        }

        throw new InvalidArgumentException(
            'Controller routes only accept controller method names as strings.'
        );
    }

    protected function isRouteConfigArray(array $definition): bool
    {
        return array_key_exists('method', $definition)
            || array_key_exists('uses', $definition)
            || array_key_exists('action', $definition)
            || array_key_exists('middleware', $definition)
            || array_key_exists('middlewares', $definition);
    }

    protected function middlewaresFromMixed(mixed $middlewares): array
    {
        if ($middlewares === null || $middlewares === '') {
            return [];
        }

        if (is_string($middlewares)) {
            return [$middlewares];
        }

        if (is_array($middlewares)) {
            return $middlewares;
        }

        throw new InvalidArgumentException(
            'Controller route middlewares must be string or array.'
        );
    }

    protected function callCallback(Closure $callback): void
    {
        $reflection = new ReflectionFunction($callback);

        if ($reflection->getNumberOfParameters() > 0) {
            $callback($this);
            return;
        }

        $callback();
    }

    protected function isAssociativeArray(array $array): bool
    {
        if ($array === []) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }
}