<?php

declare(strict_types=1);

use Container\Container;
use Framework\App;

/**
 * Returns the bootstrapped application instance or a resolved singleton.
 *
 * @template T of object
 *
 * @param class-string<T> $class Class name to resolve from the container.
 * @return T|null
 */
function app(string $class = App::class): ?object
{
    return Container::resolve($class);
}

/**
 * Returns an existing singleton or creates it on first access.
 *
 * @template T of object
 *
 * @param class-string<T> $class Class name to instantiate.
 * @return T
 */
function singleton(string $class): object
{
    return Container::singleton($class);
}
