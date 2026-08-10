<?php

declare(strict_types=1);

namespace tests\Database;

use Container\Container;
use Database\DB;
use Database\Drivers\PDODriver;
use Framework\App;
use PHPUnit\Framework\TestCase;

class DBTest extends TestCase
{
    protected function setUp(): void
    {
        $app = Container::singleton(App::class);
        $app->database = new PDODriver();
        $app->database->connect('sqlite', '', 0, ':memory:', '', '');

        DB::execute('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL)');
    }

    protected function tearDown(): void
    {
        $app = Container::singleton(App::class);
        $app->database->close();
    }

    public function test_connection_returns_database_driver(): void
    {
        $this->assertInstanceOf(PDODriver::class, DB::connection());
    }

    public function test_insert_and_select(): void
    {
        $id = DB::insert('users', ['name' => 'Manolo']);

        $this->assertSame(1, (int) $id);
        $this->assertSame('Manolo', DB::selectValue('SELECT name FROM users WHERE id = ?', [$id]));
    }

    public function test_transaction_commits(): void
    {
        DB::transaction(function (): void {
            DB::insert('users', ['name' => 'Pepe']);
        });

        $this->assertSame(1, (int) DB::selectValue('SELECT COUNT(*) FROM users'));
    }

    public function test_get_pdo_returns_pdo_instance(): void
    {
        $this->assertInstanceOf(\PDO::class, DB::getPdo());
    }
}
