<?php

declare(strict_types=1);

namespace Database\Drivers;

use PDO;
use RuntimeException;

class PDODriver implements DatabaseDriver
{
    private ?PDO $pdo;

    public function connect(
        string $protocol,
        string $host,
        int $port,
        string $database,
        string $username,
        string $password,
    ): void {
        $dsn = "{$protocol}:host={$host};port={$port};dbname={$database}";
        $this->pdo = new PDO($dsn, $username, $password);
    }

    public function close(): void
    {
        $this->pdo = null;
    }

    public function statement(string $query, array $bindings = []): array
    {
        $statement = $this->pdo->prepare($query);
        $statement->execute($bindings);

        $result = $statement->fetchAll(PDO::FETCH_ASSOC);

        if ($result === false) {
            throw new RuntimeException('Failed to fetch results from statement');
        }

        return $result;
    }
}
