<?php

declare(strict_types=1);

use Http\Request;
use Http\Response;

function json(array $data): Response
{
    return Response::json($data);
}

function redirect(string $uri): Response
{
    return Response::redirect($uri);
}

function view(string $view, array $params = [], ?string $layout = null): Response
{
    return Response::view($view, $params, $layout);
}

function request(): Request
{
    return app()->request;
}
