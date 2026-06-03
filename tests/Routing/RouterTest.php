<?php

declare(strict_types=1);

namespace tests;

use Closure;
use Http\HttpMethod;
use Http\Request;
use Http\Response;
use PHPUnit\Framework\TestCase;
use Routing\Router;

/**
 * Tests router registration and route resolution behavior.
 */
class RouterTest extends TestCase
{
    /**
     * Creates a mock request with the given URI and HTTP method.
     *
     * @param string $uri Request URI to mock.
     * @param HttpMethod $method Request HTTP method to mock.
     * @return Request
     */
    private function createMockRequest(string $uri, HttpMethod $method): Request
    {
        return (new Request())
                ->setUri($uri)
                ->setMethod($method);
    }

    /**
     * Verifies that a single registered route is resolved correctly.
     *
     * @return void
     */
    public function test_resolve_basic_route_with_callback_action(): void
    {
        $uri = '/test';
        $action = fn () => 'test';
        $router = new Router();
        $router->get($uri, $action);
        $route = $router->resolveRoute($this->createMockRequest($uri, HttpMethod::GET));

        $this->assertEquals($action, $route->action());
        $this->assertEquals($uri, $route->uri());
    }

    /**
     * Verifies that multiple registered routes are resolved correctly.
     *
     * @return void
     */
    public function test_resolve_multiple_basic_routes_with_callback_action(): void
    {
        $routes = [
            '/test' => fn () => 'test',
            '/foo' => fn () => 'foo',
            'bar' => fn () => 'bar',
            'long/nested/route' => fn () => 'long nested route',
        ];

        $router = new Router();

        foreach ($routes as $uri => $action) {
            $router->get($uri, $action);
        }
        foreach ($routes as $uri => $action) {
            $route = $router->resolveRoute($this->createMockRequest($uri, HttpMethod::GET));

            $this->assertEquals($action, $route->action());
            $this->assertEquals($uri, $route->uri());
        }
    }

    /**
     * Verifies route resolution across multiple HTTP methods.
     *
     * @return void
     */
    public function test_resolve_multiple_basic_routes_with_callback_action_for_different_http_method(): void
    {
        $routes = [
            [HttpMethod::GET, '/test', fn () => 'get'],
            [HttpMethod::POST, '/test', fn () => 'post'],
            [HttpMethod::PUT, '/test', fn () => 'put'],
            [HttpMethod::PATCH, '/test', fn () => 'patch'],
            [HttpMethod::DELETE, '/test', fn () => 'delete'],

            [HttpMethod::GET, '/random/get', fn () => 'get'],
            [HttpMethod::POST, '/random/nested/post', fn () => 'post'],
            [HttpMethod::PUT, '/put/random/route', fn () => 'put'],
            [HttpMethod::PATCH, '/some/patch,route', fn () => 'patch'],
            [HttpMethod::DELETE, '/d', fn () => 'delete'],
        ];

        $router = new Router();

        foreach ($routes as [$method, $uri, $action]) {
            $router->{strtolower($method->value)}($uri, $action);
        }

        foreach ($routes as [$method, $uri, $action]) {
            $route = $router->resolveRoute($this->createMockRequest($uri, $method));

            $this->assertEquals($action, $route->action());
            $this->assertEquals($uri, $route->uri());
        }
    }

    public function test_run_middlewares(): void
    {
        $middleware1 = new class () {
            public function handle(Request $request, Closure $next): Response
            {
                $response = $next($request);
                $response->setHeader('X-Middleware1', 'middleware1');
                return $response;
            }
        };

        $middleware2 = new class () {
            public function handle(Request $request, Closure $next): Response
            {
                $response = $next($request);
                $response->setHeader('X-Middleware2', 'middleware2');
                return $response;
            }
        };

        $router = new Router();
        $uri = '/test';
        $expectedResponse = Response::text('test');
        $action = fn ($request) => $expectedResponse;
        $router->get($uri, $action)->setMiddlewares([$middleware1, $middleware2]);

        $request = $this->createMockRequest($uri, HttpMethod::GET);
        $response = $router->resolve($request);

        $this->assertEquals($expectedResponse, $response);
        $this->assertEquals('middleware1', $response->headers('X-Middleware1'));
        $this->assertEquals('middleware2', $response->headers('X-Middleware2'));
    }
}
