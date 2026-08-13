<?php

declare(strict_types=1);

namespace Database;

use ArrayAccess;
use Database\Drivers\DatabaseDriver;
use Database\Exceptions\MassAssignmentException;
use Database\Model\ModelBuilder;
use Database\Model\Relations\BelongsTo;
use Database\Model\Relations\HasMany;
use JsonSerializable;

/**
 * Base Active-Record style ORM model.
 *
 * Hydrates rows from the query builder into model instances, tracks dirty
 * attributes, applies casts and timestamps, and provides lazy relationships.
 */
abstract class Model implements ArrayAccess, JsonSerializable
{
    /**
     * Table name. When empty it is derived from the class name:
     * snake_case(class basename) + "s" (override for irregular plurals).
     */
    protected string $table = '';

    protected string $primaryKey = 'id';

    /**
     * Whether the model currently exists in the database.
     */
    public bool $exists = false;

    /**
     * Whether created_at/updated_at are maintained automatically.
     */
    protected bool $timestamps = true;

    protected string $createdAt = 'created_at';

    protected string $updatedAt = 'updated_at';

    /**
     * Columns allowed for mass assignment. When non-empty it acts as a
     * whitelist; otherwise $guarded (or nothing except the primary key) applies.
     *
     * @var array<int, string>
     */
    protected array $fillable = [];

    /**
     * Columns never mass-assignable. `['*']` blocks everything.
     *
     * @var array<int, string>
     */
    protected array $guarded = [];

    /**
     * Attribute cast map: column => 'int'|'float'|'string'|'bool'|'array'|'json'.
     *
     * @var array<string, string>
     */
    protected array $casts = [];

    /**
     * Raw attribute values as stored in the database (arrays are kept
     * unserialized; they are JSON-encoded only at write time).
     *
     * @var array<string, mixed>
     */
    protected array $attributes = [];

    /**
     * Snapshot of attributes at last save/hydration, used for dirty tracking.
     *
     * @var array<string, mixed>
     */
    protected array $original = [];

    /**
     * Driver override for testing; falls back to DB::connection() when null.
     */
    private static ?DatabaseDriver $connection = null;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(array $attributes = [])
    {
        if ($attributes !== []) {
            $this->fill($attributes);
        }
    }

    public static function setConnection(?DatabaseDriver $connection): void
    {
        self::$connection = $connection;
    }

    public static function connection(): DatabaseDriver
    {
        return self::$connection ?? DB::connection();
    }

    public function getTable(): string
    {
        if ($this->table !== '') {
            return $this->table;
        }

        return snake_case(basename(str_replace('\\', '/', static::class))) . 's';
    }

    public function getKeyName(): string
    {
        return $this->primaryKey;
    }

    public function getKey(): mixed
    {
        return $this->getAttribute($this->primaryKey);
    }

    public function newQuery(): ModelBuilder
    {
        return new ModelBuilder(static::connection(), $this, $this->getTable());
    }

    /**
     * @return Model[]
     */
    public static function all(): array
    {
        return (new static())->newQuery()->get();
    }

