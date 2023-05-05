<?php

namespace Whis\Http;

class Controller
{
        /**
     * HTTP middlewares.
     *
     * @var \Whis\Http\Middleware[]
     */
    protected array $middlewares = [];

    /**
     * Get all HTTP middlewares for this route.
     *
     * @return \Whis\Http\Middleware[]
     */
    public function middlewares(): array {
        return $this->middlewares;
    }

    public function setMiddlewares(array $middlewares): self {
        $this->middlewares = array_map(fn ($middleware) => new $middleware(), $middlewares);
        return $this;
    }
}
