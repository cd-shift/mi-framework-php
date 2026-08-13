<?php

declare(strict_types=1);

namespace tests\Database;

use Database\Drivers\PDODriver;
use Database\Exceptions\ConnectionException;
use Database\Exceptions\QueryException;
use Database\Exceptions\UnsupportedDriverException;
use Database\Query\BuilderException;
use Exception;
use PDO;
use PHPUnit\Framework\TestCase;

class PDODriverTest extends TestCase
{
    private PDODriver $driver;

    protected function setUp(): void
    {
        $this->driver = new PDODriver();
        $this->driver->connect('sqlite', '', 0, ':memory:', '', '');

        $this->driver->execute('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, age INTEGER)');
    }

    protected function tearDown(): void
    {
        $this->driver->close();
    }

    public function test_connect_establishes_connection(): void
    {
        $this->assertTrue($this->driver->isConnected());
        $this->assertInstanceOf(PDO::class, $this->driver->getPdo());
    }

    public function test_connect_stores_config(): void
    {
        $config = $this->driver->getConfig();

        $this->assertSame('sqlite', $config['protocol']);
        $this->assertSame(':memory:', $config['database']);
    }

    public function test_get_database_name_returns_configured_database(): void
    {
        $this->assertSame(':memory:', $this->driver->getDatabaseName());
    }

    public function test_connect_sets_exception_error_mode(): void
    {
        $this->assertSame(PDO::ERRMODE_EXCEPTION, $this->driver->getPdo()->getAttribute(PDO::ATTR_ERRMODE));
    }

