<?php

declare(strict_types=1);

namespace tests\Database;

use Database\Drivers\PDODriver;
use Database\Exceptions\MassAssignmentException;
use Database\Exceptions\ModelNotFoundException;
use Database\Model;
use Database\Model\Relations\BelongsTo;
use Database\Model\Relations\HasMany;
use PHPUnit\Framework\TestCase;

class User extends Model
{
    protected string $table = 'users';

    protected array $casts = ['age' => 'int', 'is_admin' => 'bool', 'settings' => 'array'];

    protected array $fillable = ['name', 'email', 'age', 'is_admin', 'settings'];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'user_id', 'id');
    }
}

class Post extends Model
{
    protected string $table = 'posts';

    protected array $fillable = ['title'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}

class Locked extends Model
{
    protected string $table = 'users';

    protected array $guarded = ['*'];
}

class Raw extends Model
{
    protected string $table = 'users';
}

class UserProfile extends Model
{
}

class ModelTest extends TestCase
{
    private PDODriver $driver;

    protected function setUp(): void
    {
        $this->driver = new PDODriver();
        $this->driver->connect('sqlite', '', 0, ':memory:', '', '');
        Model::setConnection($this->driver);

        $this->driver->execute(
            'CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT,
                email TEXT,
                age INTEGER,
                is_admin INTEGER,
                settings TEXT,
                created_at TEXT,
                updated_at TEXT
            )',
        );

