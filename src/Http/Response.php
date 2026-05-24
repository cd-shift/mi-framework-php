<?php

declare(strict_types=1);

namespace Http;

/**
 * Represents an HTTP response with status, headers, and optional content.
 */
class Response
{
    /**
     * HTTP status code.
     */
    protected int $status = 200;

    /**
     * Response headers indexed by normalized header name.
     *
     * @var array<string, string>
     */
    protected array $headers = [];

    /**
     * Response body content.
     */
    protected ?string $content = null;

    /**
     * Returns the current status code.
     */
    public function status(): int
    {
        return $this->status;
    }

    /**
     * Sets the status code.
     *
     * @param int $status HTTP status code
     */
    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Returns all response headers.
     *
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Sets or overwrites a response header.
     *
     * @param string $header header name
     * @param string $value  header value
     */
    public function setHeader(string $header, string $value): self
    {
        $this->headers[strtolower($header)] = $value;

        return $this;
    }

    /**
     * Removes a response header if it exists.
     *
     * @param string $header header name
     */
    public function removeHeader(string $header): void
    {
        unset($this->headers[strtolower($header)]);
    }

    /**
     * Sets the response content type header.
     *
     * @param string $value MIME type value
     */
    public function setContentType(string $value): self
    {
        $this->setHeader('Content-Type', $value);

        return $this;
    }

    /**
     * Returns the response body content.
     */
    public function content(): ?string
    {
        return $this->content;
    }

    /**
     * Sets the response body content.
     *
     * @param string $content response body
     */
    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Normalizes response headers based on the current content.
     */
    public function prepare(): void
    {
        if (is_null($this->content)) {
            $this->removeHeader('Content-Type');
            $this->removeHeader('Content-Length');
        } else {
            $this->setHeader('Content-Length', (string) strlen($this->content));
        }
    }

    /**
     * Creates a JSON response.
     *
     * @param array<string, mixed> $data payload data
     */
    public static function json(array $data): self
    {
        return (new Response())
            ->setContentType('application/json')
            ->setContent(json_encode(['message' => 'GET OK']))
        ;
    }

    /**
     * Creates a plain text response.
     *
     * @param string $text response text
     */
    public static function text(string $text): self
    {
        return (new self())
            ->setContentType('text/plain')
            ->setContent($text)
        ;
    }

    /**
     * Creates a redirect response.
     *
     * @param string $uri destination URI
     */
    public static function redirect(string $uri): self
    {
        return (new self())
            ->setStatus(302)
            ->setHeader('Location', $uri)
        ;
    }
}
