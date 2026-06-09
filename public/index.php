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
use Http\Middleware;
use Http\Request;
use Http\Response;
use Routing\Route;

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

class AuthMiddleware implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->headers('Authorization') != 'test') {
            return Response::json(['message' => 'Not authenticated'])->setStatus(401);
        }
        $response = $next($request);
        $response->setHeader('X-AuthMiddleware-Test', 'middleware ejecutado');
        return $response;
    }
}

Route::get('/middlewares', fn (Request $request) => Response::json(['Message' => 'Ok']))
        ->setMiddlewares([AuthMiddleware::class]);

Route::get('/html', fn (Request $request) => Response::view('home', ['user' => 'Manolo']));

$app->run();
