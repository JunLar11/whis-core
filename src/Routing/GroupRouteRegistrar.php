<?php

namespace Whis\Routing;

use Closure;
use InvalidArgumentException;
use Whis\Http\HttpMethod;

class GroupRouteRegistrar
{
    public function __construct(
        protected Router $router
    ) {
    }

    public function routes(array $definitions): array
    {
        $registered = [];

        foreach ($definitions as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            $key = strtolower($key);

            $result = match ($key) {
                'get'         => $this->registerMany(HttpMethod::GET, $value),
                'post'        => $this->registerMany(HttpMethod::POST, $value),
                'put'         => $this->registerMany(HttpMethod::PUT, $value),
                'patch'       => $this->registerMany(HttpMethod::PATCH, $value),
                'delete'      => $this->registerMany(HttpMethod::DELETE, $value),
                'controller',
                'controllers' => $this->registerControllers($value),
                'group',
                'groups'      => $this->registerGroups($value),
                default       => [],
            };

            if ($result instanceof Route) {
                $registered[] = $result;
                continue;
            }

            if (is_array($result)) {
                $registered = array_merge($registered, $result);
            }
        }

        return $registered;
    }

    protected function registerMany(HttpMethod $method, mixed $routes): array
    {
        if (!is_array($routes)) {
            throw new InvalidArgumentException(
                "Group routes for method [{$method->value}] must be an array."
            );
        }

        $registered = [];

        /*
         * Forma asociativa:
         *
         * 'get' => [
         *     '' => [Home::class, 'create'],
         *     '/form' => function () {},
         *     '/profile' => [
         *         'controller' => UserController::class,
         *         'method' => 'profile',
         *         'middlewares' => [AuthMiddleware::class],
         *     ],
         * ]
         */
        if ($this->isAssociativeArray($routes)) {
            foreach ($routes as $uri => $definition) {
                if (!is_string($uri)) {
                    throw new InvalidArgumentException(
                        'Group route URI must be a string.'
                    );
                }

                [$action, $middlewares] = $this->parseRouteDefinition($definition);

                $route = $this->registerSingle($method, $uri, $action);

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
         * 'get' => [
         *     '',
         *     [Home::class, 'create'],
         *
         *     '/form',
         *     function () {},
         * ]
         */
        for ($i = 0; $i < count($routes); $i += 2) {
            $uri = $routes[$i] ?? null;
            $definition = $routes[$i + 1] ?? null;

            if (!is_string($uri)) {
                throw new InvalidArgumentException(
                    'Group route URI must be a string.'
                );
            }

            [$action, $middlewares] = $this->parseRouteDefinition($definition);

            $route = $this->registerSingle($method, $uri, $action);

            if (!empty($middlewares)) {
                $route->middleware($middlewares);
            }

            $registered[] = $route;
        }

        return $registered;
    }

    protected function registerSingle(
        HttpMethod $method,
        string $uri,
        Closure|array $action
    ): Route {
        return match ($method) {
            HttpMethod::GET    => $this->router->get($uri, $action),
            HttpMethod::POST   => $this->router->post($uri, $action),
            HttpMethod::PUT    => $this->router->put($uri, $action),
            HttpMethod::PATCH  => $this->router->patch($uri, $action),
            HttpMethod::DELETE => $this->router->delete($uri, $action),
        };
    }

    protected function parseRouteDefinition(mixed $definition): array
    {
        /*
         * Closure normal:
         *
         * '/form' => function () {
         *     return view('form');
         * }
         */
        if ($definition instanceof Closure) {
            return [$definition, []];
        }

        /*
         * Acción normal:
         *
         * '/home' => [Home::class, 'create']
         */
        if (is_array($definition) && $this->isCallableArray($definition)) {
            return [$definition, []];
        }

        /*
         * Forma avanzada:
         *
         * '/profile' => [
         *     'action' => [UserController::class, 'profile'],
         *     'middlewares' => [AuthMiddleware::class],
         * ]
         *
         * '/profile' => [
         *     'controller' => UserController::class,
         *     'method' => 'profile',
         *     'middlewares' => [AuthMiddleware::class],
         * ]
         */
        if (is_array($definition) && $this->isRouteConfigArray($definition)) {
            $action = $this->actionFromConfig($definition);

            $middlewares = $definition['middleware']
                ?? $definition['middlewares']
                ?? [];

            return [
                $action,
                $this->middlewaresFromMixed($middlewares),
            ];
        }

        throw new InvalidArgumentException(
            'Group routes must use Closure actions, [Controller::class, method], or route config arrays.'
        );
    }

    protected function actionFromConfig(array $definition): Closure|array
    {
        $action = $definition['action']
            ?? $definition['uses']
            ?? null;

        if ($action instanceof Closure) {
            return $action;
        }

        if (is_array($action) && $this->isCallableArray($action)) {
            return $action;
        }

        $controller = $definition['controller'] ?? null;
        $method = $definition['method'] ?? null;

        if (is_string($controller) && is_string($method) && $method !== '') {
            return [$controller, $method];
        }

        throw new InvalidArgumentException(
            'Group route config requires action, uses, or controller + method.'
        );
    }

    protected function registerControllers(mixed $definitions): array
    {
        if (!is_array($definitions)) {
            throw new InvalidArgumentException(
                'Group controllers definition must be an array.'
            );
        }

        $registered = [];

        /*
         * Forma asociativa:
         *
         * 'controllers' => [
         *     Dashboard::class => [
         *         'prefix' => '/dashboard',
         *         'middlewares' => [AuthMiddleware::class],
         *         'routes' => [
         *             'get' => [
         *                 '' => 'create',
         *             ],
         *         ],
         *     ],
         * ]
         */
        if ($this->isAssociativeArray($definitions)) {
            foreach ($definitions as $controller => $definition) {
                if (!is_string($controller)) {
                    throw new InvalidArgumentException(
                        'Controller class must be a string.'
                    );
                }

                $registered = array_merge(
                    $registered,
                    $this->registerController($controller, $definition)
                );
            }

            return $registered;
        }

        /*
         * Forma lista:
         *
         * 'controllers' => [
         *     [
         *         'controller' => Dashboard::class,
         *         'prefix' => '/dashboard',
         *         'routes' => [...],
         *     ],
         * ]
         */
        foreach ($definitions as $definition) {
            if (!is_array($definition)) {
                throw new InvalidArgumentException(
                    'Controller group item must be an array.'
                );
            }

            $controller = $definition['controller'] ?? null;

            if (!is_string($controller)) {
                throw new InvalidArgumentException(
                    'Controller group item requires controller.'
                );
            }

            $registered = array_merge(
                $registered,
                $this->registerController($controller, $definition)
            );
        }

        return $registered;
    }

    protected function registerController(string $controller, mixed $definition): array
    {
        if (!is_array($definition)) {
            throw new InvalidArgumentException(
                "Controller definition for [$controller] must be an array."
            );
        }

        $prefix = $definition['prefix'] ?? '';
        $middlewares = $definition['middleware']
            ?? $definition['middlewares']
            ?? [];

        /*
         * Puedes usar:
         *
         * 'routes' => [
         *     'get' => [...]
         * ]
         *
         * O directamente:
         *
         * 'get' => [...]
         * 'post' => [...]
         */
        $routes = $definition['routes'] ?? $this->extractControllerRoutes($definition);

        if (!is_string($prefix)) {
            throw new InvalidArgumentException(
                "Controller prefix for [$controller] must be a string."
            );
        }

        if (!is_array($routes)) {
            throw new InvalidArgumentException(
                "Controller routes for [$controller] must be an array."
            );
        }

        $this->router->controller(
            $controller,
            $prefix,
            $routes,
            $middlewares
        );

        /*
         * Route::controller() actualmente retorna void.
         * Por eso aquí regresamos [].
         */
        return [];
    }

    protected function extractControllerRoutes(array $definition): array
    {
        $routes = [];

        foreach (['get', 'post', 'put', 'patch', 'delete'] as $method) {
            if (array_key_exists($method, $definition)) {
                $routes[$method] = $definition[$method];
            }

            $upper = strtoupper($method);

            if (array_key_exists($upper, $definition)) {
                $routes[$method] = $definition[$upper];
            }
        }

        return $routes;
    }

    protected function registerGroups(mixed $definitions): array
    {
        if (!is_array($definitions)) {
            throw new InvalidArgumentException(
                'Nested groups definition must be an array.'
            );
        }

        $registered = [];

        /*
         * Forma asociativa:
         *
         * 'groups' => [
         *     '/admin' => [
         *         'middlewares' => [AdminMiddleware::class],
         *         'routes' => [
         *             'get' => [...],
         *         ],
         *     ],
         * ]
         */
        if ($this->isAssociativeArray($definitions)) {
            foreach ($definitions as $prefix => $definition) {
                if (!is_string($prefix)) {
                    throw new InvalidArgumentException(
                        'Nested group prefix must be a string.'
                    );
                }

                $registered = array_merge(
                    $registered,
                    $this->registerGroup($prefix, $definition)
                );
            }

            return $registered;
        }

        /*
         * Forma lista:
         *
         * 'groups' => [
         *     [
         *         'prefix' => '/admin',
         *         'middlewares' => [AdminMiddleware::class],
         *         'routes' => [...],
         *     ],
         * ]
         */
        foreach ($definitions as $definition) {
            if (!is_array($definition)) {
                throw new InvalidArgumentException(
                    'Nested group item must be an array.'
                );
            }

            $prefix = $definition['prefix'] ?? null;

            if (!is_string($prefix)) {
                throw new InvalidArgumentException(
                    'Nested group item requires prefix.'
                );
            }

            $registered = array_merge(
                $registered,
                $this->registerGroup($prefix, $definition)
            );
        }

        return $registered;
    }

    protected function registerGroup(string $prefix, mixed $definition): array
    {
        if (!is_array($definition)) {
            throw new InvalidArgumentException(
                "Nested group definition for [$prefix] must be an array."
            );
        }

        $middlewares = $definition['middleware']
            ?? $definition['middlewares']
            ?? [];

        $routes = $definition['routes'] ?? $this->extractGroupRoutes($definition);

        if (!is_array($routes)) {
            throw new InvalidArgumentException(
                "Nested group routes for [$prefix] must be an array."
            );
        }

        $result = $this->router->group($prefix, $routes, $middlewares);

        return is_array($result) ? $result : [];
    }

    protected function extractGroupRoutes(array $definition): array
    {
        $routes = [];

        foreach ([
            'get',
            'post',
            'put',
            'patch',
            'delete',
            'controller',
            'controllers',
            'group',
            'groups',
        ] as $key) {
            if (array_key_exists($key, $definition)) {
                $routes[$key] = $definition[$key];
            }

            $upper = strtoupper($key);

            if (array_key_exists($upper, $definition)) {
                $routes[$key] = $definition[$upper];
            }
        }

        return $routes;
    }

    protected function isCallableArray(array $action): bool
    {
        return count($action) === 2
            && isset($action[0], $action[1])
            && (is_string($action[0]) || is_object($action[0]))
            && is_string($action[1]);
    }

    protected function isRouteConfigArray(array $definition): bool
    {
        return array_key_exists('action', $definition)
            || array_key_exists('uses', $definition)
            || array_key_exists('controller', $definition)
            || array_key_exists('method', $definition)
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
            'Middlewares must be string or array.'
        );
    }

    protected function isAssociativeArray(array $array): bool
    {
        if ($array === []) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }
}