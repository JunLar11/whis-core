<?php

use Whis\Auth\Auth;

if (! function_exists('api_context')) {
    function api_context(): ?\Whis\Auth\Api\ApiAuthContext
    {
        return Auth::apiContext();
    }
}

if (! function_exists('api_token')) {
    function api_token(): mixed
    {
        return Auth::apiToken();
    }
}

if (! function_exists('jwt_payload')) {
    function jwt_payload(): ?array
    {
        return Auth::jwtPayload();
    }
}

if (! function_exists('api_token_can')) {
    function api_token_can(string $ability): bool
    {
        return Auth::tokenCan($ability);
    }
}

if (! function_exists('api_token_cant')) {
    function api_token_cant(string $ability): bool
    {
        return Auth::tokenCant($ability);
    }
}
