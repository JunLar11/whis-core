<?php

namespace Whis\Routing;

use Whis\Container\DependencyInjection;
use Whis\Http\HttpMethod;
use Whis\Exceptions\HttpNotFoundException;
use Whis\Http\Request;
use Whis\Http\Response;
use Closure;

class Router
{
    /**
     * Routes
     *
     * @var array<HttpMethod,Route>
     */
    protected array $routes = [];

    public function __construct()
    {
        foreach (HttpMethod::cases() as $method) {
            $this->routes[$method->value] = [];
        }
    }
    
    /**
     * resolveRoute
     *
     * @param  Request $request
     * @return Route
     */
    public function resolveRoute(Request $request): Route
    {
        // echo "<pre>";
        // var_dump($request->method()->value);
        // var_dump($this->routes);
        
        // var_dump($this->routes["GET"][0]->matches($this->removeQueryStringVariables(strtolower($request->uri()))));
        // exit;

        // echo "</pre>";
        

        foreach ($this->routes[$request->method()->value] as $route) {
            if ($route->matches($this->removeQueryStringVariables(strtolower($request->uri())))) {
                //var_dump($route);
                return $route;
            }
        }

        throw new HttpNotFoundException();
    }

    protected function removeQueryStringVariables($url)
    {
        if ($url != '') {
            $parts = explode('&', $url, 2);

            if (strpos($parts[0], '=') === false) {
                $url = $parts[0];
            } else {
                $url = '';
            }
        }

        return $url;
    }

    public function resolve(Request $request): Response
    {
        $route=$this->resolveRoute($request);
        $request->setRoute($route);
        $action=$route->action();
        $middlewares = $route->middlewares();
        if(is_array($action)){
            $controller= new $action[0]();
            $action[0]= $controller;
            $middlewares = array_merge($middlewares, $controller->middlewares());
        }
        

        $parameters=DependencyInjection::resolveParameters($action,$request->routeParameters());
        
        return $this->runMiddlewares($request, $middlewares, fn()=>call_user_func($action,...$parameters));
    }

    /**
     * Runs middlewares
     *
     * @param Request $request
     * @param array<Middleware> $middlewares
     * @param Closure $target
     * @return Response
     */

    protected function runMiddlewares(Request $request, array $middlewares, $target): Response
    {
        if (count($middlewares)==0) {
            return $target();
        }

        return($middlewares[0])->handle(
            $request,
            function (Request $request) use ($middlewares, $target) {
                return $this->runMiddlewares($request, array_slice($middlewares, 1), $target);
            }
        );
    }


    /**
     * Add a route to the routes collection
     *
     * @param HttpMethod $method
     * @param string $uri
     * @param Closure|array $action
     *
     * @return Route
     */
    protected function registerRoute(HttpMethod $method, string $uri, Closure|array $action): Route
    {
        $route = new Route($uri, $action);
        $this->routes[$method->value][] = $route;
        return $route;
    }

    /**
     * Add a GET route to the routes collection
     *
     * @param string $uri
     * @param Closure|array $action
     * @return Route
     */
    public function get(string $uri, Closure|array $action): Route
    {
        return $this->registerRoute(HttpMethod::GET, $uri, $action);
    }

    /**
     * Add a POST route to the routes collection
     *
     * @param string $uri
     * @param Closure|array $action
     * @return Route
     */
    public function post(string $uri, Closure|array $action): Route
    {
        return $this->registerRoute(HttpMethod::POST, $uri, $action);
    }

    /**
     * Add a PUT route to the routes collection
     *
     * @param string $uri
     * @param Closure|array $action
     * @return Route
     */
    public function put(string $uri, Closure|array $action): Route
    {
        return $this->registerRoute(HttpMethod::PUT, $uri, $action);
    }

    /**
     * Add a PATCH route to the routes collection
     *
     * @param string $uri
     * @param Closure|array $action
     * @return Route
     */
    public function patch(string $uri, Closure|array $action): Route
    {
        return $this->registerRoute(HttpMethod::PATCH, $uri, $action);
    }

    /**
     * Add a DELETE route to the routes collection
     *
     * @param string $uri
     * @param Closure|array $action
     * @return Route
     */
    public function delete(string $uri, Closure|array $action): Route
    {
        return $this->registerRoute(HttpMethod::DELETE, $uri, $action);
    }

    /**
     * Get the value of routes
     *
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}
