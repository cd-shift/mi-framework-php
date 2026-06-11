<?php

declare(strict_types=1);

namespace Validation\Rules;

use Override;

class RequiredWith implements ValidationRule
{
    protected string $withField;

    public function __construct(string $withField)
    {
        $this->withField = $withField;
    }

    #[Override]
    public function message(): string
    {
        return "This field is required when {$this->withField} is present";
    }

    #[Override]
    public function isValid(string $field, array $data): bool
    {
        if (isset($data[$this->withField]) && ($data[$this->withField]) != '') {
            return isset($data[$field]) && ($data[$field]) != '';
        }

        return true;
    }
}
