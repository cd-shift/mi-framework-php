<?php

declare(strict_types=1);

namespace tests;

use Http\Response;
use PHPUnit\Framework\TestCase;

/**
 * Tests response factory methods and header normalization behavior.
 */
class ResponseTest extends TestCase
{
    /**
     * Verifies that JSON responses are created with the expected content and headers.
     *
     * @return void
     */
    public function test_json_response_is_structured_correctly(): void
    {
        $content = ['test' => 'HOLA', 'num' => '1'];
        $response = Response::json($content);

        $this->assertEquals(200, $response->status());
        $this->assertEquals(json_encode($content), $response->content());
        $this->assertEquals(['content-type' => 'application/json'], $response->headers());
    }

    /**
     * Verifies that plain text responses are created with the expected content and headers.
     *
     * @return void
     */
    public function test_text_response_is_constructed_correctly(): void
    {
        $content = 'Response Test';
        $response = Response::text($content);

        $this->assertEquals(200, $response->status());
        $this->assertEquals($content, $response->content());
        $this->assertEquals(['content-type' => 'text/plain'], $response->headers());
    }

    /**
     * Verifies that redirect responses contain the expected status and location header.
     *
     * @return void
     */
    public function test_redirect_response_is_constructed_correctly(): void
    {
        $uri = 'redirect/uri';
        $response = Response::redirect($uri);

        $this->assertEquals(302, $response->status());
        $this->assertNull($response->content());
        $this->assertEquals((['location' => $uri]), $response->headers());
    }

    /**
     * Verifies that prepare removes content headers when the response body is empty.
     *
     * @return void
     */
    public function test_prepare_method_removes_content_headers_if_there_is_no_content(): void
    {
        $response = new Response();
        $response->setContentType('Test');
        $response->setHeader('Content-Length', 10);
        $response->prepare();

        $this->assertEmpty($response->headers());
    }

    /**
     * Verifies that prepare adds a content length header when body content exists.
     *
     * @return void
     */
    public function test_prepare_method_adds_content_length_header_if_there_is_content(): void
    {
        $content = 'Contenido Test';
        $response = Response::text($content);
        $response->prepare();

        $this->assertNotEmpty($response->headers());
        $this->assertEquals(strlen($content), $response->headers()['content-length']);
    }
}
