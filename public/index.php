<?php

require_once "../vendor/autoload.php";

use mi\HttpMethod;
use mi\HttpNotFoundException;
use mi\Route;
use mi\Router;

$router = new Router();

$router->get('/test', function () {
    return "GET OK\n";
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

try {
    //$route = new Route('test/{test}/user/{user}', fn () => 'test');
    // var_dump ($route->parseParameters('/test/1/user/69'));

    // Devuelve una ruta ahora, no una accion

    $route = $router->resolve($_SERVER["REQUEST_URI"], $_SERVER["REQUEST_METHOD"]);
    $action = $route->action();
    print($action());



} catch (HttpNotFoundException $e) {
    print("NOT FOUND");
    http_response_code(404);
}
