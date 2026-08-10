<?php

declare(strict_types=1);

namespace tests\Database\Query;

use Database\DB;
use Database\Drivers\PDODriver;
use Database\Exceptions\QueryException;
use Database\Query\Builder;
use Database\Query\BuilderException;
use Database\Query\Expression;
use PHPUnit\Framework\TestCase;

class BuilderTest extends TestCase
{
    private PDODriver $driver;

    protected function setUp(): void
    {
        $this->driver = new PDODriver();
        $this->driver->connect('sqlite', '', 0, ':memory:', '', '');

        $this->driver->execute(
            'CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                age INTEGER,
                email TEXT
            )',
        );

        $this->driver->execute(
            'CREATE TABLE posts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                title TEXT NOT NULL
            )',
        );
    }

    protected function tearDown(): void
    {
        $this->driver->close();
    }

    public function test_get_returns_all_rows(): void
    {
        $this->seedUsers();

        $rows = $this->builder('users')->get();

        $this->assertCount(3, $rows);
        $this->assertSame('Manolo', $rows[0]['name']);
    }

    public function test_get_returns_selected_columns(): void
    {
        $this->seedUsers();

        $rows = $this->builder('users')->get(['name']);

        $this->assertSame([['name' => 'Manolo'], ['name' => 'Ana'], ['name' => 'Pepe']], $rows);
    }

    public function test_select_sets_columns(): void
    {
        $this->seedUsers();

        $rows = $this->builder('users')->select('id', 'name')->orderBy('id')->get();

        $this->assertSame(['id', 'name'], array_keys($rows[0]));
    }

    public function test_select_raw_injects_expression(): void
    {
        $this->seedUsers();

        $rows = $this->builder('users')->select('name', DB::raw('UPPER(name) AS upper_name'))->orderBy('id')->get();

        $this->assertSame('MANOLO', $rows[0]['upper_name']);
    }

    public function test_select_raw_bindings_are_kept_in_order(): void
    {
        $this->seedUsers();

        $builder = $this->builder('users')->select('name')->selectRaw('? AS suffix', ['!'])->where('age', '>', 10);

        $this->assertSame('SELECT `name`, ? AS suffix FROM `users` WHERE `age` > ?', $builder->toSql());
        $this->assertSame(['!', 10], $builder->getBindings());
    }

    public function test_distinct(): void
    {
        $this->driver->insert('users', ['name' => 'Manolo', 'age' => 30]);
        $this->driver->insert('users', ['name' => 'Manolo', 'age' => 25]);

        $rows = $this->builder('users')->select('name')->distinct()->get();

        $this->assertCount(1, $rows);
    }

    public function test_where_with_operator(): void
    {
        $this->seedUsers();

        $rows = $this->builder('users')->where('age', '>', 26)->get();

        $this->assertSame(['Manolo'], array_column($rows, 'name'));
    }

    public function test_where_shorthand_equals(): void
    {
        $this->seedUsers();

        $rows = $this->builder('users')->where('name', 'Ana')->get();

        $this->assertCount(1, $rows);
        $this->assertSame('Ana', $rows[0]['name']);
    }

    public function test_where_null_compiles_to_is_null(): void
    {
        $this->seedUsers();

        $builder = $this->builder('users')->where('email', null);

        $this->assertStringContainsString('`email` IS NULL', $builder->toSql());
        $this->assertSame([], $builder->getBindings());
    }

    public function test_or_where(): void
    {
        $this->seedUsers();

        $rows = $this->builder('users')->where('name', 'Manolo')->orWhere('name', 'Pepe')->orderBy('id')->get();

        $this->assertSame(['Manolo', 'Pepe'], array_column($rows, 'name'));
    }

    public function test_nested_where_group(): void
    {
        $this->seedUsers();

        $rows = $this->builder('users')
            ->where('age', '>=', 25)
            ->where(function (Builder $query): void {
                $query->where('name', 'Ana')->orWhere('name', 'Pepe');
            })
            ->orderBy('id')
            ->get();

        $this->assertSame(['Ana', 'Pepe'], array_column($rows, 'name'));
    }

    public function test_nested_where_group_produces_parenthesized_sql(): void
    {
        $builder = $this->builder('users')
            ->where('age', '>=', 25)
            ->where(function (Builder $query): void {
                $query->where('name', 'Ana')->orWhere('name', 'Pepe');
            });

        $this->assertSame(
            'SELECT * FROM `users` WHERE `age` >= ? AND (`name` = ? OR `name` = ?)',
            $builder->toSql(),
        );
        $this->assertSame([25, 'Ana', 'Pepe'], $builder->getBindings());
    }

    public function test_where_in(): void
    {
        $this->seedUsers();

        $rows = $this->builder('users')->whereIn('name', ['Manolo', 'Pepe'])->orderBy('id')->get();

        $this->assertSame(['Manolo', 'Pepe'], array_column($rows, 'name'));
    }

    public function test_where_not_in(): void
    {
        $this->seedUsers();

        $rows = $this->builder('users')->whereNotIn('name', ['Manolo'])->orderBy('id')->get();

        $this->assertSame(['Ana', 'Pepe'], array_column($rows, 'name'));
    }

    public function test_where_in_with_subquery(): void
    {
        $this->seedUsers();
        $this->seedPosts();

        $rows = $this->builder('users')
            ->whereIn('id', function (Builder $query): void {
                $query->select('user_id')->from('posts')->where('title', 'Post 2');
            })
            ->get();

        $this->assertSame([2], array_column($rows, 'id'));
    }

    public function test_where_null_and_not_null(): void
    {
        $this->seedUsers();

        $rows = $this->builder('users')->whereNotNull('email')->get();
        $this->assertCount(2, $rows);

        $rows = $this->builder('users')->whereNull('email')->get();
        $this->assertSame(['Pepe'], array_column($rows, 'name'));
    }

    public function test_where_between(): void
    {
        $this->seedUsers();

        $rows = $this->builder('users')->whereBetween('age', [25, 26])->orderBy('id')->get();

        $this->assertSame(['Ana', 'Pepe'], array_column($rows, 'name'));
    }

    public function test_where_raw(): void
    {
        $this->seedUsers();

        $rows = $this->builder('users')->whereRaw('age > ? AND name != ?', [20, 'Pepe'])->orderBy('id')->get();

        $this->assertSame(['Manolo', 'Ana'], array_column($rows, 'name'));
    }

    public function test_where_column(): void
    {
        $this->seedUsers();

        $rows = $this->builder('users')->whereColumn('name', 'email')->get();

        $this->assertSame([], $rows);
    }

    public function test_where_exists(): void
    {
        $this->seedUsers();
        $this->seedPosts();

        $rows = $this->builder('users')
            ->whereExists(function (Builder $query): void {
                $query->select('id')->from('posts')->whereColumn('posts.user_id', 'users.id');
            })
            ->orderBy('id')
            ->get();

        $this->assertSame([1, 2], array_column($rows, 'id'));
    }

    public function test_where_with_subquery_value(): void
    {
        $this->seedUsers();

        $rows = $this->builder('users')
            ->where('age', '=', function (Builder $query): void {
                $query->selectRaw('MAX(age)')->from('users');
            })
            ->get();

        $this->assertSame(['Manolo'], array_column($rows, 'name'));
    }

    public function test_order_by_direction(): void
    {
        $this->seedUsers();

        $rows = $this->builder('users')->orderBy('age', 'desc')->get();

        $this->assertSame(['Manolo', 'Pepe', 'Ana'], array_column($rows, 'name'));
    }

    public function test_order_by_invalid_direction_falls_back_to_asc(): void
    {
        $this->seedUsers();

        $rows = $this->builder('users')->orderBy('age', 'sideways')->get();

        $this->assertSame(['Ana', 'Pepe', 'Manolo'], array_column($rows, 'name'));
    }

    public function test_order_by_raw(): void
    {
        $this->seedUsers();

        $rows = $this->builder('users')->orderByRaw('age DESC, id ASC')->get();

        $this->assertSame(['Manolo', 'Pepe', 'Ana'], array_column($rows, 'name'));
    }

    public function test_latest_and_oldest(): void
    {
        $this->seedUsers();

        $rows = $this->builder('users')->latest('age')->get();
        $this->assertSame(['Manolo', 'Pepe', 'Ana'], array_column($rows, 'name'));

        $rows = $this->builder('users')->oldest('age')->get();
        $this->assertSame(['Ana', 'Pepe', 'Manolo'], array_column($rows, 'name'));
    }

    public function test_limit_and_offset(): void
    {
        $this->seedUsers();

        $rows = $this->builder('users')->orderBy('id')->limit(2)->offset(1)->get();

        $this->assertSame(['Ana', 'Pepe'], array_column($rows, 'name'));
    }

    public function test_take_and_skip_are_aliases(): void
    {
        $this->seedUsers();

        $rows = $this->builder('users')->orderBy('id')->take(1)->skip(1)->get();

        $this->assertSame(['Ana'], array_column($rows, 'name'));
    }

    public function test_group_by_and_having(): void
    {
        $this->driver->insert('users', ['name' => 'Manolo', 'age' => 30]);
        $this->driver->insert('users', ['name' => 'Ana', 'age' => 30]);
        $this->driver->insert('users', ['name' => 'Pepe', 'age' => 25]);

        $rows = $this->builder('users')
            ->select('age', DB::raw('COUNT(*) AS total'))
            ->groupBy('age')
            ->having(DB::raw('total'), '>', DB::raw('1'))
            ->get();

        $this->assertCount(1, $rows);
        $this->assertSame(30, $rows[0]['age']);
        $this->assertSame(2, $rows[0]['total']);
    }

    public function test_join(): void
    {
        $this->seedUsers();
        $this->seedPosts();

        $rows = $this->builder('users')
            ->join('posts', 'posts.user_id', '=', 'users.id')
            ->select('users.name', 'posts.title')
            ->orderBy('posts.id')
            ->get();

        $this->assertSame('Manolo', $rows[0]['name']);
        $this->assertSame('Post 2', $rows[1]['title']);
    }

    public function test_left_join_keeps_unmatched_rows(): void
    {
        $this->seedUsers();
        $this->seedPosts();

        $rows = $this->builder('users')
            ->leftJoin('posts', 'posts.user_id', '=', 'users.id')
            ->select('users.name', 'posts.title')
            ->orderBy('users.id')
            ->get();

        $this->assertCount(3, $rows);
        $this->assertNull($rows[2]['title']);
    }

    public function test_count(): void
    {
        $this->seedUsers();

        $this->assertSame(3, $this->builder('users')->count());
    }

    public function test_count_with_where(): void
    {
        $this->seedUsers();

        $this->assertSame(2, $this->builder('users')->where('age', '>=', 26)->count());
    }

    public function test_min_max_sum_avg(): void
    {
        $this->seedUsers();

        $this->assertSame(25, $this->builder('users')->min('age'));
        $this->assertSame(30, $this->builder('users')->max('age'));
        $this->assertSame(81, $this->builder('users')->sum('age'));
        $this->assertSame(27.0, $this->builder('users')->avg('age'));
    }

    public function test_exists_and_doesnt_exist(): void
    {
        $this->seedUsers();

        $this->assertTrue($this->builder('users')->where('name', 'Ana')->exists());
        $this->assertFalse($this->builder('users')->where('name', 'Nobody')->exists());
        $this->assertTrue($this->builder('users')->where('name', 'Nobody')->doesntExist());
    }

    public function test_first_returns_first_row(): void
    {
        $this->seedUsers();

        $row = $this->builder('users')->orderBy('age', 'desc')->first();

        $this->assertSame('Manolo', $row['name']);
    }

    public function test_first_returns_null_when_empty(): void
    {
        $this->assertNull($this->builder('users')->first());
    }

    public function test_value_returns_single_value(): void
    {
        $this->seedUsers();

        $this->assertSame('Manolo', $this->builder('users')->where('id', 1)->value('name'));
    }

    public function test_pluck_returns_column_values(): void
    {
        $this->seedUsers();

        $this->assertSame(['Manolo', 'Ana', 'Pepe'], $this->builder('users')->orderBy('id')->pluck('name'));
    }

    public function test_pluck_with_key(): void
    {
        $this->seedUsers();

        $this->assertSame([1 => 'Manolo', 2 => 'Ana', 3 => 'Pepe'], $this->builder('users')->pluck('name', 'id'));
    }

    public function test_insert_and_insert_get_id(): void
    {
        $id = $this->builder('users')->insertGetId(['name' => 'Luis', 'age' => 40, 'email' => 'luis@test.com']);

        $this->assertSame(1, (int) $id);
        $this->assertSame(1, $this->builder('users')->count());
    }

    public function test_insert_with_empty_data_throws(): void
    {
        $this->expectException(BuilderException::class);

        $this->builder('users')->insert([]);
    }

    public function test_insert_multiple_rows(): void
    {
        $this->builder('users')->insert([
            ['name' => 'A', 'age' => 1, 'email' => null],
            ['name' => 'B', 'age' => 2, 'email' => null],
        ]);

        $this->assertSame(2, $this->builder('users')->count());
        $this->assertSame(['A', 'B'], $this->builder('users')->orderBy('id')->pluck('name'));
    }

    public function test_update_with_where(): void
    {
        $this->seedUsers();

        $affected = $this->builder('users')->where('name', 'Ana')->update(['age' => 99]);

        $this->assertSame(1, $affected);
        $this->assertSame(99, $this->builder('users')->where('name', 'Ana')->value('age'));
    }

    public function test_update_with_empty_data_throws(): void
    {
        $this->expectException(BuilderException::class);

        $this->builder('users')->update([]);
    }

    public function test_delete_with_where(): void
    {
        $this->seedUsers();

        $affected = $this->builder('users')->where('name', 'Pepe')->delete();

        $this->assertSame(1, $affected);
        $this->assertSame(2, $this->builder('users')->count());
    }

    public function test_delete_without_where_throws(): void
    {
        $this->expectException(BuilderException::class);

        $this->builder('users')->delete();
    }

    public function test_truncate(): void
    {
        $this->seedUsers();

        $this->builder('users')->truncate();

        $this->assertSame(0, $this->builder('users')->count());
    }

    public function test_paginate_first_page(): void
    {
        $this->seedUsers();

        $result = $this->builder('users')->orderBy('id')->paginate(2, 1);

        $this->assertSame(['Manolo', 'Ana'], array_column($result['data'], 'name'));
        $this->assertSame(3, $result['total']);
        $this->assertSame(2, $result['per_page']);
        $this->assertSame(1, $result['current_page']);
        $this->assertSame(2, $result['last_page']);
        $this->assertSame(1, $result['from']);
        $this->assertSame(2, $result['to']);
    }

    public function test_paginate_second_page(): void
    {
        $this->seedUsers();

        $result = $this->builder('users')->orderBy('id')->paginate(2, 2);

        $this->assertSame(['Pepe'], array_column($result['data'], 'name'));
        $this->assertSame(2, $result['current_page']);
        $this->assertSame(3, $result['from']);
        $this->assertSame(3, $result['to']);
    }

    public function test_paginate_page_beyond_last_page_clamps(): void
    {
        $this->seedUsers();

        $result = $this->builder('users')->paginate(2, 99);

        $this->assertSame(2, $result['current_page']);
        $this->assertSame(['Pepe'], array_column($result['data'], 'name'));
    }

    public function test_paginate_empty_table(): void
    {
        $result = $this->builder('users')->paginate(2, 1);

        $this->assertSame([], $result['data']);
        $this->assertSame(0, $result['total']);
        $this->assertSame(1, $result['current_page']);
        $this->assertNull($result['from']);
        $this->assertNull($result['to']);
    }

    public function test_bindings_are_positional_and_ordered(): void
    {
        $builder = $this->builder('users')
            ->where('age', '>', 18)
            ->whereRaw('name LIKE ?', ['%a%'])
            ->having('total', '>', 1);

        $this->assertSame([18, '%a%', 1], $builder->getBindings());
    }

    public function test_invalid_table_throws_query_exception(): void
    {
        $this->expectException(QueryException::class);

        $this->builder('missing_table')->get();
    }

    public function test_no_table_throws_builder_exception(): void
    {
        $this->expectException(BuilderException::class);

        $this->builder(null)->get();
    }

    public function test_db_raw_returns_expression(): void
    {
        $expression = DB::raw('NOW()');

        $this->assertInstanceOf(Expression::class, $expression);
        $this->assertSame('NOW()', $expression->getValue());
    }

    public function test_table_accepts_raw_expression(): void
    {
        $this->seedUsers();

        $rows = $this->builder(null)->from(DB::raw('users'))->get();

        $this->assertCount(3, $rows);
    }

    public function test_for_page(): void
    {
        $this->seedUsers();

        $rows = $this->builder('users')->orderBy('id')->forPage(2, 2)->get();

        $this->assertSame(['Pepe'], array_column($rows, 'name'));
    }

    public function test_invalid_column_identifier_throws_builder_exception(): void
    {
        $this->expectException(BuilderException::class);

        $this->builder('users')->select('name; DROP TABLE users')->toSql();
    }

    public function test_invalid_identifier_in_where_column_throws(): void
    {
        $this->expectException(BuilderException::class);

        $this->builder('users')->where('age) OR 1=1 --', '=', 5)->toSql();
    }

    public function test_invalid_insert_column_throws_builder_exception(): void
    {
        $this->expectException(BuilderException::class);

        $this->builder('users')->insert(['name; DROP TABLE users' => 'x']);
    }

    public function test_unsupported_where_operator_throws(): void
    {
        $this->expectException(BuilderException::class);

        $this->builder('users')->where('age', '===', 5);
    }

    public function test_unsupported_where_column_operator_throws(): void
    {
        $this->expectException(BuilderException::class);

        $this->builder('users')->whereColumn('a', 'matches', 'b');
    }

    public function test_unsupported_having_operator_throws(): void
    {
        $this->expectException(BuilderException::class);

        $this->builder('users')->having('age', '>>>', 1);
    }

    public function test_uppercase_operator_is_normalized(): void
    {
        $this->seedUsers();

        $rows = $this->builder('users')->where('name', 'LIKE', '%a%')->orderBy('id')->get();

        $this->assertSame(['Manolo', 'Ana'], array_column($rows, 'name'));
    }

    public function test_update_without_where_throws(): void
    {
        $this->expectException(BuilderException::class);

        $this->builder('users')->update(['name' => 'X']);
    }

    public function test_paginate_ignores_get_superglobal(): void
    {
        $this->seedUsers();
        $_GET['page'] = 99;

        try {
            $result = $this->builder('users')->orderBy('id')->paginate(2);
        } finally {
            unset($_GET['page']);
        }

        $this->assertSame(1, $result['current_page']);
    }

    public function test_paginate_defaults_to_first_page(): void
    {
        $this->seedUsers();

        $result = $this->builder('users')->orderBy('id')->paginate(2);

        $this->assertSame(1, $result['current_page']);
        $this->assertSame(['Manolo', 'Ana'], array_column($result['data'], 'name'));
    }

    public function test_where_between_requires_two_values(): void
    {
        $this->expectException(BuilderException::class);

        $this->builder('users')->whereBetween('age', [1]);
    }

    private function builder(?string $table): Builder
    {
        return new Builder($this->driver, $table);
    }

    private function seedUsers(): void
    {
        $this->driver->insert('users', ['name' => 'Manolo', 'age' => 30, 'email' => 'manolo@test.com']);
        $this->driver->insert('users', ['name' => 'Ana', 'age' => 25, 'email' => 'ana@test.com']);
        $this->driver->insert('users', ['name' => 'Pepe', 'age' => 26, 'email' => null]);
    }

    private function seedPosts(): void
    {
        $this->driver->insert('posts', ['user_id' => 1, 'title' => 'Post 1']);
        $this->driver->insert('posts', ['user_id' => 2, 'title' => 'Post 2']);
    }
}
