<?php

declare(strict_types=1);

namespace Validation\Rules;

use Override;

class Number implements ValidationRule
{
    #[Override]
    public function message(): string
    {
        return 'This field must be numeric';
    }

    #[Override]
    public function isValid(string $field, array $data): bool
    {
        return array_key_exists($field, $data) && is_numeric($data[$field]);
    }
}
