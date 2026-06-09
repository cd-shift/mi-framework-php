<?php

declare(strict_types=1);
/*
    GLOBAL HELPERS FUNCTIONS
*/

use Container\Container;
use Framework\App;

function app(string $class = App::class)
{
    return Container::resolve($class);
}

function singleton(string $class)
{
    return Container::singleton($class);
}
