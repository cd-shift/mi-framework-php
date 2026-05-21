<?php

require_once "../vendor/autoload.php";

use Http\HttpNotFoundException;
use Http\Response;
use Server\PhpNativeServer;
use Http\Request;
use Routing\Router;

$router = new Router();

$router->get('/test', function (Request $request) {
    // Patron de diseno Builder Pattern
    return Response::text("GET OK");

});

$router->post('/test', function () {
    return Response::text("POST OK");
});

$router->get('/redirect', function (Request $request){
    return Response::redirect("/test");
});

$router->put('/test', function () {
    return "PUT OK\n";
});

$router->patch('/test', function () {
    return "PATCH OK\n";
});

$router->delete('/test', function () {
    return "DELETE OK\n";
});

$server = new PhpNativeServer();
try {
    $request = new Request($server);

    $route = $router->resolve($request);
    $action = $route->action();
    $response = $action($request);
    $server->sendResponse($response);



} catch (HttpNotFoundException $e) {
    $response = Response::text("Not Found Friend :/")->setStatus(404);
    $server->sendResponse($response);
}
