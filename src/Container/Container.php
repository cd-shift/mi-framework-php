<?php

declare(strict_types=1);

namespace Container;

/**
 * Stores singleton instances for simple application-wide resolution.
 */
class Container
{
    /**
     * Registered singleton instances indexed by class name.
     *
     * @var array<class-string, object>
     */
    private static array $instances = [];

    /**
     * Returns an existing singleton instance or creates it on first access.
     *
     * @template T of object
     *
     * @param class-string<T> $class Class name to instantiate.
     * @return T
     */
    public static function singleton(string $class): object
    {
        if (!isset(self::$instances[$class])) {
            self::$instances[$class] = new $class();
        }

        return self::$instances[$class];
    }

    /**
     * Resolves a previously registered singleton instance.
     *
     * @template T of object
     *
     * @param class-string<T> $class Class name to resolve.
     * @return T|null
     */
    public static function resolve(string $class): ?object
    {
        return self::$instances[$class] ?? null;
    }
}
