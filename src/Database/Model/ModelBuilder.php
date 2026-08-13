<?php

declare(strict_types=1);

namespace Database\Model;

use Database\Drivers\DatabaseDriver;
use Database\Exceptions\ModelNotFoundException;
use Database\Model;
use Database\Query\Builder;

/**
 * Query builder that hydrates every selected row into a Model instance.
 *
 * get() returns hydrated models and first() is redeclared with the narrower
 * ?Model return type; the inherited value(), pluck(), count() and paginate()
 * flow through them transparently because Model implements ArrayAccess.
 */
class ModelBuilder extends Builder
{
    public function __construct(
        DatabaseDriver $driver,
        private readonly Model $model,
        ?string $table = null,
    ) {
        parent::__construct($driver, $table);
    }

    public function get(array|string $columns = ['*']): array
    {
        return array_map(
            fn (array $row): Model => $this->hydrateModel($row),
            parent::get($columns),
        );
    }

    public function first(array|string $columns = ['*']): ?Model
    {
        $builder = clone $this;
        $builder->limit(1);

        $rows = $builder->get($columns);

        return $rows[0] ?? null;
    }

    public function find(mixed $id): ?Model
    {
        return $this->where($this->model->getKeyName(), $id)->first();
    }

    public function findOrFail(mixed $id): Model
    {
        $model = $this->find($id);

        if ($model === null) {
            throw new ModelNotFoundException(sprintf(
                '%s not found for key [%s].',
                $this->model::class,
                $id,
            ));
        }

        return $model;
    }

    public function firstOrFail(): Model
    {
        $model = $this->first();

        if ($model === null) {
            throw new ModelNotFoundException(sprintf('No %s record was found.', $this->model::class));
        }

        return $model;
    }

    /**
     * Builds a fresh model instance for the given database row.
     */
    public function hydrateModel(array $row): Model
    {
        $model = clone $this->model;
        $model->setRawAttributes($row, true);
        $model->exists = true;

        return $model;
    }
}
