<?php

declare(strict_types=1);

namespace Http;

use Server\Server;

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
     * Builds a request object from the current server adapter state.
     *
     * @param Server $server server adapter used to read request data
     */
    public function __construct(Server $server)
    {
        $this->uri = $server->requestUri();
        $this->method = $server->requestMethod();
        $this->data = $server->postData();
        $this->query = $server->queryParams();
    }

    /**
     * Returns the normalized request URI path.
     */
    public function uri(): string
    {
        return $this->uri;
    }

    /**
     * Returns the request HTTP method.
     */
    public function method(): HttpMethod
    {
        return $this->method;
    }

    /**
     * Returns request body data.
     *
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->data;
    }

    /**
     * Returns query string parameters.
     *
     * @return array<string, mixed>
     */
    public function queryParams(): array
    {
        return $this->query;
    }
}
