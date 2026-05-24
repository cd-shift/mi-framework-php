<?php

declare(strict_types=1);

namespace Server;

use Http\HttpMethod;
use Http\Response;

/**
 * Defines the contract required by server adapters.
 */
interface Server
{
    /**
     * Returns the current request URI path.
     */
    public function requestUri(): string;

    /**
     * Returns the current request HTTP method.
     */
    public function requestMethod(): HttpMethod;

    /**
     * Returns request payload data.
     *
     * @return array<string, mixed>
     */
    public function postData(): array;

    /**
     * Returns request query parameters.
     *
     * @return array<string, mixed>
     */
    public function queryParams(): array;

    /**
     * Sends a response to the client.
     *
     * @param Response $response response to send
     */
    public function sendResponse(Response $response): void;
}
