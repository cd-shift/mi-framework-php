<?php

declare(strict_types=1);

namespace Server;

use Http\HttpMethod;
use Http\Request;
use Http\Response;

/**
 * Server adapter backed by PHP native superglobals and header functions.
 */
class PhpNativeServer implements Server
{
    /**
     * Reads the current request URI path from PHP runtime state.
     */
    public function getRequest(): Request
    {
        return (new Request())
                ->setUri(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))
                ->setMethod(HttpMethod::from($_SERVER['REQUEST_METHOD']))
                ->setPostData($_POST)
                ->setQueryParams($_GET);
    }


    /**
     * Sends the normalized response status, headers, and body to the client.
     *
     * @param Response $response response to send
     */
    public function sendResponse(Response $response): void
    {
        /*
         * PHP sends a default Content-Type header. It is removed to allow
         * header-less responses when the response has no content.
         */
        header('Content-Type: None');
        header_remove('Content-Type');

        $response->prepare();
        http_response_code($response->status());
        foreach ($response->headers() as $header => $value) {
            header("{$header}: {$value}");
        }
        echo $response->content();
    }
}
