<?php

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\TestCase;
use Routing\Route;

/**
 * Tests route matching and parameter extraction behavior.
 */
class RouteTest extends TestCase
{
    /**
     * Provides route definitions without path parameters.
     *
     * @return array<int, array{0: string}>
     */
    public static function routesWithNoParameters(): array
    {
        return [
            ['/'],
            ['/test'],
            ['/test/nested'],
            ['/test/another/nested'],
            ['/test/another/nested/route'],
            ['/test/another/nested/very/nested/route/'],
        ];
    }

    /**
     * Verifies that static routes match expected URIs and reject invalid ones.
     *
     * @param string $uri Route URI definition to validate.
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('routesWithNoParameters')]
    public function test_regex_with_no_parameters(string $uri): void
    {
        $route = new Route($uri, fn () => 'test');
        $this->assertTrue($route->matches($uri));
        $this->assertFalse($route->matches("$uri/extra/path"));
        $this->assertFalse($route->matches("/some/path/$uri"));
        $this->assertFalse($route->matches('/random/route'));
    }

    /**
     * Verifies that static routes accept a trailing slash.
     *
     * @param string $uri Route URI definition to validate.
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('routesWithNoParameters')]
    public function test_regex_on_uri_that_ends_with_slash(string $uri): void
    {
        $route = new Route($uri, fn () => 'test');
        $this->assertTrue($route->matches("$uri/"));
    }

    /**
     * Provides route definitions with parameters and expected parsed values.
     *
     * @return array<int, array{0: string, 1: string, 2: array<string, int|string>}>
     */
    public static function routesWithParameters(): array
    {
        return [
            [
                '/test/{test}',
                '/test/1',
                ['test' => 1],
            ],
            [
                '/users/{user}',
                '/users/2',
                ['user' => 2],
            ],
            [
                '/test/{test}',
                '/test/string',
                ['test' => 'string'],
            ],
            [
                '/test/nested/{route}',
                '/test/nested/5',
                ['route' => 5],
            ],
            [
                '/test/{param}/long/{test}/with/{multiple}/params',
                '/test/12345/long/5/with/yellow/params',
                ['param' => 12345, 'test' => 5, 'multiple' => 'yellow'],
            ],
        ];
    }

    /**
     * Verifies regex matching behavior for parameterized routes.
     *
     * @param string $definition Route definition with placeholders.
     * @param string $uri URI to validate against the route.
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('routesWithParameters')]
    public function test_regex_with_parameters(string $definition, string $uri): void
    {
        $route = new Route($definition, fn () => 'test');
        $this->assertTrue($route->matches($uri));
        $this->assertFalse($route->matches("$uri/extra/path"));
        $this->assertFalse($route->matches("/some/path/$uri"));
        $this->assertFalse($route->matches('/random/route'));
    }

    /**
     * Verifies parameter extraction from matching URIs.
     *
     * @param string $definition Route definition with placeholders.
     * @param string $uri URI to parse.
     * @param array<string, int|string> $expectedParameters Expected parsed values.
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('routesWithParameters')]
    public function test_parse_parameters(string $definition, string $uri, array $expectedParameters): void
    {
        $route = new Route($definition, fn () => 'test');
        $this->assertTrue($route->hasParameter());
        $this->assertEquals($expectedParameters, $route->parseParameters($uri));
    }
}
