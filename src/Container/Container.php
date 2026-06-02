<?php

declare(strict_types=1);

namespace Container;

class Container
{
    private static array $instances = [];

    public static function singleton(string $class)
    {
        if (!isset(self::$instances[$class])) {
            self::$instances[$class] = new $class();
        }
        return self::$instances[$class];
    }

    public static function resolve(string $class)
    {
        return self::$instances[$class] ?? null;
    }
}
