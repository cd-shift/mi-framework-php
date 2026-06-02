<?php

declare(strict_types=1);

namespace Framework;

use Container\Container;
use Http\HttpNotFoundException;
use Http\Request;
use Http\Response;
use Routing\Router;
use Server\PhpNativeServer;
use Server\Server;

class App
{
    public Router $router;
    public Request $request;
    public Server $server;

    public static function bootstrap() // Inicializacion de la Aplicacion
    {
        $app = Container::singleton(App::class); //(self::class)
        $app->router = new Router();
        $app->server = new PhpNativeServer();
        $app->request = $app->server->getRequest();
        return $app;
    }

    public function run()
    {
        $server = new PhpNativeServer();
        try {
            $response = $this->router->resolve($this->request);
            $server->sendResponse($response);
        } catch (HttpNotFoundException $e) {
            /**
             * Fallback 404 response when no route matches.
             *
             * @var Response $response
             */
            $response = Response::text('Not Found Friend :/')->setStatus(404);
            $this->server->sendResponse($response);
        }
    }
}
