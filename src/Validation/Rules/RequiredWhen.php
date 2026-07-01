<?php

declare(strict_types=1);

namespace Validation\Rules;

use Override;
use Validation\Exceptions\RuleParseException;

class RequiredWhen implements ValidationRule
{
    protected string $otherField;
    protected string $operator;
    protected int|float $value;

    public function __construct(string $otherField, string $operator, int|float $value)
    {
        if (!in_array($operator, ['<', '<=', '=', '>', '>='], true)) {
            throw new RuleParseException("RequiredWhen only supports <, <=, =, > or >= operators, Unknown operator given: $operator");
        }

        $this->otherField = $otherField;
        $this->operator = $operator;
        $this->value = $value;
    }

    #[Override]
    public function message(): string
    {
        return "This field is required when {$this->otherField} {$this->operator} {$this->value}";
    }

    #[Override]
    public function isValid(string $field, array $data): bool
    {
        if (!$this->shouldRequire($data)) {
            return true;
        }

        return isset($data[$field]) && $data[$field] != '';
    }

    protected function shouldRequire(array $data): bool
    {
        if (!array_key_exists($this->otherField, $data) || !is_numeric($data[$this->otherField])) {
            return false;
        }

        $otherValue = (float) $data[$this->otherField];

        return match ($this->operator) {
            '<' => $otherValue < $this->value,
            '<=' => $otherValue <= $this->value,
            '=' => $otherValue === (float) $this->value,
            '>' => $otherValue > $this->value,
            '>=' => $otherValue >= $this->value,
        };
    }
}
