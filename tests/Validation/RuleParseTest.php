<?php

declare(strict_types=1);

namespace tests\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Validation\Exceptions\RuleParseException;
use Validation\Exceptions\UnknownRuleException;
use Validation\Rule;
use Validation\Rules\Email;
use Validation\Rules\LessThan;
use Validation\Rules\Number;
use Validation\Rules\Required;
use Validation\Rules\RequiredWith;

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

    public function test_parsing_unknown_rules_throws_unkown_rule_exception(): void
    {
        $this->expectException(UnknownRuleException::class);
        $this->expectExceptionMessage('Rule non_existent not found');

        Rule::from('non_existent');
    }

    public static function parameterizedRules(): array
    {
        return [
            [LessThan::class, 'less_than:100', 'test', ['test' => 150], false],
            [LessThan::class, 'less_than:100', 'test', ['test' => 50], true],
            [RequiredWith::class, 'required_with:other', 'test', ['other' => 1], false],
            [RequiredWith::class, 'required_with:other', 'test', ['other' => 1, 'test' => 'x'], true],
        ];
    }

    #[DataProvider('parameterizedRules')]
    public function test_parse_rules_with_parameters($class, $ruleString, $field, $data, $expected): void
    {
        $rule = Rule::from($ruleString);
        $this->assertInstanceOf($class, $rule);
        $this->assertSame($expected, $rule->isValid($field, $data));
    }

    public function test_parsing_rule_with_parameters_without_passing_correct_parameters_throws_rule_parse_exception(): void
    {
        $this->expectException(RuleParseException::class);
        $this->expectExceptionMessage('Rule less_than requires 1 parameters, but 0 were given');

        Rule::from('less_than:');
    }
}
