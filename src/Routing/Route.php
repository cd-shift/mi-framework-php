<?php

declare(strict_types=1);

namespace Routing;

use Closure;
use Container\Container;
use Framework\App;

/**
 * Represents a route definition and URI matching rules.
 */
class Route
{
    /**
     * Route URI pattern.
     */
    protected string $uri;

    /**
     * Route action callback.
     */
    protected \Closure $action;

    /**
     * Regular expression derived from the URI pattern.
     */
    protected string $regex;

    /**
     * Ordered list of parameter names declared in the route pattern.
     *
     * @var array<int, string>
     */
    protected array $params;

    protected array $middlewares = [];

    /**
     * Creates a route from a URI pattern and its action callback.
     *
     * @param string   $uri    route URI pattern
     * @param \Closure $action action to execute when the route matches
     */
    public function __construct(string $uri, \Closure $action)
    {
        $this->uri = $uri;
        $this->action = $action;
        $this->regex = preg_replace('/\{([a-zA-Z]+)\}/', '([a-zA-Z0-9]+)', $uri);
        preg_match_all('/\{([a-zA-Z]+)\}/', $uri, $params);
        $this->params = $params[1];
    }

    /**
     * Returns the original URI pattern.
     */
    public function uri(): string
    {
        return $this->uri;
    }

    /**
     * Returns the route action callback.
     */
    public function action(): \Closure
    {
        return $this->action;
    }

    public function middlewares(): array
    {
        return $this->middlewares;
    }

    public function setMiddlewares(array $middlewares): self
    {
        $this->middlewares = array_map(fn ($middleware) => new $middleware(), $middlewares);
        return $this;
    }

    public function hasMiddlewares(): bool
    {
        return count($this->middlewares) > 0;
    }

    /**
     * Determines whether a URI matches this route pattern.
     *
     * @param string $uri request URI
     */
    public function matches(string $uri): bool
    {
        return 1 === preg_match("#^{$this->regex}/?$#", $uri);
    }

    /**
     * Indicates whether the route pattern declares parameters.
     */
    public function hasParameter(): bool
    {
        return count($this->params) > 0;
    }

    /**
     * Extracts parameter values from a matching URI.
     *
     * @param string $uri request URI
     *
     * @return array<string, string>
     */
    public function parseParameters(string $uri): array
    {
        preg_match("#^/?{$this->regex}$#", $uri, $arguments);

        return \array_combine($this->params, \array_slice($arguments, 1)) ?: [];
    }

    public static function get(string $uri, Closure $action): Route
    {
        return Container::resolve(App::class)->router->get($uri, $action);
    }
}
