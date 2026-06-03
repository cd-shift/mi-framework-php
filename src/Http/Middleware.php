<?php

declare(strict_types=1);

namespace Http;

use Closure;

/**
 * Defines the contract for route middleware handlers.
 */
interface Middleware
{
    /**
     * Processes the request and optionally delegates to the next middleware.
     *
     * @param Request $request Current HTTP request.
     * @param Closure $next Next middleware or target action.
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response;
}
