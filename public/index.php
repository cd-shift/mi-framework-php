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
    return json($request->routeParameters());
});

/**
 * Handles POST /test.
 *
 * @param Request $request Incoming request.
 * @return Response
 */
$app->router->post('/test', function (Request $request) {
    return json($request->query());
});

/**
 * Handles GET /redirect.
 *
 * @param Request $request Incoming request.
 * @return Response
 */
$app->router->get('/redirect', function (Request $request) {
    return redirect('/test');
});

/**
 * Example middleware that guards routes using the Authorization header.
 */
class AuthMiddleware implements Middleware
{
    /**
     * Validates the request and enriches the response when authorized.
     *
     * @param Request $request Current HTTP request.
     * @param \Closure $next Next middleware or final route action.
     * @return Response
     */
    public function handle(Request $request, \Closure $next): Response
    {
        if ($request->headers('Authorization') != 'test') {
            return json(['message' => 'Not authenticated'])->setStatus(401);
        }
        $response = $next($request);
        $response->setHeader('X-AuthMiddleware-Test', 'middleware ejecutado');
        return $response;
    }
}

/**
 * Handles GET /middlewares.
 *
 * @param Request $request Incoming request.
 * @return Response
 */
Route::get('/middlewares', fn (Request $request) => json(['Message' => 'Ok']))
        ->setMiddlewares([AuthMiddleware::class]);

/**
 * Handles GET /html.
 *
 * @param Request $request Incoming request.
 * @return Response
 */
Route::get('/html', fn (Request $request) => view('home', ['user' => 'Manolo']));

Route::post('/validate', fn (Request $request) => json($request->validate([
    'test' => 'required',
    'num' => 'number',
    'email' => ['required_with:num', 'email'],
], [
    'email' => ['email' => 'Dame el CAMPO']
])));

Route::get('/session', function (Request $request) {
    // session()->flash('test', 'test');
    return json($_SESSION);
});

Route::get('/form', fn (Request $request) => view('form'));

Route::post('/form', function (Request $request) {
    return json($request->validate(['email' => 'email', 'name' => 'required']));
});

$app->run();
