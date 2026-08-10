<?php

declare(strict_types=1);

namespace Database\Query;

/**
 * Represents a raw SQL expression that should not be escaped or bound as a
 * positional parameter when building queries.
 */
final class Expression
{
    public function __construct(private readonly string $value)
    {
    }

    /**
     * Returns the raw SQL value of the expression.
     */
    public function getValue(): string
    {
        return $this->value;
    }
}
