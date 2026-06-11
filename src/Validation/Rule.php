<?php

declare(strict_types=1);

namespace Validation;

use Validation\Rules\Email;
use Validation\Rules\Required;
use Validation\Rules\RequiredWith;
use Validation\Rules\ValidationRule;

class Rule
{
    public static function email(): ValidationRule
    {
        return new Email();
    }

    public static function required(): ValidationRule
    {
        return new Required();
    }

    public static function requiredWith(string $withField): ValidationRule
    {
        return new RequiredWith($withField);
    }
}
