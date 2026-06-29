<?php

declare(strict_types=1);

function snake_case(string $str): string
{
    $str = trim($str);
    $str = preg_replace('/(?<=[a-z\d])([A-Z])/', '_$1', $str);
    $str = preg_replace('/[\s\-_]+/', '_', $str);
    return strtolower($str);
}