        $this->driver->execute(
            'CREATE TABLE posts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                title TEXT,
                created_at TEXT,
                updated_at TEXT
            )',
        );
    }

    protected function tearDown(): void
    {
        Model::setConnection(null);
        $this->driver->close();
    }

    public function test_constructor_fills_attributes(): void
    {
        $user = new User(['name' => 'Ana', 'age' => 25]);

        $this->assertSame('Ana', $user->name);
        $this->assertSame(25, $user->age);
    }

    public function test_table_name_is_derived_from_class_name(): void
    {
        $this->assertSame('users', (new User())->getTable());
        $this->assertSame('user_profiles', (new UserProfile())->getTable());
    }

    public function test_find_returns_hydrated_model(): void
    {
        $user = User::create(['name' => 'Ana', 'age' => 25]);

        $found = User::find($user->id);

        $this->assertInstanceOf(User::class, $found);
        $this->assertTrue($found->exists);
        $this->assertSame('Ana', $found->name);
        $this->assertSame(25, $found->age);
    }

    public function test_find_returns_null_when_missing(): void
    {
        $this->assertNull(User::find(999));
    }

    public function test_find_or_fail_throws_when_missing(): void
    {
        $this->expectException(ModelNotFoundException::class);

        User::findOrFail(999);
    }

    public function test_instance_find_or_fail(): void
    {
        $user = User::create(['name' => 'Ana']);

        $this->assertInstanceOf(User::class, (new User())->findOrFail($user->id));

        $this->expectException(ModelNotFoundException::class);

        (new User())->findOrFail(999);
    }

    public function test_first_or_fail_throws_when_empty(): void
    {
        $this->expectException(ModelNotFoundException::class);

        User::firstOrFail();
    }

    public function test_all_returns_models(): void
    {
        $this->seedUsers();

        $users = User::all();

        $this->assertCount(3, $users);
        $this->assertInstanceOf(User::class, $users[0]);
        $this->assertSame('Manolo', $users[0]->name);
    }

    public function test_where_returns_hydrated_models(): void
    {
        $this->seedUsers();

        $users = User::where('age', '>=', 26)->orderBy('id')->get();

        $this->assertSame(['Manolo', 'Pepe'], array_map(static fn (User $user): string => $user->name, $users));
    }

    public function test_static_magic_forwards_to_builder(): void
    {
        $this->seedUsers();

        $this->assertSame(3, User::count());
        $this->assertInstanceOf(User::class, User::first());

        $result = User::orderBy('id')->paginate(2, 1);
        $this->assertSame(2, $result['per_page']);
        $this->assertInstanceOf(User::class, $result['data'][0]);
    }

    public function test_instance_call_forwards_to_builder(): void
    {
        $this->seedUsers();

        $user = User::first();

        $this->assertSame(['Manolo', 'Pepe'], $user->where('age', '>', 25)->orderBy('age', 'desc')->pluck('name'));
    }

    public function test_create_sets_id_and_timestamps(): void
    {
        $user = User::create(['name' => 'Ana']);

        $this->assertTrue($user->exists);
        $this->assertNotNull($user->id);
        $this->assertNotEmpty($user->created_at);
        $this->assertSame($user->created_at, $user->updated_at);
        $this->assertSame(1, User::count());
    }

    public function test_save_inserts_and_assigns_id(): void
    {
        $user = new User(['name' => 'Ana', 'age' => 25]);

        $this->assertTrue($user->save());
        $this->assertTrue($user->exists);
        $this->assertNotNull($user->id);
        $this->assertSame(1, User::count());
    }

    public function test_update_instance_method(): void
    {
        $user = User::create(['name' => 'Ana']);

        $this->assertTrue($user->update(['name' => 'Ana2']));
        $this->assertSame('Ana2', User::find($user->id)->name);
    }

    public function test_save_persists_changed_attributes(): void
    {
        $user = User::create(['name' => 'Ana', 'age' => 25]);
        $user->age = 31;
        $user->save();

        $this->assertSame(31, User::find($user->id)->age);
    }

    public function test_save_updates_only_dirty_columns(): void
    {
        $user = User::create(['name' => 'Ana', 'email' => 'ana@test.com', 'age' => 25]);
        $user->age = 31;
        $user->save();

        $last = $this->driver->getLastQuery();

        $this->assertStringContainsString('`age` = ?', $last);
        $this->assertStringNotContainsString('`name` = ?', $last);
    }

    public function test_dirty_tracking(): void
    {
        $user = User::create(['name' => 'Ana', 'age' => 25]);
        $this->assertTrue($user->isClean());

        $user->age = 31;

        $this->assertTrue($user->isDirty('age'));
        $this->assertFalse($user->isDirty('name'));
        $this->assertSame(['age' => 31], $user->getDirty());

        $user->save();
        $this->assertTrue($user->isClean());
    }

    public function test_updated_at_refreshes_on_update_but_created_at_is_untouched(): void
    {
        $user = User::create(['name' => 'Ana']);
        $user->created_at = '2020-01-01 00:00:00';
        $user->updated_at = '2020-01-01 00:00:00';
        $user->save();

        $user->age = 31;
        $user->save();

        $fetched = User::find($user->id);
        $this->assertSame('2020-01-01 00:00:00', $fetched->created_at);
        $this->assertNotSame('2020-01-01 00:00:00', $fetched->updated_at);
    }

    public function test_delete_removes_row(): void
    {
        $user = User::create(['name' => 'Ana']);

        $this->assertTrue($user->delete());
        $this->assertNull(User::find($user->id));
        $this->assertFalse($user->delete());
    }

    public function test_delete_unsaved_model_returns_false(): void
    {
        $this->assertFalse((new User(['name' => 'Ana']))->delete());
    }

    public function test_fill_respects_fillable_whitelist(): void
    {
        $user = new User(['name' => 'Ana', 'email' => 'ana@test.com', 'age' => 25]);

        $this->assertSame('Ana', $user->name);
        $this->assertSame('ana@test.com', $user->email);
        $this->assertSame(25, $user->age);
    }

    public function test_mass_assignment_rejects_non_fillable_key(): void
    {
        $this->expectException(MassAssignmentException::class);

        new User(['created_at' => '2020-01-01 00:00:00']);
    }

    public function test_mass_assignment_rejects_primary_key(): void
    {
        $this->expectException(MassAssignmentException::class);

        new User(['id' => 5, 'name' => 'x']);
    }

    public function test_guarded_all_blocks_mass_assignment(): void
    {
        $this->expectException(MassAssignmentException::class);

        Locked::create(['name' => 'x']);
    }

    public function test_default_allows_all_except_primary_key(): void
    {
        $raw = new Raw(['name' => 'x', 'email' => 'x@test.com']);

        $this->assertSame('x', $raw->name);

        $this->expectException(MassAssignmentException::class);

        new Raw(['id' => 7]);
    }

    public function test_protected_attributes_can_be_set_directly(): void
    {
        $user = new User(['name' => 'Ana']);
        $user->setAttribute('created_at', '2020-01-01 00:00:00');
        $user->id = 42;

        $this->assertSame('2020-01-01 00:00:00', $user->created_at);
        $this->assertSame(42, $user->id);
    }

    public function test_casts_apply_on_read(): void
    {
        $user = User::create(['name' => 'Ana', 'age' => '30', 'is_admin' => 1, 'settings' => ['dark' => true]]);

        $fetched = User::find($user->id);

        $this->assertSame(30, $fetched->age);
        $this->assertTrue($fetched->is_admin);
        $this->assertSame(['dark' => true], $fetched->settings);
    }

    public function test_array_json_cast_roundtrips_through_database(): void
    {
        $user = User::create(['name' => 'Ana', 'settings' => ['a' => 1, 'b' => [2, 3]]]);

        $stored = $this->driver->selectValue('SELECT settings FROM users WHERE id = ?', [$user->id]);
        $this->assertSame('{"a":1,"b":[2,3]}', $stored);

        $this->assertSame(['a' => 1, 'b' => [2, 3]], User::find($user->id)->settings);
    }

    public function test_has_many_create_attaches_foreign_key(): void
    {
        $user = User::create(['name' => 'Ana']);

        $post = $user->posts()->create(['title' => 'Hello']);

        $this->assertSame($user->id, $post->user_id);
        $this->assertSame(['Hello'], array_map(static fn (Post $p): string => $p->title, $user->posts()->get()));
        $this->assertSame(1, $user->posts()->count());
        $this->assertInstanceOf(Post::class, $user->posts()->first());
    }

    public function test_has_many_save_attaches_foreign_key(): void
    {
        $user = User::create(['name' => 'Ana']);
        $post = new Post(['title' => 'Saved']);

        $user->posts()->save($post);

        $this->assertSame($user->id, $post->user_id);
        $this->assertSame(1, $user->posts()->count());
    }

    public function test_has_many_returns_only_related_rows(): void
    {
        $a = User::create(['name' => 'A']);
        $b = User::create(['name' => 'B']);
        $a->posts()->create(['title' => 'Post A']);
        $b->posts()->create(['title' => 'Post B']);

        $this->assertSame(['Post A'], array_map(static fn (Post $p): string => $p->title, $a->posts()->get()));
    }

    public function test_belongs_to_get_returns_parent(): void
    {
        $user = User::create(['name' => 'Ana']);
        $post = $user->posts()->create(['title' => 'Hello']);

        $parent = $post->user()->get();

        $this->assertInstanceOf(User::class, $parent);
        $this->assertEquals($user->id, $parent->id);
    }

    public function test_belongs_to_associate_sets_foreign_key(): void
    {
        $user = User::create(['name' => 'Ana']);
        $post = new Post(['title' => 'Hello']);

        $post->user()->associate($user);
        $this->assertSame($user->id, $post->user_id);

        $post->save();
        $this->assertEquals($user->id, Post::find($post->id)->user_id);
    }

    public function test_to_array_applies_casts(): void
    {
        $user = User::create(['name' => 'Ana', 'age' => '30', 'is_admin' => 1, 'settings' => ['dark' => true]]);

        $array = User::find($user->id)->toArray();

        $this->assertSame(30, $array['age']);
        $this->assertTrue($array['is_admin']);
        $this->assertSame(['dark' => true], $array['settings']);
    }

    public function test_json_serialize(): void
    {
        $user = User::create(['name' => 'Ana']);

        $this->assertStringContainsString('"name":"Ana"', json_encode($user));
    }

    public function test_array_access(): void
    {
        $user = new User(['name' => 'Ana']);

        $this->assertSame('Ana', $user['name']);

        $user['age'] = 25;
        $this->assertSame(25, $user['age']);
        $this->assertTrue(isset($user['age']));

        unset($user['age']);
        $this->assertNull($user['age']);
    }

    private function seedUsers(): void
    {
        User::create(['name' => 'Manolo', 'email' => 'manolo@test.com', 'age' => 30]);
        User::create(['name' => 'Ana', 'email' => 'ana@test.com', 'age' => 25]);
        User::create(['name' => 'Pepe', 'email' => 'pepe@test.com', 'age' => 26]);
    }
}
