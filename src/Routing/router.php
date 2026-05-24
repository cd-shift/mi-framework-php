<?php

declare(strict_types=1);

namespace Routing;

use Http\HttpMethod;
use Http\HttpNotFoundException;
use Http\Request;

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
     * @param Request $request incoming HTTP request
     *
     * @throws HttpNotFoundException when no route matches the request URI and method
     */
    public function resolve(Request $request): Route
    {
        foreach ($this->routes[$request->method()->value] as $route) {
            if ($route->matches($request->uri())) {
                return $route;
            }
        }

        throw new HttpNotFoundException();
    }

    /**
     * Registers a GET route.
     *
     * @param string   $uri    route URI pattern
     * @param \Closure $action route action callback
     */
    public function get(string $uri, \Closure $action): void
    {
        $this->registerRoute(HttpMethod::GET, $uri, $action);
    }

    /**
     * Registers a POST route.
     *
     * @param string   $uri    route URI pattern
     * @param \Closure $action route action callback
     */
    public function post(string $uri, \Closure $action): void
    {
        $this->registerRoute(HttpMethod::POST, $uri, $action);
    }

    /**
     * Registers a PUT route.
     *
     * @param string   $uri    route URI pattern
     * @param \Closure $action route action callback
     */
    public function put(string $uri, \Closure $action): void
    {
        $this->registerRoute(HttpMethod::PUT, $uri, $action);
    }

    /**
     * Registers a PATCH route.
     *
     * @param string   $uri    route URI pattern
     * @param \Closure $action route action callback
     */
    public function patch(string $uri, \Closure $action): void
    {
        $this->registerRoute(HttpMethod::PATCH, $uri, $action);
    }

    /**
     * Registers a DELETE route.
     *
     * @param string   $uri    route URI pattern
     * @param \Closure $action route action callback
     */
    public function delete(string $uri, \Closure $action): void
    {
        $this->registerRoute(HttpMethod::DELETE, $uri, $action);
    }

    /**
     * Registers a route for a specific HTTP method.
     *
     * @param HttpMethod $method HTTP method bucket
     * @param string     $uri    route URI pattern
     * @param \Closure   $action route action callback
     */
    protected function registerRoute(HttpMethod $method, string $uri, \Closure $action): void
    {
        $this->routes[$method->value][] = new Route($uri, $action);
    }
}
