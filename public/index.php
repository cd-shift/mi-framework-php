<?php

require_once "../vendor/autoload.php";

use mi\HttpNotFoundException;
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
    $action = $router->resolve();
    print($action());
} catch (HttpNotFoundException $e) {
    print("NOT FOUND");
    http_response_code(404);
}

?>