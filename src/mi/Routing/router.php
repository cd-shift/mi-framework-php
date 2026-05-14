<?php

namespace mi\Routing;

use Closure;
use mi\Http\HttpMethod;
use mi\Http\HttpNotFoundException;
use mi\Http\Request;

class Router {
    protected array $routes = [];

    public function __construct() {
        foreach (HttpMethod::cases() as $method) {
            $this->routes[$method->value] = [];
        } 
    }

    public function resolve(Request $request) {
        foreach ($this->routes[$request->method()->value] as $route) {
            if ($route->matches($request->uri())) {
                return $route;
            }
        }
        throw new HttpNotFoundException();
    }

    // Funcion para construir rutas en general
    protected function registerRoute(HttpMethod $method, String $uri ,Closure $action) {
        $this->routes[$method->value][] = new Route($uri, $action);
    }

    // Construccion de las rutas HTTP especificas
    public function get(string $uri, Closure $action) {
        $this->registerRoute(HttpMethod::GET, $uri, $action);
    }

    public function post(string $uri, Closure $action) {
        $this->registerRoute(HttpMethod::POST, $uri, $action);
    }

    public function put(string $uri, Closure $action) {
        $this->registerRoute(HttpMethod::PUT, $uri, $action);
    }

    public function patch(string $uri, Closure $action){
        $this->registerRoute(HttpMethod::PATCH, $uri, $action);
    }

    public function delete(string $uri, Closure $action) {
        $this->registerRoute(HttpMethod::DELETE, $uri, $action);
    }
}