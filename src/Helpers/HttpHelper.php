<?php

declare(strict_types=1);

use Http\Request;
use Http\Response;

/**
 * Creates a JSON response from the given payload.
 *
 * @param array<string, mixed> $data Payload data.
 * @return Response
 */
function json(array $data): Response
{
    return Response::json($data);
}

/**
 * Creates a redirect response to the given URI.
 *
 * @param string $uri Destination URI.
 * @return Response
 */
function redirect(string $uri): Response
{
    return Response::redirect($uri);
}

/**
 * Creates an HTML response by rendering a view and optional layout.
 *
 * @param string $view View name relative to the configured views directory.
 * @param array<string, mixed> $params Variables exposed to the template.
 * @param string|null $layout Layout name or null to use the default layout.
 * @return Response
 */
function view(string $view, array $params = [], ?string $layout = null): Response
{
    return Response::view($view, $params, $layout);
}

/**
 * Returns the current application request.
 *
 * @return Request
 */
function request(): Request
{
    return app()->request;
}
