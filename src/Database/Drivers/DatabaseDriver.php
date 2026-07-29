<?php

declare(strict_types=1);

namespace Database\Drivers;

interface DatabaseDriver
{
    public function connect(
        string $protocol,
        string $host,
        int $port,
        string $database,
        string $username,
        string $password,
    );

    public function close();

    public function statement(string $query, array $bindings = []);
}
