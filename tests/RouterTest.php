<?php

namespace mi\Tests;

use mi\HttpMethod;
use mi\Router;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase{
    public function test_resolve_basic_route_with_callback_action() {
       $uri = '/test';
       $action = fn () => "test";
       $router = new Router();
       $router->get($uri, $action);
       
       $this->assertEquals($action, $router->resolve($uri, HttpMethod::GET->value));
    }

    public function test_resolve_multiple_basic_routes_with_callback_action() {
        $routes = [
            '/test' => fn () => 'test',
            '/foo' => fn () => 'foo',
            'bar'=> fn () => 'bar',
            'long/nested/route' => fn () => 'long nested route',
        ];

        $router = new Router();

        foreach ($routes as $uri => $action) {
            $router->get($uri, $action);
        }

        foreach ($routes as $uri => $action) {
            $this->assertEquals($action, $router->resolve($uri, HttpMethod::GET->value));
        }
    }

    public function test_resolve_multiple_basic_routes_with_callback_action_for_different_http_method() {
        $routes = [
            [HttpMethod::GET,'/test',fn () => "get"],
            [HttpMethod::POST,'/test',fn () => "post"],
            [HttpMethod::PUT,'/test',fn () => "put"],
            [HttpMethod::PATCH,'/test',fn () => "patch"],
            [HttpMethod::DELETE,'/test',fn () => "delete"],

            [HttpMethod::GET,'/random/get',fn () => "get"],
            [HttpMethod::POST,'/random/nested/post',fn () => "post"],
            [HttpMethod::PUT,'/put/random/route',fn () => "put"],
            [HttpMethod::PATCH,'/some/patch,route',fn () => "patch"],
            [HttpMethod::DELETE,'/d',fn () => "delete"],
        ];

        $router = new Router();

        foreach ($routes as [$method,$uri,$action]) {
            // Version Larga
            /*
            match ($method) {
                HttpMethod::GET => $router->get($uri, $action),
                HttpMethod::POST => $router->post($uri, $action),
                HttpMethod::PUT => $router->put($uri, $action),
                HttpMethod::PATCH => $router->patch($uri, $action),
                HttpMethod::DELETE => $router->delete($uri, $action),
            };
            */
            // Version corta
            // lo de strtolower devolvera "get o put o post... algo de magia"
            $router->{strtolower($method->value)}($uri, $action);
        }

        foreach ($routes as [$method,$uri,$action]) {
            $this->assertEquals($action, $router->resolve($uri, $method->value));
        }
    }
}