    public function test_connect_sets_assoc_default_fetch_mode(): void
    {
        $this->assertSame(PDO::FETCH_ASSOC, $this->driver->getPdo()->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE));
    }

    public function test_close_disconnects(): void
    {
        $this->driver->close();

        $this->assertFalse($this->driver->isConnected());
    }

    public function test_reconnect_restores_connection(): void
    {
        $this->driver->reconnect();

        $this->assertTrue($this->driver->isConnected());
    }

    public function test_insert_returns_last_insert_id_and_persists(): void
    {
        $id = $this->driver->insert('users', ['name' => 'Manolo', 'age' => 30]);

        $this->assertSame(1, (int) $id);
        $this->assertSame('Manolo', $this->driver->selectValue('SELECT name FROM users WHERE id = ?', [$id]));
    }

    public function test_insert_with_empty_data_throws_query_exception(): void
    {
        $this->expectException(QueryException::class);

        $this->driver->insert('users', []);
    }

    public function test_select_returns_rows_as_associative_arrays(): void
    {
        $this->seedUsers();

        $rows = $this->driver->select('SELECT * FROM users ORDER BY id');

        $this->assertCount(2, $rows);
        $this->assertSame(['id' => 1, 'name' => 'Manolo', 'age' => 30], $rows[0]);
    }

    public function test_statement_returns_rows(): void
    {
        $this->seedUsers();

        $rows = $this->driver->statement('SELECT name FROM users ORDER BY id');

        $this->assertSame([['name' => 'Manolo'], ['name' => 'Ana']], $rows);
    }

    public function test_select_one_returns_first_row(): void
    {
        $this->seedUsers();

        $row = $this->driver->selectOne('SELECT * FROM users ORDER BY id');

        $this->assertSame('Manolo', $row['name']);
    }

    public function test_select_one_returns_null_when_no_rows(): void
    {
        $this->assertNull($this->driver->selectOne('SELECT * FROM users'));
    }

    public function test_select_value_returns_single_value(): void
    {
        $this->seedUsers();

        $value = $this->driver->selectValue('SELECT name FROM users WHERE id = ?', [2]);

        $this->assertSame('Ana', $value);
    }

    public function test_select_value_returns_null_when_no_value(): void
    {
        $this->assertNull($this->driver->selectValue('SELECT name FROM users WHERE id = ?', [999]));
    }

    public function test_select_column_returns_flat_column_values(): void
    {
        $this->seedUsers();

        $names = $this->driver->selectColumn('SELECT name FROM users ORDER BY id');

        $this->assertSame(['Manolo', 'Ana'], $names);
    }

    public function test_execute_returns_affected_rows(): void
    {
        $this->seedUsers();

        $affected = $this->driver->execute('DELETE FROM users WHERE id > ?', [1]);

        $this->assertSame(1, $affected);
    }

    public function test_update_returns_affected_rows_and_updates_data(): void
    {
        $this->seedUsers();

        $affected = $this->driver->update('users', ['age' => 99], 'id = ?', [1]);

        $this->assertSame(1, $affected);
        $this->assertSame(99, $this->driver->selectValue('SELECT age FROM users WHERE id = 1'));
    }

    public function test_delete_returns_affected_rows_and_deletes_data(): void
    {
        $this->seedUsers();

        $affected = $this->driver->delete('users', 'name = ?', ['Ana']);

        $this->assertSame(1, $affected);
        $this->assertNull($this->driver->selectValue('SELECT name FROM users WHERE name = ?', ['Ana']));
    }

    public function test_last_insert_id_returns_last_id(): void
    {
        $this->seedUsers();

        $this->assertSame(2, (int) $this->driver->lastInsertId());
    }

    public function test_transaction_callback_commits_changes(): void
    {
        $this->driver->transaction(function (PDODriver $db): void {
            $db->insert('users', ['name' => 'Pepe', 'age' => 40]);
        });

        $this->assertSame('Pepe', $this->driver->selectValue('SELECT name FROM users WHERE name = ?', ['Pepe']));
    }

    public function test_transaction_callback_rolls_back_on_exception(): void
    {
        try {
            $this->driver->transaction(function (PDODriver $db): void {
                $db->insert('users', ['name' => 'Pepe', 'age' => 40]);

                throw new Exception('boom');
            });
        } catch (Exception $e) {
            $this->assertSame('boom', $e->getMessage());
        }

        $this->assertNull($this->driver->selectValue('SELECT name FROM users WHERE name = ?', ['Pepe']));
    }

    public function test_manual_transaction_commit(): void
    {
        $this->driver->beginTransaction();
        $this->assertTrue($this->driver->inTransaction());

        $this->driver->insert('users', ['name' => 'Pepe', 'age' => 40]);
        $this->driver->commit();

        $this->assertFalse($this->driver->inTransaction());
        $this->assertSame(1, (int) $this->driver->selectValue('SELECT COUNT(*) FROM users'));
    }

    public function test_manual_transaction_rollback(): void
    {
        $this->driver->beginTransaction();
        $this->driver->insert('users', ['name' => 'Pepe', 'age' => 40]);
        $this->driver->rollBack();

        $this->assertSame(0, (int) $this->driver->selectValue('SELECT COUNT(*) FROM users'));
    }

    public function test_in_transaction_is_false_outside_transaction(): void
    {
        $this->assertFalse($this->driver->inTransaction());
    }

    public function test_table_exists_returns_true_for_existing_table(): void
    {
        $this->assertTrue($this->driver->tableExists('users'));
    }

    public function test_table_exists_returns_false_for_missing_table(): void
    {
        $this->assertFalse($this->driver->tableExists('missing_table'));
    }

    public function test_query_log_records_queries_when_enabled(): void
    {
        $this->driver->enableQueryLog();
        $this->seedUsers();
        $this->driver->select('SELECT * FROM users');

        $log = $this->driver->getQueryLog();

        $this->assertCount(3, $log);

        $select = $log[2];
        $this->assertSame('SELECT * FROM users', $select['query']);
        $this->assertArrayHasKey('bindings', $select);
        $this->assertArrayHasKey('time', $select);
    }

    public function test_query_log_is_empty_when_disabled(): void
    {
        $this->seedUsers();

        $this->assertSame([], $this->driver->getQueryLog());
    }

    public function test_last_query_is_tracked(): void
    {
        $this->seedUsers();
        $this->driver->select('SELECT * FROM users');

        $this->assertSame('SELECT * FROM users', $this->driver->getLastQuery());
    }

    public function test_invalid_sql_throws_query_exception(): void
    {
        $this->expectException(QueryException::class);

        $this->driver->statement('SELECT * FROM nonexistent_table');
    }

    public function test_query_exception_carries_query_and_bindings(): void
    {
        try {
            $this->driver->statement('SELECT * FROM nonexistent WHERE id = ?', [5]);
            $this->fail('Expected QueryException to be thrown.');
        } catch (QueryException $e) {
            $this->assertSame('SELECT * FROM nonexistent WHERE id = ?', $e->getQuery());
            $this->assertSame([5], $e->getBindings());
        }
    }

    public function test_using_driver_without_connection_throws_connection_exception(): void
    {
        $driver = new PDODriver();

        $this->expectException(ConnectionException::class);

        $driver->statement('SELECT 1');
    }

    public function test_get_pdo_without_connection_throws_connection_exception(): void
    {
        $driver = new PDODriver();

        $this->expectException(ConnectionException::class);

        $driver->getPdo();
    }

    public function test_failed_connection_throws_connection_exception(): void
    {
        $driver = new PDODriver();

        $this->expectException(ConnectionException::class);

        $driver->connect('invalid_driver', 'localhost', 0, 'db', 'root', 'pass');
    }

    public function test_get_config_redacts_password(): void
    {
        $driver = new PDODriver();
        $driver->connect('sqlite', '', 0, ':memory:', 'root', 'secret');

        $config = $driver->getConfig();

        $this->assertSame('******', $config['password']);
        $this->assertSame('root', $config['username']);
    }

    public function test_options_cannot_disable_exception_error_mode(): void
    {
        $driver = new PDODriver();
        $driver->connect('sqlite', '', 0, ':memory:', '', '', 'utf8mb4', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
        ]);

        $this->assertSame(PDO::ERRMODE_EXCEPTION, $driver->getPdo()->getAttribute(PDO::ATTR_ERRMODE));
    }

    public function test_close_rolls_back_open_transaction(): void
    {
        $file = sys_get_temp_dir() . '/mi-framework-pdo-' . uniqid() . '.sqlite';

        try {
            $driver = new PDODriver();
            $driver->connect('sqlite', '', 0, $file, '', '');
            $driver->execute('CREATE TABLE t (id INTEGER)');
            $driver->beginTransaction();
            $driver->insert('t', ['id' => 1]);
            $this->assertTrue($driver->inTransaction());

            $driver->close();

            $driver2 = new PDODriver();
            $driver2->connect('sqlite', '', 0, $file, '', '');
            $this->assertSame(0, (int) $driver2->selectValue('SELECT COUNT(*) FROM t'));
            $driver2->close();
        } finally {
            @unlink($file);
            @unlink($file . '-journal');
        }
    }

    public function test_insert_with_invalid_column_throws(): void
    {
        $this->expectException(BuilderException::class);

        $this->driver->insert('users', ['name; DROP TABLE users' => 'x']);
    }

    public function test_insert_with_invalid_table_throws(): void
    {
        $this->expectException(BuilderException::class);

        $this->driver->insert('users; DROP TABLE users', ['name' => 'x']);
    }

    public function test_update_with_invalid_column_throws(): void
    {
        $this->expectException(BuilderException::class);

        $this->driver->update('users', ['name; DROP TABLE users' => 'x'], 'id = ?', [1]);
    }

    public function test_table_exists_throws_for_unsupported_protocol(): void
    {
        $driver = new PDODriver();
        $driver->connect('sqlite', '', 0, ':memory:', '', '');

        $reflection = new \ReflectionProperty(PDODriver::class, 'config');
        $config = $reflection->getValue($driver);
        $config['protocol'] = 'oracle';
        $reflection->setValue($driver, $config);

        $this->expectException(UnsupportedDriverException::class);

        $driver->tableExists('users');
    }

    private function seedUsers(): void
    {
        $this->driver->insert('users', ['name' => 'Manolo', 'age' => 30]);
        $this->driver->insert('users', ['name' => 'Ana', 'age' => 25]);
    }
}
