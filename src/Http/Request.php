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
     * Route matched by URI
     * @var Route
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

    protected array $headers = [];

    /**
     * Returns the normalized request URI path.
     */
    public function uri(): string
    {
        return $this->uri;
    }

    /**
     * Set request URI.
     * @param string $uri
     * @return self
     */
    public function setUri(string $uri): self
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     * Get route matched by the URI of this request
     * @return Route
     */
    public function route(): Route
    {
        return $this->route;
    }

    /**
     * Set route for this request.
     * @param Route $route
     * @return self
     */
    public function setRoute(Route $route): self
    {
        $this->route = $route;
        return $this;
    }

    /**
     * Returns the request HTTP method.
     */
    public function method(): HttpMethod
    {
        return $this->method;
    }

    /**
     * Set HTTP method
     * @param HttpMethod $method
     * @return self
     */
    public function setMethod(HttpMethod $method): self
    {
        $this->method = $method;
        return $this;
    }

    public function headers(?string $key = null): array|string|null
    {
        if (is_null($key)) {
            return $this->headers;
        }
        return $this->headers[strtolower($key)] ?? null;
    }

    public function setHeaders(array $headers): self
    {
        foreach ($headers as $header => $value) {
            $this->headers[strtolower($header)] = $value;
        }
        return $this;
    }

    /**
     * Returns request body data.
     *
     * @return array<string, mixed>
     */
    public function data(?string $key = null): array|string|null
    {
        if (is_null($key)) {
            return $this->data;
        }
        return $this->data[$key] ?? null;
    }

    /**
     * Set POST data
     * @param array $data
     * @return self
     */
    public function setPostData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Returns query string parameters.
     *
     * @return array<string, mixed>
     */
    public function query(?string $key = null): string|array|null
    {
        if (is_null($key)) {
            return $this->query;
        }
        return $this->query[$key] ?? null;
    }

    /**
     * Set query parameters.
     * @param array $query
     * @return self
     */
    public function setQueryParams(array $query): self
    {
        $this->query = $query;
        return $this;
    }

    public function routeParameters(?string $key = null): string|array|null
    {
        $parameters = $this->route->parseParameters($this->uri);
        if (is_null($key)) {
            return $parameters;
        }
        return $parameters[$key] ?? null;

    }
}
