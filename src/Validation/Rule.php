<?php

declare(strict_types=1);

namespace Validation;

use ReflectionClass;
use Validation\Exceptions\RuleParseException;
use Validation\Exceptions\UnknownRuleException;
use Validation\Rules\Email;
use Validation\Rules\LessThan;
use Validation\Rules\Number;
use Validation\Rules\Required;
use Validation\Rules\RequiredWhen;
use Validation\Rules\RequiredWith;
use Validation\Rules\ValidationRule;

class Rule
{
    private static array $rules = [];
    private static $defaultRules = [
        Required::class,
        RequiredWhen::class,
        RequiredWith::class,
        Number::class,
        LessThan::class,
        Email::class,
    ];

    public static function load(array $rules)
    {
        foreach ($rules as $class) {
            $className = array_slice(explode('\\', $class), -1)[0];
            $ruleName = snake_case($className);
            self::$rules[$ruleName] = $class;

        }
    }

    public static function loadDefaultRules()
    {
        self::load(self::$defaultRules);
    }

    public static function nameOf(ValidationRule $rule): string
    {
        $class = new ReflectionClass($rule);
        return snake_case($class->getShortName());
    }

    public static function email(): ValidationRule
    {
        return new Email();
    }

    public static function number(): ValidationRule
    {
        return new Number();
    }

    public static function lessThan(int|float $value): ValidationRule
    {
        return new LessThan($value);
    }

    public static function required(): ValidationRule
    {
        return new Required();
    }

    public static function requiredWhen(string $otherField, string $operator, int|float $value): ValidationRule
    {
        return new RequiredWhen($otherField, $operator, $value);
    }

    public static function requiredWith(string $withField): ValidationRule
    {
        return new RequiredWith($withField);
    }

    public static function from(string $str): ValidationRule
    {
        if (mb_strlen($str) === 0) {
            throw new RuleParseException("Can't parse empty string to rule.");
        }

        $ruleParts = explode(':', $str);

        if (!array_key_exists($ruleParts[0], self::$rules)) {
            throw new UnknownRuleException("Rule {$ruleParts[0]} not found");
        }

        if (count($ruleParts) === 1) {
            return self::parseBasicRule($ruleParts[0]);
        }

        [$ruleName, $params] = $ruleParts;

        return self::parseRuleWithParameters($ruleName, $params);
    }

    public static function parseBasicRule(string $ruleName): ValidationRule
    {
        $class = new ReflectionClass(self::$rules[$ruleName]);

        if (count($class->getConstructor()?->getParameters() ?? []) > 0) {
            throw new RuleParseException("Rule $ruleName requires parameters, but none have been passed");
        }

        return $class->newInstance();
    }

    public static function parseRuleWithParameters(string $ruleName, string $params): ValidationRule
    {
        $class = new ReflectionClass(self::$rules[$ruleName]);
        $constructorParameters = $class->getConstructor()?->getParameters() ?? [];
        $givenParameters = array_filter(explode(',', $params), fn ($p) => !empty($p));

        if (count($givenParameters) !== count($constructorParameters)) {
            throw new RuleParseException(sprintf(
                'Rule %s requires %d parameters, but %d were given: %s',
                $ruleName,
                count($constructorParameters),
                count($givenParameters),
                $params
            ));
        }

        return $class->newInstance(...$givenParameters);
    }
}
