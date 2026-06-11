<?php

declare(strict_types=1);

namespace Validation\Rules;

interface ValidationRule
{
    public function message(): string;
    public function isValid(string $field, array $data): bool;
}
