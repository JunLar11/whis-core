<?php
namespace Whis\Routing;

use Closure;
use ReflectionFunction;
use Whis\Container\DependencyInjection;
use Whis\Exceptions\HttpNotFoundException;
use Whis\Http\HttpMethod;
use Whis\Http\Request;
use Whis\Http\Response;

class Router
{
    /**
     * @var array<string,array<Route>>
     */
    protected array $routes = [];

    /**
     * Stack de grupos activos.
     *
     * Cada grupo puede tener:
     * - prefix
     * - middlewares
     *
     * @var array<int,array{prefix:string,middlewares:array}>
     */
    protected array $groupStack = [];

    public function __construct()
    {
        foreach (HttpMethod::cases() as $method) {
            $this->routes[$method->value] = [];
        }
    }

    public function resolveRoute(Request $request): Route
    {
        $path = $this->removeQueryStringVariables($request->uri());

        foreach ($this->routes[$request->method()->value] as $route) {
            if ($route->matches($path)) {
                return $route;
            }
        }

        throw new HttpNotFoundException();
    }

    protected function removeQueryStringVariables(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return '/';
        }

        return $path;
    }

    public function resolve(Request $request): Response
    {
        $route = $this->resolveRoute($request);

        $request->setRoute($route);

        $action = $route->action();

        $middlewares = $route->middlewares();

        if (is_array($action)) {
            $controller = new $action[0]();

            $action[0] = $controller;

            $middlewares = array_merge(
                $middlewares,
                $controller->middlewares()
            );
        }

        $parameters = DependencyInjection::resolveParameters(
            $action,
            $request->routeParameters()
        );

        return $this->runMiddlewares(
            $request,
            $middlewares,
            fn() => call_user_func($action, ...$parameters)
        );
    }

    protected function runMiddlewares(Request $request, array $middlewares, Closure $target): Response
    {
        if (count($middlewares) === 0) {
            return $target();
        }

        return $middlewares[0]->handle(
            $request,
            function (Request $request) use ($middlewares, $target) {
                return $this->runMiddlewares(
                    $request,
                    array_slice($middlewares, 1),
                    $target
                );
            }
        );
    }

    protected function registerRoute(HttpMethod $method, string $uri, Closure | array $action): Route
    {
        $uri = $this->applyCurrentGroupPrefix($uri);

        $route = new Route($uri, $action);

        $groupMiddlewares = $this->currentGroupMiddlewares();

        if (! empty($groupMiddlewares)) {
            $route->addMiddlewares($groupMiddlewares);
        }

        $this->routes[$method->value][] = $route;

        return $route;
    }

    protected function registerMany(HttpMethod $method, array $routes): array
    {
        $registered = [];

        /*
         * Forma asociativa:
         *
         * Route::get([
         *     '' => [Home::class, 'create'],
         *     '/form' => fn () => view('form'),
         * ]);
         */
        if ($this->isAssociativeArray($routes)) {
            foreach ($routes as $uri => $action) {
                if (! is_string($uri)) {
                    continue;
                }

                if (! $action instanceof Closure && ! is_array($action)) {
                    continue;
                }

                $registered[] = $this->registerRoute($method, $uri, $action);
            }

            return $registered;
        }

        /*
         * Forma por pares:
         *
         * Route::get([
         *     '',
         *     [Home::class, 'create'],
         *     '/form',
         *     fn () => view('form'),
         * ]);
         */
        for ($i = 0; $i < count($routes); $i += 2) {
            $uri    = $routes[$i] ?? null;
            $action = $routes[$i + 1] ?? null;

            if (! is_string($uri)) {
                continue;
            }

            if (! $action instanceof Closure && ! is_array($action)) {
                continue;
            }

            $registered[] = $this->registerRoute($method, $uri, $action);
        }

        return $registered;
    }

    public function get(string | array $uri, Closure | array | null $action = null): Route | array
    {
        if (is_array($uri)) {
            return $this->registerMany(HttpMethod::GET, $uri);
        }

        if ($action === null) {
            throw new \InvalidArgumentException('GET route action is required.');
        }

        return $this->registerRoute(HttpMethod::GET, $uri, $action);
    }

    public function post(string | array $uri, Closure | array | null $action = null): Route | array
    {
        if (is_array($uri)) {
            return $this->registerMany(HttpMethod::POST, $uri);
        }

        if ($action === null) {
            throw new \InvalidArgumentException('POST route action is required.');
        }

        return $this->registerRoute(HttpMethod::POST, $uri, $action);
    }

    public function put(string | array $uri, Closure | array | null $action = null): Route | array
    {
        if (is_array($uri)) {
            return $this->registerMany(HttpMethod::PUT, $uri);
        }

        if ($action === null) {
            throw new \InvalidArgumentException('PUT route action is required.');
        }

        return $this->registerRoute(HttpMethod::PUT, $uri, $action);
    }

    public function patch(string | array $uri, Closure | array | null $action = null): Route | array
    {
        if (is_array($uri)) {
            return $this->registerMany(HttpMethod::PATCH, $uri);
        }

        if ($action === null) {
            throw new \InvalidArgumentException('PATCH route action is required.');
        }

        return $this->registerRoute(HttpMethod::PATCH, $uri, $action);
    }

    public function delete(string | array $uri, Closure | array | null $action = null): Route | array
    {
        if (is_array($uri)) {
            return $this->registerMany(HttpMethod::DELETE, $uri);
        }

        if ($action === null) {
            throw new \InvalidArgumentException('DELETE route action is required.');
        }

        return $this->registerRoute(HttpMethod::DELETE, $uri, $action);
    }

    public function group(
        string $prefix,
        Closure | array $callbackOrRoutes,
        array | string $middlewares = []
    ): ?array {
        $this->groupStack[] = [
            'prefix'      => $this->normalizeUri($prefix),
            'middlewares' => $this->normalizeMiddlewares($middlewares),
        ];

        try {
            if ($callbackOrRoutes instanceof Closure) {
                $reflection = new ReflectionFunction($callbackOrRoutes);

                if ($reflection->getNumberOfParameters() > 0) {
                    $callbackOrRoutes($this);
                } else {
                    $callbackOrRoutes();
                }

                return null;
            }

            return (new GroupRouteRegistrar($this))->routes($callbackOrRoutes);
        } finally {
            array_pop($this->groupStack);
        }
    }

    protected function applyCurrentGroupPrefix(string $uri): string
    {
        $prefix = '';

        foreach ($this->groupStack as $group) {
            $prefix = $this->joinUris($prefix, $group['prefix']);
        }

        return $this->joinUris($prefix, $uri);
    }

    protected function currentGroupMiddlewares(): array
    {
        $middlewares = [];

        foreach ($this->groupStack as $group) {
            $middlewares = array_merge(
                $middlewares,
                $group['middlewares']
            );
        }

        return $middlewares;
    }

    protected function joinUris(string $prefix, string $uri): string
    {
        $prefix = $this->normalizeUri($prefix);
        $uri    = $this->normalizeUri($uri);

        if ($prefix === '') {
            return $uri;
        }

        if ($uri === '') {
            return $prefix;
        }

        return rtrim($prefix, '/') . '/' . ltrim($uri, '/');
    }

    protected function normalizeUri(string $uri): string
    {
        $uri = trim($uri);

        if ($uri === '' || $uri === '/') {
            return '';
        }

        return '/' . trim($uri, '/');
    }

    protected function normalizeMiddlewares(array | string $middlewares): array
    {
        if (is_string($middlewares)) {
            return [$middlewares];
        }

        return $middlewares;
    }

    protected function isAssociativeArray(array $array): bool
    {
        if ($array === []) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }
    public function controller(
        string $controller,
        string | Closure | array $prefixOrRoutesOrCallback,
        Closure | array | string | null $routesOrCallbackOrMiddlewares = null,
        array | string $middlewares = []
    ): void {
        if (! class_exists($controller)) {
            throw new \InvalidArgumentException(
                "Controller class [$controller] does not exist."
            );
        }

        $prefix   = '';
        $callback = null;
        $routes   = null;

        /*
     * Forma 1:
     *
     * Route::controller(Home::class, function () {
     *     get('', 'create');
     * });
     */
        if ($prefixOrRoutesOrCallback instanceof Closure) {
            $callback = $prefixOrRoutesOrCallback;

            $middlewares = is_array($routesOrCallbackOrMiddlewares) || is_string($routesOrCallbackOrMiddlewares)
                ? $routesOrCallbackOrMiddlewares
                : [];
        }

        /*
     * Forma 3 sin prefijo:
     *
     * Route::controller(Home::class, [
     *     'get' => [
     *         '' => 'create',
     *     ],
     * ]);
     */
        elseif (is_array($prefixOrRoutesOrCallback)) {
            $routes = $prefixOrRoutesOrCallback;

            $middlewares = is_array($routesOrCallbackOrMiddlewares) || is_string($routesOrCallbackOrMiddlewares)
                ? $routesOrCallbackOrMiddlewares
                : [];
        }

        /*
     * Formas con prefijo:
     *
     * Route::controller(Home::class, '/dashboard', function () {});
     *
     * Route::controller(Home::class, '/dashboard', [
     *     'get' => [
     *         '' => 'create',
     *     ],
     * ]);
     */
        else {
            $prefix = $prefixOrRoutesOrCallback;

            if ($routesOrCallbackOrMiddlewares instanceof Closure) {
                $callback = $routesOrCallbackOrMiddlewares;
            } elseif (is_array($routesOrCallbackOrMiddlewares)) {
                $routes = $routesOrCallbackOrMiddlewares;
            } else {
                throw new \InvalidArgumentException(
                    'Controller group callback or routes array is required.'
                );
            }
        }

        $controllerGroup = new ControllerRouteGroup($this, $controller);

        $this->group($prefix, function () use ($callback, $routes, $controllerGroup) {
            ControllerRouteScope::push($controllerGroup);

            try {
                if ($callback instanceof Closure) {
                    $reflection = new ReflectionFunction($callback);

                    if ($reflection->getNumberOfParameters() > 0) {
                        $callback($controllerGroup);
                        return;
                    }

                    $callback();
                    return;
                }

                if (is_array($routes)) {
                    $controllerGroup->routes($routes);
                    return;
                }
            } finally {
                ControllerRouteScope::pop();
            }
        }, $middlewares);
    }
}
