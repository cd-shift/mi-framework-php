<?php

declare(strict_types=1);

namespace Database;

use Closure;
use Database\Drivers\DatabaseDriver;
use PDO;

class DB
{
    public static function connection(): DatabaseDriver
    {
        return app()->database;
    }

    public static function statement(string $query, array $bindings = []): array
    {
        return self::connection()->statement($query, $bindings);
    }

    public static function execute(string $query, array $bindings = []): int
    {
        return self::connection()->execute($query, $bindings);
    }

    public static function select(string $query, array $bindings = []): array
    {
        return self::connection()->select($query, $bindings);
    }

    public static function selectOne(string $query, array $bindings = []): ?array
    {
        return self::connection()->selectOne($query, $bindings);
    }

    public static function selectValue(string $query, array $bindings = []): mixed
    {
        return self::connection()->selectValue($query, $bindings);
    }

    public static function selectColumn(string $query, array $bindings = []): array
    {
        return self::connection()->selectColumn($query, $bindings);
    }

    public static function insert(string $table, array $data): int|string
    {
        return self::connection()->insert($table, $data);
    }

    public static function update(string $table, array $data, string $where, array $bindings = []): int
    {
        return self::connection()->update($table, $data, $where, $bindings);
    }

    public static function delete(string $table, string $where, array $bindings = []): int
    {
        return self::connection()->delete($table, $where, $bindings);
    }

    public static function lastInsertId(?string $name = null): int|string
    {
        return self::connection()->lastInsertId($name);
    }

    public static function transaction(Closure $callback): mixed
    {
        return self::connection()->transaction($callback);
    }

    public static function beginTransaction(): bool
    {
        return self::connection()->beginTransaction();
    }

    public static function commit(): bool
    {
        return self::connection()->commit();
    }

    public static function rollBack(): bool
    {
        return self::connection()->rollBack();
    }

    public static function tableExists(string $table): bool
    {
        return self::connection()->tableExists($table);
    }

    public static function getPdo(): PDO
    {
        return self::connection()->getPdo();
    }

    public static function getQueryLog(): array
    {
        return self::connection()->getQueryLog();
    }
}
