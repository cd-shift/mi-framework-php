<?php

namespace tests;

use Http\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase 
{
    public function test_json_response_is_structured_correctly()
    {
        $content = ["test" => "HOLA", 
                    "num" => "1"];
        $response = Response::json($content);

        $this->assertEquals(200, $response->status());
        $this->assertEquals(json_encode($content), $response->content());
        $this->assertEquals(["content-type" => "application/json"], $response->headers());
    }

    public function test_text_response_is_constructed_correctly() 
    {
        $content = "Response Test";
        $response = Response::text($content);

        $this->assertEquals(200, $response->status());
        $this->assertEquals($content, $response->content());
        $this->assertEquals(["content-type" => "text/plain"], $response->headers());
    }

    public function test_redirect_response_is_constructed_correctly() 
    {
        $uri = "redirect/uri";
        $response = Response::redirect($uri);

        $this->assertEquals(302, $response->status());
        $this->assertNull($response->content());
        $this->assertEquals((['location' => $uri]), $response->headers());
    }

    public function test_prepare_method_removes_content_headers_if_there_is_no_content() 
    {
        $response = new Response();
        $response->setContentType("Test");
        $response->setHeader("Content-Length", 10);
        $response->prepare();

        $this->assertEmpty($response->headers());
    }

    public function test_prepare_method_adds_content_length_header_if_there_is_content() 
    {
        $content = "Contenido Test";
        $response = Response::text($content);
        $response->prepare();

        $this->assertNotEmpty($response->headers());
        $this->assertEquals(strlen($content), $response->headers()["content-length"]);
    }
}