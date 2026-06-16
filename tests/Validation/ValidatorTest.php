<?php

declare(strict_types=1);

namespace tests\Validation;

use PHPUnit\Framework\TestCase;
use Validation\Exceptions\ValidationException;
use Validation\Rule;
use Validation\Rules\Email;
use Validation\Rules\Required;
use Validation\Validator;

class ValidatorTest extends TestCase
{
    public function test_invalid_email_returns_email_rule_error(): void
    {
        $validator = new Validator([
            'test' => 'hola',
            'num' => '5',
            'email' => 'testtest.com',
        ]);

        $this->expectException(ValidationException::class);

        try {
            $validator->validate([
                'test' => Rule::required(),
                'num' => Rule::number(),
                'email' => [Rule::required(), Rule::email()],
            ]);
        } catch (ValidationException $exception) {
            $this->assertSame([
                'email' => [
                    Email::class => 'Email has invalid format',
                ],
            ], $exception->errors());

            throw $exception;
        }
    }

    public function test_empty_email_uses_custom_required_message(): void
    {
        $validator = new Validator([
            'test' => 'hola',
            'num' => '5',
            'email' => '',
        ]);

        $this->expectException(ValidationException::class);

        try {
            $validator->validate([
                'test' => Rule::required(),
                'num' => Rule::number(),
                'email' => [Rule::required(), Rule::email()],
            ], [
                'email' => [Required::class => 'Dame el CAMPO'],
            ]);
        } catch (ValidationException $exception) {
            $this->assertSame([
                'email' => [
                    Required::class => 'Dame el CAMPO',
                    Email::class => 'Email has invalid format',
                ],
            ], $exception->errors());

            throw $exception;
        }
    }
}
