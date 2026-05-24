<?php

declare(strict_types=1);

/**
 * Application front controller.
 *
 * Registers routes, resolves incoming requests, and sends responses
 * through the native PHP server adapter.
 */
require_once '../vendor/autoload.php';

use Http\HttpNotFoundException;
use Http\Request;
use Http\Response;
use Routing\Router;
use Server\PhpNativeServer;

/**
 * Main router instance used to register route handlers.
 *
 * @var Router $router
 */
$router = new Router();

/**
 * Handles GET /test.
 *
 * @param Request $request Incoming request.
 * @return Response
 */
$router->get('/test', function (Request $request) {
    return Response::text('GET OK');
});

/**
 * Handles POST /test.
 *
 * @return Response
 */
$router->post('/test', function () {
    return Response::text('POST OK');
});

/**
 * Handles GET /redirect.
 *
 * @param Request $request Incoming request.
 * @return Response
 */
$router->get('/redirect', function (Request $request) {
    return Response::redirect('/test');
});

/**
 * Handles PUT /test.
 *
 * @return string
 */
$router->put('/test', function () {
    return "PUT OK\n";
});

/**
 * Handles PATCH /test.
 *
 * @return string
 */
$router->patch('/test', function () {
    return "PATCH OK\n";
});

/**
 * Handles DELETE /test.
 *
 * @return string
 */
$router->delete('/test', function () {
    return "DELETE OK\n";
});

/**
 * Native server adapter instance.
 *
 * @var PhpNativeServer $server
 */
$server = new PhpNativeServer();
try {
    /**
     * Normalized request value object.
     *
     * @var Request $request
     */
    $request = new Request($server);

    /**
     * Resolved route for the incoming request.
     *
     * @var \Routing\Route $route
     */
    $route = $router->resolve($request);

    /**
     * Route handler callback.
     *
     * @var \Closure $action
     */
    $action = $route->action();

    /**
     * Handler result sent back to the server adapter.
     *
     * @var mixed $response
     */
    $response = $action($request);
    $server->sendResponse($response);
} catch (HttpNotFoundException $e) {
    /**
     * Fallback 404 response when no route matches.
     *
     * @var Response $response
     */
    $response = Response::text('Not Found Friend :/')->setStatus(404);
    $server->sendResponse($response);
}
