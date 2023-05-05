<?php

namespace Whis\Session;

interface SessionStorage
{
    public function start(): void;

    public function save(): void;

    public function id(): string;

    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value): void;

    public function has(string $key): bool;

    public function remove(string $key): void;

    public function destroy(): void;
}
