<?php

declare(strict_types=1);

namespace Database\Model\Relations;

use Database\Model;

/**
 * Many-to-one relationship. The parent model holds a foreign key that points
 * to the owner key of the related model (e.g. posts.user_id -> users.id).
 *
 * Lazy loading only: each access issues its own query.
 */
class BelongsTo
{
    public function __construct(
        private readonly Model $parent,
        private readonly string $relatedClass,
        private readonly string $foreignKey,
        private readonly string $ownerKey,
    ) {
    }

    public function get(): ?Model
    {
        $related = $this->relatedClass;

        return $related::where($this->ownerKey, $this->parent->getAttribute($this->foreignKey))->first();
    }

    /**
     * Sets the foreign key on the parent without persisting it. The caller
     * decides when to save() the parent.
     */
    public function associate(Model $model): Model
    {
        $this->parent->setAttribute($this->foreignKey, $model->getAttribute($this->ownerKey));

        return $this->parent;
    }
}
