<?php

declare(strict_types=1);

namespace Validation\Exceptions;

use Exceptions\MiException;

class ValidationException extends MiException
{
    public function __construct(protected array $errors)
    {
        $this->errors = $errors;
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
