<?php

declare(strict_types=1);

namespace Framework;

use Http\HttpNotFoundException;
use Http\Request;
use Http\Response;
use Routing\Router;
use Server\PhpNativeServer;
use Server\Server;
use View\MiEngine;
use View\View;

/**
 * Coordinates application bootstrapping and request execution.
 */
class App
{
    /**
     * Router instance used to register and resolve routes.
     */
    public Router $router;

    /**
     * Current normalized HTTP request.
     */
    public Request $request;

    /**
     * Server adapter responsible for IO with the client.
     */
    public Server $server;

    /**
     * View renderer used by HTML response helpers.
     */
    public View $view;

    /**
     * Bootstraps the application singleton with its core services.
     *
     * @return self
     */
    public static function bootstrap(): self
    {
        $app = singleton(App::class);
        $app->router = new Router();
        $app->server = new PhpNativeServer();
        $app->request = $app->server->getRequest();
        $app->view = new MiEngine(__DIR__ . '/../views');

        return $app;
    }

    /**
     * Resolves the current request and sends the resulting response.
     *
     * @return void
     */
    public function run(): void
    {
        try {
            $response = $this->router->resolve($this->request);
            $this->server->sendResponse($response);
        } catch (HttpNotFoundException $e) {
            $response = Response::text('Not Found Friend :/')->setStatus(404);

            $this->server->sendResponse($response);
        }
    }
}
