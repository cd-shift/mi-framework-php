<?php

declare(strict_types=1);

namespace Database\Exceptions;

use Throwable;

class QueryException extends DatabaseException
{
    public function __construct(
        string $message,
        int|string $code = 0,
        ?Throwable $previous = null,
        private readonly ?string $query = null,
        private readonly array $bindings = [],
    ) {
        parent::__construct($message, (int) $code, $previous);
    }

    public function getQuery(): ?string
    {
        return $this->query;
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }
}
