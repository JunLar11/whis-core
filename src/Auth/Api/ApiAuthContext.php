<?php

namespace Whis\Auth\Api;

use Whis\Auth\Authenticatable;

class ApiAuthContext
{
    public function __construct(
        public bool $authenticated = false,
        public ?Authenticatable $user = null,
        public mixed $token = null,
        public ?array $jwtPayload = null,
        public ?string $driver = null,
        public ?string $error = null,
        public int $status = 401
    ) {
    }

    public static function deny(
        string $error = 'No autorizado.',
        int $status = 401,
        ?string $driver = null
    ): self {
        return new self(
            authenticated: false,
            error: $error,
            status: $status,
            driver: $driver
        );
    }

    public static function token(Authenticatable $user, mixed $token): self
    {
        return new self(
            authenticated: true,
            user: $user,
            token: $token,
            driver: 'token'
        );
    }

    public static function jwt(Authenticatable $user, array $payload): self
    {
        return new self(
            authenticated: true,
            user: $user,
            jwtPayload: $payload,
            driver: 'jwt'
        );
    }

    public function guest(): bool
    {
        return ! $this->authenticated || $this->user === null;
    }

    public function check(): bool
    {
        return ! $this->guest();
    }

    public function can(string $ability): bool
    {
        if ($ability === '' || $ability === '*') {
            return true;
        }

        if ($this->token && method_exists($this->token, 'can')) {
            return $this->token->can($ability);
        }

        $abilities = $this->jwtPayload['abilities'] ?? $this->jwtPayload['scopes'] ?? [];

        if (is_string($abilities)) {
            $abilities = array_filter(array_map('trim', explode(',', $abilities)));
        }

        if (! is_array($abilities)) {
            return false;
        }

        return in_array('*', $abilities, true) || in_array($ability, $abilities, true);
    }

    public function cant(string $ability): bool
    {
        return ! $this->can($ability);
    }
}
