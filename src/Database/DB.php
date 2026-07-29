<?php

declare(strict_types=1);

namespace Database;

class DB
{
    public static function statement(string $query, array $bindings = []): array
    {
        return app()->database->statement($query, $bindings);
    }
}
