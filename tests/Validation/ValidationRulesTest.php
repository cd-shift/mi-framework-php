<?php

declare(strict_types=1);

namespace tests\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Validation\Rules\Email;
use Validation\Rules\Required;
use Validation\Rules\RequiredWith;

class ValidationRulesTest extends TestCase
{
    public static function emails(): array
    {
        return [
            ['test@test.com', true],
            ['antonio@mastermind.ac', true],
            ['test@testcom', false],
            ['test@test.', false],
            ['antonio@', false],
            ['antonio@.', false],
            ['antonio', false],
            ['@', false],
            ['', false],
            [null, false],
            [4, false],
        ];
    }

    #[DataProvider('emails')]
    public function test_email(mixed $email, bool $expected): void
    {
        $data = ['email' => $email];
        $rule = new Email();
        $this->assertEquals($expected, $rule->isValid('email', $data));
    }

    public static function requiredData(): array
    {
        return [
            ['', false],
            [null, false],
            [5, true],
            ['test', true],
        ];
    }

    #[DataProvider('requiredData')]
    public function test_required(mixed $value, bool $expected): void
    {
        $data = ['test' => $value];
        $rule = new Required();
        $this->assertEquals($expected, $rule->isValid('test', $data));
    }

    public function test_required_with(): void
    {
        $rule = new RequiredWith('other');
        $data = ['other' => 10, 'test' => 5];
        $this->assertTrue($rule->isValid('test', $data));
        $data = ['other' => 10];
        $this->assertFalse($rule->isValid('test', $data));
    }
}
