<?php

declare(strict_types=1);

namespace Framework;

use Http\HttpMethod;
use Http\HttpNotFoundException;
use Http\Request;
use Http\Response;
use Routing\Router;
use Server\PhpNativeServer;
use Server\Server;
use Session\PhpNativeSessionStorage;
use Session\Session;
use Throwable;
use Validation\Exceptions\ValidationException;
use Validation\Rule;
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

    public Session $session;

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
        $app->session = new Session(new PhpNativeSessionStorage());
        Rule::loadDefaultRules();

        return $app;
    }

    public function prepareNextRequest()
    {
        if ($this->request->method() === HttpMethod::GET) {
            $this->session->set('_previous', $this->request->uri());
        }
    }

    public function terminate(Response $response)
    {
        $this->prepareNextRequest();
        $this->server->sendResponse($response);
    }

    /**
     * Resolves the current request and sends the resulting response.
     *
     * @return void
     */
    public function run(): void
    {
        try {
            $this->terminate($this->router->resolve($this->request));


        } catch (HttpNotFoundException $e) {
            $this->abort(Response::text('Not Found Friend :/')->setStatus(404));

        } catch (ValidationException $e) {
            $this->abort(redirectBack()->withErrors($e->errors(), 422));

        } catch (Throwable $e) {
            $response = json([
                'error' => $e::class,
                'message' => $e->getMessage(),
                'trace' => $e->getTrace(),
            ]);
            $this->abort($response->setStatus(500));
        }
    }

    public function abort(Response $response)
    {
        $this->terminate($response);
    }
}
