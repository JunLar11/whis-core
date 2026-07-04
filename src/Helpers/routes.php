<?php

use Whis\Routing\ControllerRouteScope;
use Whis\Routing\Route;

function GET(
    string | array $uri,
    string | \Closure  | array | null $action = null,
    array | string $middlewares = []
): Route | array {
    $controller = ControllerRouteScope::current();

    if ($controller !== null) {
        if ($action instanceof \Closure) {
            throw new \InvalidArgumentException(
                'Controller routes do not accept Closure actions. Use Route::group() or Route::get() for Closure routes.'
            );
        }

        return $controller->get($uri, $action, $middlewares);
    }

    if (is_string($action)) {
        throw new \InvalidArgumentException(
            'GET route action cannot be a string outside Route::controller(). Use [Controller::class, "method"].'
        );
    }

    $route = Route::get($uri, $action);

    return __whis_apply_route_middlewares($route, $middlewares);
}

function POST(
    string | array $uri,
    string | \Closure  | array | null $action = null,
    array | string $middlewares = []
): Route | array {
    $controller = ControllerRouteScope::current();

    if ($controller !== null) {
        if ($action instanceof \Closure) {
            throw new \InvalidArgumentException(
                'Controller routes do not accept Closure actions. Use Route::group() or Route::post() for Closure routes.'
            );
        }

        return $controller->post($uri, $action, $middlewares);
    }

    if (is_string($action)) {
        throw new \InvalidArgumentException(
            'POST route action cannot be a string outside Route::controller(). Use [Controller::class, "method"].'
        );
    }

    $route = Route::post($uri, $action);

    return __whis_apply_route_middlewares($route, $middlewares);
}

function PUT(
    string | array $uri,
    string | \Closure  | array | null $action = null,
    array | string $middlewares = []
): Route | array {
    $controller = ControllerRouteScope::current();

    if ($controller !== null) {
        if ($action instanceof \Closure) {
            throw new \InvalidArgumentException(
                'Controller routes do not accept Closure actions. Use Route::group() or Route::put() for Closure routes.'
            );
        }

        return $controller->put($uri, $action, $middlewares);
    }

    if (is_string($action)) {
        throw new \InvalidArgumentException(
            'PUT route action cannot be a string outside Route::controller(). Use [Controller::class, "method"].'
        );
    }

    $route = Route::put($uri, $action);

    return __whis_apply_route_middlewares($route, $middlewares);
}

function PATCH(
    string | array $uri,
    string | \Closure  | array | null $action = null,
    array | string $middlewares = []
): Route | array {
    $controller = ControllerRouteScope::current();

    if ($controller !== null) {
        if ($action instanceof \Closure) {
            throw new \InvalidArgumentException(
                'Controller routes do not accept Closure actions. Use Route::group() or Route::patch() for Closure routes.'
            );
        }

        return $controller->patch($uri, $action, $middlewares);
    }

    if (is_string($action)) {
        throw new \InvalidArgumentException(
            'PATCH route action cannot be a string outside Route::controller(). Use [Controller::class, "method"].'
        );
    }

    $route = Route::patch($uri, $action);

    return __whis_apply_route_middlewares($route, $middlewares);
}

function DELETE(
    string | array $uri,
    string | \Closure  | array | null $action = null,
    array | string $middlewares = []
): Route | array {
    $controller = ControllerRouteScope::current();

    if ($controller !== null) {
        if ($action instanceof \Closure) {
            throw new \InvalidArgumentException(
                'Controller routes do not accept Closure actions. Use Route::group() or Route::delete() for Closure routes.'
            );
        }

        return $controller->delete($uri, $action, $middlewares);
    }

    if (is_string($action)) {
        throw new \InvalidArgumentException(
            'DELETE route action cannot be a string outside Route::controller(). Use [Controller::class, "method"].'
        );
    }

    $route = Route::delete($uri, $action);

    return __whis_apply_route_middlewares($route, $middlewares);
}

function GROUP(
    string $prefix,
    \Closure|array $callbackOrRoutes,
    array|string $middlewares = []
): ?array {
    return Route::group($prefix, $callbackOrRoutes, $middlewares);
}

function CONTROLLER(
    string $controller,
    string | \Closure  | array $prefixOrRoutesOrCallback,
    \Closure  | array | string | null $routesOrCallbackOrMiddlewares = null,
    array | string $middlewares = []
): void {
    Route::controller(
        $controller,
        $prefixOrRoutesOrCallback,
        $routesOrCallbackOrMiddlewares,
        $middlewares
    );
}

function __whis_apply_route_middlewares(
    Route | array $routeOrRoutes,
    array | string $middlewares
): Route | array {
    if ($middlewares === [] || $middlewares === '') {
        return $routeOrRoutes;
    }

    if ($routeOrRoutes instanceof Route) {
        return $routeOrRoutes->middleware($middlewares);
    }

    foreach ($routeOrRoutes as $route) {
        if ($route instanceof Route) {
            $route->middleware($middlewares);
        }
    }

    return $routeOrRoutes;
}
