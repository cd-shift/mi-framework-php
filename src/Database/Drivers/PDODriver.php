<?php

declare(strict_types=1);

namespace Database\Drivers;

use Closure;
use Database\Exceptions\ConnectionException;
use Database\Exceptions\QueryException;
use Database\Exceptions\UnsupportedDriverException;
use Database\Query\Identifier;
use PDO;
use PDOException;
use PDOStatement;
use Throwable;

class PDODriver implements DatabaseDriver
{
    private ?PDO $pdo = null;

    private array $config = [];

    private bool $queryLogEnabled = false;

    private array $queryLog = [];

    private ?string $lastQuery = null;

    private array $lastBindings = [];

    public function connect(
        string $protocol,
        string $host,
        int $port,
        string $database,
        string $username,
        string $password,
        string $charset = 'utf8mb4',
        array $options = [],
    ): void {
        $this->config = compact('protocol', 'host', 'port', 'database', 'username', 'password', 'charset', 'options');

        try {
            $this->pdo = $this->createConnection($this->config);
        } catch (PDOException $e) {
            $this->pdo = null;

            throw new ConnectionException(
                "Failed to connect to database [{$protocol}]: {$e->getMessage()}",
                0,
                $e,
            );
        }
    }

    public function close(): void
    {
        // Roll back any open transaction before dropping the connection so
        // uncommitted work is never silently discarded while the caller
        // believes it was saved.
        if ($this->inTransaction()) {
            try {
                $this->pdo->rollBack();
            } catch (PDOException $e) {
                error_log('PDODriver::close() failed to roll back an open transaction: ' . $e->getMessage());
            }
        }

        $this->pdo = null;
        $this->queryLog = [];
        $this->lastQuery = null;
        $this->lastBindings = [];
    }

    public function isConnected(): bool
    {
        return $this->pdo instanceof PDO;
    }

    public function reconnect(): void
    {
        $config = $this->config;

        $this->close();
        $this->connect(
            $config['protocol'],
            $config['host'],
            $config['port'],
            $config['database'],
            $config['username'],
            $config['password'],
            $config['charset'],
            $config['options'],
        );
    }

    public function getPdo(): PDO
    {
        $this->ensureConnected();

        return $this->pdo;
    }

    public function getConfig(): array
    {
        // Security: keep credentials out of the public API. Internal callers
        // such as reconnect() read $this->config directly and stay untouched.
        $config = $this->config;
        $config['password'] = '******';

        return $config;
    }

    public function getDatabaseName(): string
    {
        return $this->config['database'] ?? '';
    }

    public function statement(string $query, array $bindings = []): array
    {
        $rows = $this->run($query, $bindings)->fetchAll();

        return $rows === false ? [] : $rows;
    }

    public function execute(string $query, array $bindings = []): int
    {
        return $this->run($query, $bindings)->rowCount();
    }

    public function select(string $query, array $bindings = []): array
    {
        return $this->statement($query, $bindings);
    }

    public function selectOne(string $query, array $bindings = []): ?array
    {
        $rows = $this->statement($query, $bindings);

        return $rows[0] ?? null;
    }

    public function selectValue(string $query, array $bindings = []): mixed
    {
        $value = $this->run($query, $bindings)->fetchColumn();

        return $value === false ? null : $value;
    }

    public function selectColumn(string $query, array $bindings = []): array
    {
        return $this->run($query, $bindings)->fetchAll(PDO::FETCH_COLUMN);
    }

    public function insert(string $table, array $data): int|string
    {
        if ($data === []) {
            throw new QueryException('Cannot insert an empty data set', 0, null, "INSERT INTO {$table}");
        }

        // Security: quote table and column identifiers with the active driver
        // dialect so caller-supplied names can never inject SQL.
        $protocol = $this->protocol();
        $table = Identifier::wrapSegment($table, $protocol);
        $columns = implode(', ', array_map(
            static fn (string $column): string => Identifier::wrapSegment($column, $protocol),
            array_keys($data),
        ));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $query = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";

        $this->execute($query, array_values($data));

        return $this->lastInsertId();
    }

    public function update(string $table, array $data, string $where, array $bindings = []): int
    {
        $protocol = $this->protocol();
        $table = Identifier::wrapSegment($table, $protocol);
        $set = implode(', ', array_map(
            static fn (string $column): string => Identifier::wrapSegment($column, $protocol) . ' = ?',
            array_keys($data),
        ));
        $query = "UPDATE {$table} SET {$set} WHERE {$where}";

        return $this->execute($query, array_merge(array_values($data), $bindings));
    }

