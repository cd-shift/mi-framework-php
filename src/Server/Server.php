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
     * Returns the request sent by the client.
     *
     * @return Request
     */
    public function getRequest(): Request;

    /**
     * Sends a response to the client.
     *
     * @param Response $response Response to send.
     * @return void
     */
    public function sendResponse(Response $response): void;
}
