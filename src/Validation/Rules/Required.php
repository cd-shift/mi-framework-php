<?php

declare(strict_types=1);

namespace Validation\Rules;

use Override;

class Required implements ValidationRule
{
    #[Override]
    public function message(): string
    {
        return 'This field is required';
    }

    #[Override]
    public function isValid(string $field, array $data): bool
    {
        return isset($data[$field]) && ($data[$field]) != '';
    }
}
