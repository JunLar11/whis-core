<?php

namespace Whis\Routing;

class ControllerRouteScope
{
    /**
     * @var array<int,ControllerRouteGroup>
     */
    protected static array $stack = [];

    public static function push(ControllerRouteGroup $group): void
    {
        self::$stack[] = $group;
    }

    public static function pop(): void
    {
        array_pop(self::$stack);
    }

    public static function current(): ?ControllerRouteGroup
    {
        if (empty(self::$stack)) {
            return null;
        }

        return self::$stack[array_key_last(self::$stack)];
    }

    public static function has(): bool
    {
        return self::current() !== null;
    }
}