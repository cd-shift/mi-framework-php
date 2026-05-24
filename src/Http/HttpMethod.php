<?php

declare(strict_types=1);

namespace Http;

/**
 * Defines the supported HTTP request methods.
 */
enum HttpMethod: string
{
    /**
     * Handles read-only resource requests.
     */
    case GET = 'GET';

    /**
     * Handles resource creation requests.
     */
    case POST = 'POST';

    /**
     * Handles full resource update requests.
     */
    case PUT = 'PUT';

    /**
     * Handles partial resource update requests.
     */
    case PATCH = 'PATCH';

    /**
     * Handles resource deletion requests.
     */
    case DELETE = 'DELETE';
}
