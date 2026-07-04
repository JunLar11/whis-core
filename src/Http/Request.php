<?php
namespace Whis\Http;

use Whis\Routing\Route;
use Whis\Storage\File;
use Whis\Validation\Validator;

class Request
{
    protected string $uri;
    protected Route $route;
    protected HttpMethod $method;

    protected array $data    = [];
    protected array $query   = [];
    protected array $headers = [];
    protected array $files   = [];

    public function uri(): string
    {
        return $this->uri;
    }

    public function setUri(string $uri): self
    {
        $this->uri = $uri;
        return $this;
    }

    public function route(): Route
    {
        return $this->route;
    }

    public function setRoute(Route $route): self
    {
        $this->route = $route;
        return $this;
    }

    public function method(): HttpMethod
    {
        return $this->method;
    }

    public function setMethod(HttpMethod $method): self
    {
        $this->method = $method;
        return $this;
    }

    public function data(?string $key = null): array | string | null
    {
        if ($key === null) {
            return $this->data;
        }

        return $this->data[$key] ?? null;
    }

    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    public function query(?string $key = null): array | string | null
    {
        if ($key === null) {
            return $this->query;
        }

        return $this->query[$key] ?? null;
    }

    public function setQuery(array $query): self
    {
        $this->query = $query;
        return $this;
    }

    public function headers(?string $key = null): array | string | null
    {
        if ($key === null) {
            return $this->headers;
        }

        return $this->headers[strtolower($key)] ?? null;
    }

    public function setHeaders(array $headers): self
    {
        foreach ($headers as $header => $value) {
            $this->headers[strtolower($header)] = $value;
        }

        return $this;
    }

    public function expectsJson(): bool
    {
        $accept        = strtolower((string) $this->headers('accept'));
        $contentType   = strtolower((string) $this->headers('content-type'));
        $requestedWith = strtolower((string) $this->headers('x-requested-with'));

        return str_contains($accept, 'application/json')
        || str_contains($accept, '+json')
        || str_contains($contentType, 'application/json')
        || $requestedWith === 'xmlhttprequest'
        || $this->headers('x-csrf-token') !== null;
    }

    public function routeParameters(?string $key = null): array | string | null
    {
        $parameters = $this->route->parseParameters($this->uri);

        if ($key === null) {
            return $parameters;
        }

        return $parameters[$key] ?? null;
    }

    public function files(?string $key = null): mixed
    {
        if ($key === null) {
            return $this->files;
        }

        return $this->files[$key] ?? null;
    }

    public function setFiles(array $files): self
    {
        $this->files = $files;
        return $this;
    }

    public function validate(
        array $validationRules,
        bool $backWithErrors = true,
        array $messages = []
    ): array {
        $data = $this->data;

        foreach ($this->files as $key => $file) {
            $data[$key] = $file;
        }

        $validator = new Validator($data);

        return $validator->validate($validationRules, $messages, $backWithErrors);
    }

    public function file(
        string $name,
        array | string | null $validate = null,
        bool $backWithErrors = true,
        array $messages = []
    ): File | array | null {
        $file = $this->files[$name] ?? null;

        if ($validate === null) {
            return $file;
        }

        $validator = new Validator([
            $name => $file,
        ]);

        $validator->validate([
            $name => $validate,
        ], $messages, $backWithErrors);

        return $file;
    }
}
