<?php
namespace Whis\Routing;

use Closure;
use Whis\Http\Middleware;

class Route
{
    protected string $uri;

    protected Closure|array $action;

    protected string $regex;

    /**
     * @var array<int,string>
     */
    protected array $parameters = [];

    /**
     * @var array<int,Middleware>
     */
    protected array $middlewares = [];

    public function __construct(string $uri, Closure | array $action)
    {
        $this->uri    = $this->normalizeUri($uri);
        $this->action = $action;

        $this->compileRegex();
        $this->parseParameterNames();
    }

    protected function normalizeUri(string $uri): string
    {
        $uri = trim($uri);

        if ($uri === '' || $uri === '/') {
            return '';
        }

        return '/' . trim($uri, '/');
    }

    protected function compileRegex(): void
    {
        $regex = preg_replace('/\//', '\\/', $this->uri);

        /*
         * Parámetros con regex personalizada:
         *
         * /users/{id:\d+}
         * /uploads/{filename:.*\.(?:png|jpg|jpeg)}
         */
        $regex = preg_replace(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*):([^\}]+)\}/',
            '(?P<\1>\2)',
            $regex
        );

        /*
         * Parámetros normales:
         *
         * /users/{id}
         */
        $regex = preg_replace(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            '(?P<\1>[a-zA-Z0-9_-]+)',
            $regex
        );

        $this->regex = $regex;
    }

    protected function parseParameterNames(): void
    {
        preg_match_all(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)(:[^\}]+)?\}/',
            $this->uri,
            $parameters
        );

        $this->parameters = $parameters[1] ?? [];
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function action(): Closure | array
    {
        return $this->action;
    }

    public function middlewares(): array
    {
        return $this->middlewares;
    }

    public function hasMiddlewares(): bool
    {
        return count($this->middlewares) > 0;
    }

    public function setMiddlewares(array | string $middlewares): self
    {
        $this->middlewares = [];

        return $this->addMiddlewares($middlewares);
    }

    public function addMiddlewares(array | string $middlewares): self
    {
        if (is_string($middlewares)) {
            $middlewares = [$middlewares];
        }

        foreach ($middlewares as $middleware) {
            if ($middleware instanceof Middleware) {
                $this->middlewares[] = $middleware;
                continue;
            }

            if (is_string($middleware) && class_exists($middleware)) {
                $this->middlewares[] = new $middleware();
            }
        }

        return $this;
    }

    public function middleware(array | string $middlewares): self
    {
        return $this->addMiddlewares($middlewares);
    }

    public function matches(string $uri): bool
    {
        $uri = $this->normalizeUri($uri);

        $regex = "/^" . $this->regex . "\/?$/i";

        return preg_match($regex, $uri) === 1;
    }

    public function hasParameters(): bool
    {
        return count($this->parameters) > 0;
    }

    public function parseParameters(string $uri): array
    {
        $uri = $this->normalizeUri($uri);

        $regex = "/^" . $this->regex . "\/?$/i";

        preg_match($regex, $uri, $arguments);

        $params = [];

        foreach ($arguments as $key => $value) {
            if (is_string($key)) {
                $params[$key] = rawurldecode($value);
            }
        }

        return $params;
    }

    public static function get(string | array $uri, Closure | array | null $action = null): Route | array
    {
        return app()->router->get($uri, $action);
    }

    public static function post(string | array $uri, Closure | array | null $action = null): Route | array
    {
        return app()->router->post($uri, $action);
    }

    public static function put(string | array $uri, Closure | array | null $action = null): Route | array
    {
        return app()->router->put($uri, $action);
    }

    public static function patch(string | array $uri, Closure | array | null $action = null): Route | array
    {
        return app()->router->patch($uri, $action);
    }

    public static function delete(string | array $uri, Closure | array | null $action = null): Route | array
    {
        return app()->router->delete($uri, $action);
    }

    public static function group(
        string $prefix,
        Closure | array $callbackOrRoutes,
        array | string $middlewares = []
    ): ?array {
        return app()->router->group($prefix, $callbackOrRoutes, $middlewares);
    }

    public static function file(
        string $prefix,
        Closure | array $action,
        array | string $extensions = ['png', 'jpg', 'jpeg'],
        string $parameter = 'filename'
    ): Route {
        $prefix = '/' . trim($prefix, '/');

        $extensionPattern = self::extensionPattern($extensions);

        return self::get(
            $prefix . '/{' . $parameter . ':.*\.(?:' . $extensionPattern . ')}',
            $action
        );
    }

    public static function download(
        string $prefix,
        Closure | array $action,
        array | string $extensions = ['png', 'jpg', 'jpeg'],
        string $parameter = 'filename'
    ): Route {
        $prefix = '/' . trim($prefix, '/');

        $extensionPattern = self::extensionPattern($extensions);

        return self::get(
            $prefix . '/{' . $parameter . ':.*\.(?:' . $extensionPattern . ')}',
            $action
        );
    }

    private static function extensionPattern(array | string $extensions): string
    {
        if (is_string($extensions)) {
            $extensions = explode('|', str_replace(',', '|', $extensions));
        }

        $extensions = array_values(array_filter(array_map(
            fn($extension) => strtolower(trim((string) $extension, ". \t\n\r\0\x0B")),
            $extensions
        )));

        if (empty($extensions)) {
            $extensions = ['png', 'jpg', 'jpeg'];
        }

        $extensions = array_map(
            fn($extension) => preg_quote($extension, '/'),
            $extensions
        );

        return implode('|', $extensions);
    }

    public static function load(string $routesDirectory): void
    {
        foreach (glob($routesDirectory . '/*.php') as $routeFile) {
            require_once $routeFile;
        }
    }
    public static function controller(
        string $controller,
        string | Closure | array $prefixOrRoutesOrCallback,
        Closure | array | string | null $routesOrCallbackOrMiddlewares = null,
        array | string $middlewares = []
    ): void {
        app()->router->controller(
            $controller,
            $prefixOrRoutesOrCallback,
            $routesOrCallbackOrMiddlewares,
            $middlewares
        );
    }
}
