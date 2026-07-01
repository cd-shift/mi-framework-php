<?php

declare(strict_types=1);

namespace tests\Validation;

use Override;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use Validation\Exceptions\ValidationException;
use Validation\Rule;
use Validation\Rules\Email;
use Validation\Rules\LessThan;
use Validation\Rules\Number;
use Validation\Rules\Required;
use Validation\Rules\RequiredWhen;
use Validation\Rules\RequiredWith;
use Validation\Validator;

class ValidatorTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        Rule::loadDefaultRules();
    }

    public function test_basic_validation_passes()
    {
        $data = [
            'email' => 'test@test.com',
            'other' => 2,
            'num' => 3,
            'foo' => 5,
            'bar' => 4
        ];

        $rules = [
            'email' => new Email(),
            'other' => new Required(),
            'num' => new Number(),
        ];

        $expected = [
            'email' => 'test@test.com',
            'other' => 2,
            'num' => 3,
        ];

        $v = new Validator($data);

        $this->assertEquals($expected, $v->validate($rules));
    }

    public function test_throws_validation_exception_on_invalid_data()
    {
        $this->expectException(ValidationException::class);
        $v = new Validator(['test' => 'test']);
        $v->validate(['test' => new Number()]);
    }

    #[Depends('test_basic_validation_passes')]
    public function test_multiple_rules_validation()
    {
        $data = ['age' => 20, 'num' => 3, 'foo' => 5];

        $rules = [
            'age' => new LessThan(100),
            'num' => [new RequiredWith('age'), new Number()],
        ];

        $expected = ['age' => 20, 'num' => 3];

        $v = new Validator($data);

        $this->assertEquals($expected, $v->validate($rules));
    }

    public function test_overrides_error_messages_correctly()
    {
        $data = ['email' => 'test@', 'num1' => 'not a number'];

        $rules = [
            'email' => 'email',
            'num1' => 'number',
            'num2' => ['required', 'number'],
        ];

        $messages = [
            'email' => ['email' => 'test email message'],
            'num1' => ['number' => 'test number message'],
            'num2' => [
                'required' => 'test required message',
                'number' => 'test number message again'
            ]
        ];

        $v = new Validator($data);

        try {
            $v->validate($rules, $messages);
            $this->fail('Did not throw ValidationException');
        } catch (ValidationException $e) {
            $this->assertEquals($messages, $e->errors());
        }
    }

    public function test_basic_validation_passes_with_strings()
    {
        $data = ['email' => 'test@test.com', 'number' => 25];

        $rules = ['email' => ['email', 'required'],
                  'number' => ['number', 'required']];

        $expected = ['email' => 'test@test.com', 'number' => 25];

        $v = new Validator($data);

        $this->assertEquals($expected, $v->validate($rules));
    }

    public function test_returns_messages_for_each_rule_that_doesnt_pass()
    {
        $data = ['email' => 'test#testcom', 'number1' => 101, 'number2' => 'NoNumero'];

        $rules = [
            'email' => ['email'],
            'number1' => ['less_than:100'],
            'number2' => ['number'],
            'name' => ['required'],
            'phone' => ['required_with:email'],
            'id' => ['required_when:number1,>=,101'],
        ];

        $expected = [
            'email' => ['email' => (new Email())->message()],
            'number1' => ['less_than' => (new LessThan(100))->message()],
            'number2' => ['number' => (new Number())->message()],
            'name' => ['required' => (new Required())->message()],
            'phone' => ['required_with' => (new RequiredWith('email'))->message()],
            'id' => ['required_when' => (new RequiredWhen('number1', '>=', 101))->message()],
        ];

        $v = new Validator($data);

        try {
            $v->validate($rules);
            $this->fail('Did not throw ValidationException');
        } catch (ValidationException $e) {
            $this->assertEquals($expected, $e->errors());
        }
    }
}
