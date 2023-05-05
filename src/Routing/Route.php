<?php

namespace Whis\Routing;

use Whis\Http\Middleware;
use Closure;

class Route
{
    /**
     * Route URI
     *
     * @var string
     */
    protected string $uri;

    /**
     * Route action
     *
     * @var Closure|array
     */
    protected Closure|array $action;

    /**
     * Route regex
     *
     * @var string
     */
    protected string $regex;

    /**
     * Route parameters
     *
     * @var array<string,string>
     */
    protected array $parameters;

    /**
     * HTTP middlewares
     * @var array<Middleware>
     */
    protected array $middlewares = [];


    public function __construct(string $uri, Closure|array $action)
    {
        $this->uri = $uri;
        $this->action = $action;
        

        // Convert the route to a regular expression: escape forward slashes
        $this->regex = preg_replace('/\//', '\\/', $uri);;

        // Convert variables e.g. {controller}
        $this->regex = preg_replace('/\{([a-z]+)\}/', '(?P<\1>[a-z-]+)', $this->regex);

        // Convert variables with custom regular expressions e.g. {id:\d+}
        $this->regex = preg_replace('/\{([a-z]+):([^\}]+)\}/', '(?P<\1>\2)', $this->regex);

        //Get the parameters from the route
        preg_match_all('/\{([a-zA-Z]+)(:[^\}]+)?\}/', $uri, $parameters);

        $this->parameters = $parameters[1];
    }

    /**
     * Get route URI
     *
     * @return string
     */
    public function uri()
    {
        return $this->uri;
    }

    /**
     * Get route action
     *
     * @return Closure
     */
    public function action():Closure|array
    {
        return $this->action;
    }

    /**
     * Get all HTTP middlewares for this route
     * @return array<Middleware>
    */
    public function middlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * Check if this route has HTTP middlewares
     * @return bool
     */
    public function hasMiddlewares(): bool
    {
        return count($this->middlewares) > 0;
    }

    /**
     * Add middlewares to the route
     * @param Middleware $middleware
     * @return Route
     */
    public function setMiddlewares(array $middlewares): self
    {
        $this->middlewares = array_map(fn ($middleware) =>new $middleware(), $middlewares);
        return $this;
    }

    /**
     * Get route matches regex
     *
     * @return bool
     */
    public function matches(string $uri): bool
    {
        //var_dump($this->regex);
        $regex="/^".$this->regex."\/?$/i";
        return preg_match($regex, $uri);
    }

    /**
     * Get route parameters
     *
     * @return bool
     */
    public function hasParameters(): bool
    {
        return count($this->parameters) > 0;
    }

    /**
     * Parse route parameters
     *
     * @return array<string,string>
     */
    public function parseParameters(string $uri): array
    {
        $regex="/^".$this->regex."\/?$/i";
        preg_match($regex, $uri, $arguments);
        $params = [];
        foreach ($arguments as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $value;
            }
        }
        return $params;
    }

    public static function get(string $uri, Closure|array $action): Route
    {
        return app()->router->get($uri, $action);
    }

    public static function post(string $uri, Closure|array $action): Route
    {
        return app()->router->post($uri, $action);
    }

    public static function put(string $uri, Closure|array $action): Route
    {
        return app()->router->put($uri, $action);
    }

    public static function delete(string $uri, Closure|array $action): Route
    {
        return app()->router->delete($uri, $action);
    }

    public static function patch(string $uri, Closure|array $action): Route
    {
        return app()->router->patch($uri, $action);
    }

    public static function load(string $routesDirectory){
        foreach (glob($routesDirectory. '/*.php') as $routeFile) {
            require_once $routeFile;
        }
    }

}
