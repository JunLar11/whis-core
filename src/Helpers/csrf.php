<?php
//Helpers/csrf.php
use Whis\Support\Csrf;

if (!function_exists('csrf_token')) {
    function csrf_token(string $key = 'default'): string
    {
        return Csrf::generate($key);
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(string $key = 'default'): string
    {
        return Csrf::field($key);
    }
}

if (!function_exists('csrf_meta')) {
    function csrf_meta(string $key = 'default'): string
    {
        return Csrf::meta($key);
    }
}