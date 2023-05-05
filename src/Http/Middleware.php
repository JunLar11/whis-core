<?php

namespace Whis\Http;

use Closure;

interface Middleware
{
/**
     * Handle the request
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response;
}
