<?php

declare(strict_types=1);

namespace tests\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Validation\Rule;
use Validation\Rules\Email;
use Validation\Rules\Number;
use Validation\Rules\Required;

class RuleParseTest extends TestCase
{
    protected function setUp(): void
    {
        Rule::loadDefaultRules();
    }

    public static function basicRules(): array
    {
        return [
            [Email::class, 'email'],
            [Required::class, 'required'],
            [Number::class, 'number'],
        ];
    }

    #[DataProvider('basicRules')]
    public function test_parse_basic_rules($class, $name)
    {
        $this->assertInstanceOf($class, Rule::from($name));
    }
}
