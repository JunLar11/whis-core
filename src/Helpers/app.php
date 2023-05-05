<?php

use Whis\App;
use Whis\Config\Config;
use Whis\Container\Container;

function app($class = App::class)
{
    return Container::resolve($class);
}

function singleton(string $class, string|callable|null $build=null)
{
    return Container::singleton($class, $build);
}

function env(string $variable, $default = null) {
    return $_ENV[$variable] ?? $default;
}

function config(string $configuration, $default = null) {
    return Config::get($configuration, $default);
}

function resourcesDirectory(): string {
    return App::$root . "/resources";
}