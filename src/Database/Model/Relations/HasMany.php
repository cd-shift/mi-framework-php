<?php

declare(strict_types=1);

namespace Database\Model\Relations;

use Database\Model;

/**
 * One-to-many relationship. The related models hold a foreign key that points
 * to the local key of the parent model (e.g. posts.user_id -> users.id).
 *
 * Lazy loading only: each access issues its own query.
 */
class HasMany
{
    public function __construct(
        private readonly Model $parent,
        private readonly string $relatedClass,
        private readonly string $foreignKey,
        private readonly string $localKey,
    ) {
    }

    /**
     * @return Model[]
     */
    public function get(): array
    {
        $related = $this->relatedClass;

        return $related::where($this->foreignKey, $this->parent->getAttribute($this->localKey))->get();
    }

    public function first(): ?Model
    {
        $related = $this->relatedClass;

        return $related::where($this->foreignKey, $this->parent->getAttribute($this->localKey))->first();
    }

    public function count(): int
    {
        $related = $this->relatedClass;

        return $related::where($this->foreignKey, $this->parent->getAttribute($this->localKey))->count();
    }

    /**
     * Attaches the foreign key to the child and persists it.
     */
    public function save(Model $child): Model
    {
        $child->setAttribute($this->foreignKey, $this->parent->getAttribute($this->localKey));
        $child->save();

        return $child;
    }

    /**
     * Creates and persists a new related model with the foreign key attached.
     */
    public function create(array $attributes): Model
    {
        $child = new $this->relatedClass($attributes);

        return $this->save($child);
    }
}