    public function delete(string $table, string $where, array $bindings = []): int
    {
        $protocol = $this->protocol();
        $table = Identifier::wrapSegment($table, $protocol);
        $query = "DELETE FROM {$table} WHERE {$where}";

        return $this->execute($query, $bindings);
    }

    public function lastInsertId(?string $name = null): int|string
    {
        $this->ensureConnected();

        return $this->pdo->lastInsertId($name);
    }

    public function beginTransaction(): bool
    {
        $this->ensureConnected();

        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        $this->ensureConnected();

        return $this->pdo->commit();
    }

    public function rollBack(): bool
    {
        $this->ensureConnected();

        return $this->pdo->rollBack();
    }

    public function inTransaction(): bool
    {
        return $this->pdo?->inTransaction() ?? false;
    }

    public function transaction(Closure $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();

            return $result;
        } catch (Throwable $e) {
            if ($this->inTransaction()) {
                $this->rollBack();
            }

            throw $e;
        }
    }

    public function tableExists(string $table): bool
    {
        $protocol = $this->config['protocol'] ?? '';

        $result = match ($protocol) {
            'sqlite' => $this->selectValue(
                "SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = ?",
                [$table],
            ),
            'mysql' => $this->selectValue(
                'SELECT 1 FROM information_schema.tables WHERE table_schema = ? AND table_name = ?',
                [$this->config['database'], $table],
            ),
            'pgsql' => $this->selectValue(
                "SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = ?",
                [$table],
            ),
            // An unknown driver must fail loudly instead of masquerading as
            // "table not found", which would hide misconfiguration bugs.
            default => throw new UnsupportedDriverException(
                "tableExists() does not support the [{$protocol}] driver.",
            ),
        };

        return (bool) $result;
    }

    public function enableQueryLog(): void
    {
        $this->queryLogEnabled = true;
    }

    public function disableQueryLog(): void
    {
        $this->queryLogEnabled = false;
    }

    public function getQueryLog(): array
    {
        return $this->queryLog;
    }

    public function getLastQuery(): ?string
    {
        return $this->lastQuery;
    }

    private function protocol(): string
    {
        return $this->config['protocol'] ?? '';
    }

    private function createConnection(array $config): PDO
    {
        $defaults = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        if ($config['protocol'] === 'mysql') {
            $defaults[PDO::ATTR_EMULATE_PREPARES] = false;
        }

        $options = array_replace($defaults, $config['options']);

        // Security: the error and fetch modes must not be overridable through
        // user-supplied options, otherwise prepare()/execute() failures would
        // be swallowed silently instead of surfacing as exceptions.
        $options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        $options[PDO::ATTR_DEFAULT_FETCH_MODE] = PDO::FETCH_ASSOC;

        return new PDO(
            $this->buildDsn($config),
            $config['username'],
            $config['password'],
            $options,
        );
    }

    private function buildDsn(array $config): string
    {
        return match ($config['protocol']) {
            'sqlite' => "sqlite:{$config['database']}",
            'mysql' => "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}",
            'pgsql' => "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']}",
            'sqlsrv' => "sqlsrv:Server={$config['host']},{$config['port']};Database={$config['database']}",
            default => "{$config['protocol']}:host={$config['host']};port={$config['port']};dbname={$config['database']}",
        };
    }

    private function run(string $query, array $bindings): PDOStatement
    {
        $this->ensureConnected();

        $start = microtime(true);

        try {
            $statement = $this->pdo->prepare($query);

            // prepare() may return false if a driver ignores ERRMODE; never
            // call execute() on a failed statement.
            if ($statement === false) {
                throw new QueryException('Failed to prepare the query.', 0, null, $query, $bindings);
            }

            $statement->execute($bindings);
        } catch (PDOException $e) {
            throw new QueryException($e->getMessage(), $e->getCode(), $e, $query, $bindings);
        } finally {
            $this->record($query, $bindings, $start);
        }

        return $statement;
    }

    private function record(string $query, array $bindings, float $start): void
    {
        if ($this->queryLogEnabled) {
            $this->queryLog[] = [
                'query' => $query,
                'bindings' => $bindings,
                'time' => microtime(true) - $start,
            ];
        }

        $this->lastQuery = $query;
        $this->lastBindings = $bindings;
    }

    private function ensureConnected(): void
    {
        if (!$this->pdo instanceof PDO) {
            throw new ConnectionException('Database connection is not established. Call connect() first.');
        }
    }
}
