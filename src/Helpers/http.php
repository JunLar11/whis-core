<?php

/**
 * Global helper functions
 */

use Whis\Http\Request;
use Whis\Http\Response;
use Whis\Storage\Storage;

function json(array $data): Response
{
    return Response::json($data);
}

function redirect(string $uri): Response
{
    return Response::redirect($uri);
}

function text(string $text): Response
{
    return Response::text($text);
}

function back(): Response
{
    return redirect(session()->get('_previous', '/'));
}

function view(
    string $view,
    ?string $pageName = null,
    array|string $parameters = [],
    ?string $layout = null
): Response {
    return Response::view($view, $parameters, $layout, $pageName);
}

function Request(): Request
{
    return app()->request;
}

function storage_file(
    string $filename,
    ?string $alternativeDirectory = null,
    bool $asset = false,
    bool $cache = false
): Response {
    return Storage::response(
        $filename,
        $asset,
        $alternativeDirectory,
        false,
        null,
        $cache
    );
}

function download_file(
    string $filename,
    ?string $alternativeDirectory = null,
    bool $asset = false,
    ?string $downloadName = null
): Response {
    return Storage::response(
        $filename,
        $asset,
        $alternativeDirectory,
        true,
        $downloadName,
        false
    );
}

/*function fromJson(mixed $json): array
{
    $original = $json;
    if (is_string($json)) {
        json_decode($json);
        if (!(json_last_error() == JSON_ERROR_NONE)) {
            return json_decode($json);
        }
    }
    return $original;
}*/
