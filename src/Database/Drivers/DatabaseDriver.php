<?php

declare(strict_types=1);

namespace Database\Drivers;

use Closure;
use PDO;

interface DatabaseDriver
{
    public function connect(
        string $protocol,
        string $host,
        int $port,
        string $database,
        string $username,
        string $password,
        string $charset = 'utf8mb4',
        array $options = [],
    ): void;

    public function close(): void;

    public function isConnected(): bool;

    public function reconnect(): void;

    public function getPdo(): PDO;

    public function getConfig(): array;

    public function getDatabaseName(): string;

    public function statement(string $query, array $bindings = []): array;

    public function execute(string $query, array $bindings = []): int;

    public function select(string $query, array $bindings = []): array;

    public function selectOne(string $query, array $bindings = []): ?array;

    public function selectValue(string $query, array $bindings = []): mixed;

    public function selectColumn(string $query, array $bindings = []): array;

    public function insert(string $table, array $data): int|string;

    public function update(string $table, array $data, string $where, array $bindings = []): int;

    public function delete(string $table, string $where, array $bindings = []): int;

    public function lastInsertId(?string $name = null): int|string;

    public function beginTransaction(): bool;

    public function commit(): bool;

    public function rollBack(): bool;

    public function inTransaction(): bool;

    public function transaction(Closure $callback): mixed;

    public function tableExists(string $table): bool;

    public function enableQueryLog(): void;

    public function disableQueryLog(): void;

    public function getQueryLog(): array;

    public function getLastQuery(): ?string;
}
