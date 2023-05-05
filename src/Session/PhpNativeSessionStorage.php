<?php

namespace Whis\Session;

use RuntimeException;

class PhpNativeSessionStorage implements SessionStorage
{
    public function start(): void
    {
        if (!session_start()) {
            throw new RuntimeException('Failed to start session.');
        }
    }

    public function save(): void
    {
        session_write_close();
    }

    public function id(): string
    {
        return session_id();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key]?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key])&&!empty($_SESSION[$key]);
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function destroy(): void
    {
        session_destroy();
    }
}
