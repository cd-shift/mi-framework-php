<?php

declare(strict_types=1);

/**
 * Application front controller.
 *
 * Registers routes, resolves incoming requests, and sends responses
 * through the native PHP server adapter.
 */
require_once '../vendor/autoload.php';

use Framework\App;
use Http\Request;
use Http\Response;

$app = App::bootstrap();

/**
 * Handles GET /test.
 *
 * @param Request $request Incoming request.
 * @return Response
 */
$app->router->get('/test/{param}', function (Request $request) {
    return Response::json($request->routeParameters());
});

/**
 * Handles POST /test.
 *
 * @return Response
 */
$app->router->post('/test', function (Request $request) {
    return Response::json($request->query());
});

/**
 * Handles GET /redirect.
 *
 * @param Request $request Incoming request.
 * @return Response
 */
$app->router->get('/redirect', function (Request $request) {
    return Response::redirect('/test');
});

$app->run();
