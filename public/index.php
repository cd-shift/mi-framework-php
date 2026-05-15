<?php

require_once "../vendor/autoload.php";

use Http\HttpNotFoundException;
use Http\Response;
use Server\PhpNativeServer;
use Http\Request;
use Routing\Router;

$router = new Router();

$router->get('/test', function (Request $request) {
    $response = new Response();
    $response->setHeader("Content-Type", "application/json");
    $response->setContent(json_encode(["message" => "GET OK"]));
    return $response;

});

$router->post('/test', function () {
    return "POST OK\n";
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
    $response = new Response();
    $response->setStatus(404);
    // $response->setHeader("Content-Type", "text/plain");
    // $response->setContent("NOT FOUND GENTLEMAN");
    $server->sendResponse($response);
}
