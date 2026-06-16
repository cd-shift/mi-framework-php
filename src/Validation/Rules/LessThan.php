<?php

declare(strict_types=1);

namespace Validation\Rules;

use Override;

class LessThan implements ValidationRule
{
    protected int|float $value;

    public function __construct(int|float $value)
    {
        $this->value = $value;
    }

    #[Override]
    public function message(): string
    {
        return "This field must be less than {$this->value}";
    }

    #[Override]
    public function isValid(string $field, array $data): bool
    {
        if (!array_key_exists($field, $data) || !is_numeric($data[$field])) {
            return false;
        }

        return (float) $data[$field] < $this->value;
    }
}