    public static function create(array $attributes): static
    {
        $model = new static($attributes);
        $model->save();

        return $model;
    }

    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            $this->assertFillable($key);
            $this->setAttribute($key, $value);
        }
        return $this;
    }

    /**
     * Mass-assignable keys: the whitelist when $fillable is set, otherwise
     * everything not in $guarded. The primary key is never mass-assignable so
     * create()/update()/fill() can never hijack the identifier. Protection is
     * hard: to set a protected attribute, assign it directly (setAttribute() or
     * property assignment), not through a bulk fill.
     */
    public function isFillable(string $key): bool
    {
        if ($key === $this->primaryKey) {
            return false;
        }

        if ($this->fillable !== []) {
            return in_array($key, $this->fillable, true);
        }

        if ($this->guarded === ['*']) {
            return false;
        }

        return !in_array($key, $this->guarded, true);
    }

    public function save(): bool
    {
        if ($this->exists) {
            return $this->performUpdate();
        }

        return $this->performInsert();
    }

    public function update(array $attributes): bool
    {
        return $this->fill($attributes)->save();
    }

    public function delete(): bool
    {
        if (!$this->exists || $this->getKey() === null) {
            return false;
        }

        $affected = $this->newQuery()
            ->where($this->getKeyName(), $this->getKey())
            ->delete();

        $this->exists = false;

        return $affected > 0;
    }

    public function getAttribute(string $key): mixed
    {
        if (!array_key_exists($key, $this->attributes)) {
            return null;
        }

        return $this->castAttribute($key, $this->attributes[$key]);
    }

    public function setAttribute(string $key, mixed $value): static
    {
        $this->attributes[$key] = $value;

        return $this;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function setRawAttributes(array $attributes, bool $sync = false): static
    {
        $this->attributes = $attributes;

        if ($sync) {
            $this->syncOriginal();
        }

        return $this;
    }

    public function syncOriginal(): static
    {
        $this->original = $this->attributes;

        return $this;
    }

    public function getOriginal(?string $key = null, mixed $default = null): mixed
    {
        return $key === null ? $this->original : ($this->original[$key] ?? $default);
    }

    public function isDirty(?string $key = null): bool
    {
        if ($key === null) {
            return $this->getDirty() !== [];
        }

        return array_key_exists($key, $this->getDirty());
    }

    public function isClean(?string $key = null): bool
    {
        return !$this->isDirty($key);
    }

    public function getDirty(): array
    {
        $dirty = [];

        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || serialize($value) !== serialize($this->original[$key])) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    public function toArray(): array
    {
        $result = [];

        foreach ($this->attributes as $key => $value) {
            $result[$key] = $this->castAttribute($key, $value);
        }

        return $result;
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    public function hasMany(string $related, ?string $foreignKey = null, ?string $localKey = null): HasMany
    {
        $localKey ??= $this->getKeyName();
        $foreignKey ??= snake_case(basename(str_replace('\\', '/', static::class))) . '_' . $localKey;

        return new HasMany($this, $related, $foreignKey, $localKey);
    }

    public function belongsTo(string $related, ?string $foreignKey = null, ?string $ownerKey = null): BelongsTo
    {
        $instance = new $related();
        $ownerKey ??= $instance->getKeyName();
        $foreignKey ??= snake_case(basename(str_replace('\\', '/', $related))) . '_' . $ownerKey;

        return new BelongsTo($this, $related, $foreignKey, $ownerKey);
    }

    public function __get(string $key): mixed
    {
        return $this->getAttribute($key);
    }

    public function __set(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    public function __unset(string $key): void
    {
        unset($this->attributes[$key]);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->attributes[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->getAttribute($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->setAttribute($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->attributes[$offset]);
    }

    public function __call(string $method, array $arguments): mixed
    {
        return $this->newQuery()->{$method}(...$arguments);
    }

    public static function __callStatic(string $method, array $arguments): mixed
    {
        return (new static())->newQuery()->{$method}(...$arguments);
    }

    protected function performInsert(): bool
    {
        if ($this->usesTimestamps()) {
            $now = $this->freshTimestamp();
            $this->setAttribute($this->createdAt, $now);
            $this->setAttribute($this->updatedAt, $now);
        }

        $attributes = $this->serializeAttributes($this->attributes);

        $id = $this->newQuery()->insertGetId($attributes);
        $this->setAttribute($this->primaryKey, $id);
        $this->exists = true;
        $this->syncOriginal();

        return true;
    }

    protected function performUpdate(): bool
    {
        if ($this->usesTimestamps()) {
            $this->setAttribute($this->updatedAt, $this->freshTimestamp());
        }

        $dirty = $this->serializeAttributes($this->getDirty());

        if ($dirty === []) {
            return false;
        }

        $this->newQuery()
            ->where($this->getKeyName(), $this->getKey())
            ->update($dirty);

        $this->syncOriginal();

        return true;
    }

    protected function serializeAttributes(array $attributes): array
    {
        foreach ($attributes as $key => $value) {
            if (
                $value !== null
                && $this->shouldCastAttribute($key)
                && in_array($this->casts[$key], ['array', 'json'], true)
            ) {
                $attributes[$key] = json_encode($value);
            }
        }

        return $attributes;
    }

    protected function usesTimestamps(): bool
    {
        return $this->timestamps;
    }

    protected function freshTimestamp(): string
    {
        return date('Y-m-d H:i:s');
    }

    protected function shouldCastAttribute(string $key): bool
    {
        return array_key_exists($key, $this->casts);
    }

    protected function castAttribute(string $key, mixed $value): mixed
    {
        if ($value === null || !$this->shouldCastAttribute($key)) {
            return $value;
        }

        return match ($this->casts[$key]) {
            'int', 'integer' => (int) $value,
            'float', 'double' => (float) $value,
            'string' => (string) $value,
            'bool', 'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'array', 'json' => $this->fromJson($value),
            default => $value,
        };
    }

    protected function fromJson(mixed $value): mixed
    {
        if (is_array($value) || is_object($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true);

        return $decoded === null && json_last_error() !== JSON_ERROR_NONE ? null : $decoded;
    }

    protected function assertFillable(string $key): void
    {
        if (!$this->isFillable($key)) {
            throw new MassAssignmentException(sprintf(
                'Column [%s] is not fillable on %s. Assign it directly to bypass bulk-fill protection.',
                $key,
                static::class,
            ));
        }
    }
}
