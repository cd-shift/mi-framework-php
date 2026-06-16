<?php

declare(strict_types=1);

namespace tests\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Validation\Rules\LessThan;
use Validation\Rules\Number;
use Validation\Rules\RequiredWhen;

class ExtendedValidationRulesTest extends TestCase
{
    public static function numberData(): array
    {
        return [
            [10, true],
            ['10', true],
            ['10.5', true],
            ['abc', false],
            ['', false],
            [null, false],
        ];
    }

    #[DataProvider('numberData')]
    public function test_number(mixed $value, bool $expected): void
    {
        $data = ['test' => $value];
        $rule = new Number();
        $this->assertEquals($expected, $rule->isValid('test', $data));
    }

    public static function lessThanData(): array
    {
        return [
            [5, 10, true],
            ['9.5', 10, true],
            [10, 10, false],
            [11, 10, false],
            ['text', 10, false],
            [null, 10, false],
        ];
    }

    #[DataProvider('lessThanData')]
    public function test_less_than(mixed $value, int|float $limit, bool $expected): void
    {
        $data = ['test' => $value];
        $rule = new LessThan($limit);
        $this->assertEquals($expected, $rule->isValid('test', $data));
    }

    public static function requiredWhenData(): array
    {
        return [
            [['other' => 5, 'test' => 'value'], 'other', '<', 10, true],
            [['other' => 5], 'other', '<', 10, false],
            [['other' => 10, 'test' => 'value'], 'other', '<=', 10, true],
            [['other' => 10], 'other', '<=', 10, false],
            [['other' => 10, 'test' => 'value'], 'other', '=', 10, true],
            [['other' => 10], 'other', '=', 10, false],
            [['other' => 15, 'test' => 'value'], 'other', '>', 10, true],
            [['other' => 15], 'other', '>', 10, false],
            [['other' => 10, 'test' => 'value'], 'other', '>=', 10, true],
            [['other' => 10], 'other', '>=', 10, false],
            [['other' => 8], 'other', '>', 10, true],
            [[], 'other', '<', 10, true],
            [['other' => 'text'], 'other', '<', 10, true],
        ];
    }

    #[DataProvider('requiredWhenData')]
    public function test_required_when(array $data, string $otherField, string $operator, int|float $value, bool $expected): void
    {
        $rule = new RequiredWhen($otherField, $operator, $value);
        $this->assertEquals($expected, $rule->isValid('test', $data));
    }
}
