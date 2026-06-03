<?php

declare(strict_types=1);

namespace Http;

use Routing\Route;

/**
 * Stores normalized request data produced by a server adapter.
 */
class Request
{
    /**
     * Request URI path.
     */
    protected string $uri;

    /**
     * Route matched for this request.
     */
    protected Route $route;

    /**
     * HTTP method used by the request.
     */
    protected HttpMethod $method;

    /**
     * Body payload sent in the request.
     *
     * @var array<string, mixed>
     */
    protected array $data;

    /**
     * Query parameters sent in the URI.
     *
     * @var array<string, mixed>
     */
    protected array $query;

    /**
     * Request headers indexed by normalized header name.
     *
     * @var array<string, string>
     */
    protected array $headers = [];

    /**
     * Returns the normalized request URI path.
     *
     * @return string
     */
    public function uri(): string
    {
        return $this->uri;
    }

    /**
     * Sets the normalized request URI path.
     *
     * @param string $uri Request URI path.
     * @return self
     */
    public function setUri(string $uri): self
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     * Returns the route matched for this request.
     *
     * @return Route
     */
    public function route(): Route
    {
        return $this->route;
    }

    /**
     * Assigns the resolved route to this request.
     *
     * @param Route $route Resolved route.
     * @return self
     */
    public function setRoute(Route $route): self
    {
        $this->route = $route;
        return $this;
    }

    /**
     * Returns the request HTTP method.
     *
     * @return HttpMethod
     */
    public function method(): HttpMethod
    {
        return $this->method;
    }

    /**
     * Sets the request HTTP method.
     *
     * @param HttpMethod $method HTTP method.
     * @return self
     */
    public function setMethod(HttpMethod $method): self
    {
        $this->method = $method;
        return $this;
    }

    /**
     * Returns all headers or a single header value by key.
     *
     * @param string|null $key Header name to fetch.
     * @return array<string, string>|string|null
     */
    public function headers(?string $key = null): array|string|null
    {
        if (is_null($key)) {
            return $this->headers;
        }
        return $this->headers[strtolower($key)] ?? null;
    }

    /**
     * Sets request headers using normalized lower-case keys.
     *
     * @param array<string, string> $headers Header map.
     * @return self
     */
    public function setHeaders(array $headers): self
    {
        foreach ($headers as $header => $value) {
            $this->headers[strtolower($header)] = $value;
        }
        return $this;
    }

    /**
     * Returns request body data or a single value by key.
     *
     * @param string|null $key Payload key to fetch.
     * @return array<string, mixed>|string|null
     */
    public function data(?string $key = null): array|string|null
    {
        if (is_null($key)) {
            return $this->data;
        }
        return $this->data[$key] ?? null;
    }

    /**
     * Sets request body data.
     *
     * @param array<string, mixed> $data Request payload.
     * @return self
     */
    public function setPostData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Returns query parameters or a single value by key.
     *
     * @param string|null $key Query key to fetch.
     * @return array<string, mixed>|string|null
     */
    public function query(?string $key = null): string|array|null
    {
        if (is_null($key)) {
            return $this->query;
        }
        return $this->query[$key] ?? null;
    }

    /**
     * Sets query parameters.
     *
     * @param array<string, mixed> $query Query parameter map.
     * @return self
     */
    public function setQueryParams(array $query): self
    {
        $this->query = $query;
        return $this;
    }

    /**
     * Returns route parameters or a single route parameter by key.
     *
     * @param string|null $key Route parameter key to fetch.
     * @return array<string, string>|string|null
     */
    public function routeParameters(?string $key = null): string|array|null
    {
        $parameters = $this->route->parseParameters($this->uri);
        if (is_null($key)) {
            return $parameters;
        }
        return $parameters[$key] ?? null;
    }
}
