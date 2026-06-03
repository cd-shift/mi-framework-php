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
     *
     * @return int
     */
    public function status(): int
    {
        return $this->status;
    }

    /**
     * Sets the status code.
     *
     * @param int $status HTTP status code.
     * @return self
     */
    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Returns all response headers.
     *
     * @param string|null $key Header name to fetch.
     * @return array<string, string>|string|null
     */
    public function headers(?string $key = null): array|string|null
    {
        if (is_null($key)) {
            return $this->headers;
        }
        return $this->headers[strtolower($key)] ?? null;
    }

    /**
     * Sets or overwrites a response header.
     *
     * @param string $header Header name.
     * @param string|int $value Header value.
     * @return self
     */
    public function setHeader(string $header, string|int $value): self
    {
        $this->headers[strtolower($header)] = $value;

        return $this;
    }

    /**
     * Removes a response header if it exists.
     *
     * @param string $header Header name.
     * @return void
     */
    public function removeHeader(string $header): void
    {
        unset($this->headers[strtolower($header)]);
    }

    /**
     * Sets the response content type header.
     *
     * @param string $value MIME type value.
     * @return self
     */
    public function setContentType(string $value): self
    {
        $this->setHeader('Content-Type', $value);

        return $this;
    }

    /**
     * Returns the response body content.
     *
     * @return string|null
     */
    public function content(): ?string
    {
        return $this->content;
    }

    /**
     * Sets the response body content.
     *
     * @param string $content Response body.
     * @return self
     */
    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Normalizes response headers based on the current content.
     *
     * @return void
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
     * @param array<string, mixed> $data Payload data.
     * @return self
     */
    public static function json(array $data): self
    {
        return (new Response())
            ->setContentType('application/json')
            ->setContent(json_encode($data))
        ;
    }

    /**
     * Creates a plain text response.
     *
     * @param string $text Response text.
     * @return self
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
     * @param string $uri Destination URI.
     * @return self
     */
    public static function redirect(string $uri): self
    {
        return (new self())
            ->setStatus(302)
            ->setHeader('Location', $uri)
        ;
    }
}
