<?php

namespace tests;

use Http\HttpMethod;
use Http\Request;
use PHPUnit\Framework\TestCase;
use Routing\Route;

use function PHPUnit\Framework\assertEquals;

class RequestTest extends TestCase {
    public function test_request_returns_data_obtained_from_server_correctly() {
        $uri = '/test/route';
        $queryParams = ['a' => 1, 'b' => 2, 'test' => 'foo'];
        $postData = ['post' => 'test', 'foo' => 'bar'];

        $request = (new Request())
                    ->setUri($uri)
                    ->setMethod(HttpMethod::POST)
                    ->setQueryParams($queryParams)
                    ->setPostData($postData);

        $this->assertEquals($uri, $request->uri());
        $this->assertEquals($queryParams, $request->query());
        $this->assertEquals($postData, $request->data());
        $this->assertEquals(HttpMethod::POST, $request->method());
    }

    public function test_data_returns_value_if_key_is_given()
    { 
        // Comprueba si la función data de la clase Request se comporta como se ha descrito en el apartado a.
        $data = ['test1' => '1', 'test2' => '2', 'test3' => '3'];
        $request = (new Request())->setPostData($data);

        $this->assertEquals($request->data('test1'), 1);
        $this->assertEquals($request->data('test2'), 2);
        $this->assertNull($request->data("doesn't exist"));
    }

    public function test_query_returns_value_if_key_is_given()
    { 
        // Comprueba si la función query de la clase Request se comporta como se ha descrito en el apartado a.
        $data = ['test1' => '1', 'test2' => '2', 'test3' => '30'];
        $request = (new Request())->setQueryParams($data);

        $this->assertEquals($request->query('test1'), '1');
        $this->assertEquals($request->query('test2'), '2');
        $this->assertNull($request->query("doesn't exist"));
    }

    public function test_route_parameters_returns_value_if_key_is_given()
    { 
        // Comprueba si la función routeParameters de la clase Request se comporta como se ha descrito en el apartado a.
        $route = new Route('/test/{param}/foo/{bar}', fn () => "test");
        $request = (new Request())
                        ->setRoute($route)
                        ->setUri('/test/1/foo/2');
        
        $this->assertEquals($request->routeParameters('param'), 1);
        $this->assertEquals($request->routeParameters('bar'), 2);
        $this->assertNull($request->routeParameters("doesn't exist"));
    }
}