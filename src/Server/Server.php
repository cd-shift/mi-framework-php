<?php

declare(strict_types=1);

namespace Server;
use Http\Request;
use Http\Response;

/**
 * Defines the contract required by server adapters.
 */
interface Server
{
    /**
     * Get request sent by client.
     * @return Request
     */
    public function getRequest(): Request;

    /**
     * Sends a response to the client.
     *
     * @param Response $response response to send
     */
    public function sendResponse(Response $response): void;
}
