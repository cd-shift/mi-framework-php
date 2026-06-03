<?php

declare(strict_types=1);

namespace Routing;

use Closure;
use Http\HttpMethod;
use Http\HttpNotFoundException;
use Http\Request;
use Http\Response;

/**
 * Registers HTTP routes and resolves requests to matching routes.
 */
class Router
{
    /**
     * Route collection indexed by HTTP method value.
     *
     * @var array<string, array<int, Route>>
     */
    protected array $routes = [];

    /**
     * Initializes empty route buckets for each supported HTTP method.
     *
     * @return void
     */
    public function __construct()
    {
        foreach (HttpMethod::cases() as $method) {
            $this->routes[$method->value] = [];
        }
    }

    /**
     * Resolves a request to the first matching route.
     *
     * @param Request $request Incoming HTTP request.
     * @return Route
     *
     * @throws HttpNotFoundException When no route matches the request URI and method.
     */
    public function resolveRoute(Request $request): Route
    {
        foreach ($this->routes[$request->method()->value] as $route) {
            if ($route->matches($request->uri())) {
                return $route;
            }
        }

        throw new HttpNotFoundException();
    }

    /**
     * Resolves the request, assigns its route, and executes the route pipeline.
     *
     * @param Request $request Current HTTP request.
     * @return Response
     *
     * @throws HttpNotFoundException When no route matches the request URI and method.
     */
    public function resolve(Request $request): Response
    {
        $route = $this->resolveRoute($request);
        $request->setRoute($route);
        $action = $route->action();
        $response = $action($request);

        if ($route->hasMiddlewares()) {
            return $this->runMiddlewares($request, $route->middlewares(), $action);
        }

        return $response;
    }

    /**
     * Executes the middleware stack recursively until the target action runs.
     *
     * @param Request $request Current HTTP request.
     * @param array<int, object> $middlewares Middleware instances to execute.
     * @param Closure $target Final route action.
     * @return Response
     */
    protected function runMiddlewares(Request $request, array $middlewares, Closure $target): Response
    {
        if (count($middlewares) === 0) {
            return $target($request);
        }

        return $middlewares[0]->handle(
            $request,
            fn () => $this->runMiddlewares(
                $request,
                array_slice($middlewares, 1),
                $target
            )
        );
    }

    /**
     * Registers a GET route.
     *
     * @param string $uri Route URI pattern.
     * @param Closure $action Route action callback.
     * @return Route
     */
    public function get(string $uri, Closure $action): Route
    {
        return $this->registerRoute(HttpMethod::GET, $uri, $action);
    }

    /**
     * Registers a POST route.
     *
     * @param string $uri Route URI pattern.
     * @param Closure $action Route action callback.
     * @return Route
     */
    public function post(string $uri, Closure $action): Route
    {
        return $this->registerRoute(HttpMethod::POST, $uri, $action);
    }

    /**
     * Registers a PUT route.
     *
     * @param string $uri Route URI pattern.
     * @param Closure $action Route action callback.
     * @return Route
     */
    public function put(string $uri, Closure $action): Route
    {
        return $this->registerRoute(HttpMethod::PUT, $uri, $action);
    }

    /**
     * Registers a PATCH route.
     *
     * @param string $uri Route URI pattern.
     * @param Closure $action Route action callback.
     * @return Route
     */
    public function patch(string $uri, Closure $action): Route
    {
        return $this->registerRoute(HttpMethod::PATCH, $uri, $action);
    }

    /**
     * Registers a DELETE route.
     *
     * @param string $uri Route URI pattern.
     * @param Closure $action Route action callback.
     * @return Route
     */
    public function delete(string $uri, Closure $action): Route
    {
        return $this->registerRoute(HttpMethod::DELETE, $uri, $action);
    }

    /**
     * Registers a route for a specific HTTP method.
     *
     * @param HttpMethod $method HTTP method bucket.
     * @param string $uri Route URI pattern.
     * @param Closure $action Route action callback.
     * @return Route
     */
    protected function registerRoute(HttpMethod $method, string $uri, Closure $action): Route
    {
        $route = new Route($uri, $action);
        $this->routes[$method->value][] = $route;

        return $route;
    }
}
