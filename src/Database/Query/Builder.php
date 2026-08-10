<?php

declare(strict_types=1);

namespace Database\Query;

use Closure;
use Database\Drivers\DatabaseDriver;

/**
 * Fluent query builder for MySQL (and other drivers supported by
 * DatabaseDriver). Builds SQL with positional bindings and delegates
 * execution to the injected driver.
 */
class Builder
{
    /**
     * SQL comparison operators allowed in where()/having()/whereColumn() and
     * join(). Enforced so user-supplied operators can never be concatenated
     * into the generated query verbatim.
     */
    public const OPERATORS = [
        '=',
        '!=',
        '<>',
        '<',
        '<=',
        '>',
        '>=',
        'like',
        'not like',
        '<=>',
    ];

    private string|Expression|null $table = null;

    private array $columns = [];

    private bool $distinct = false;

    private array $joins = [];

    private array $wheres = [];

    private array $groups = [];

    private array $havings = [];

    private array $orders = [];

    private ?int $limit = null;

    private ?int $offset = null;

    private array $bindings = [
        'select' => [],
        'having' => [],
        'order' => [],
    ];

    public function __construct(
        private readonly DatabaseDriver $driver,
        ?string $table = null,
    ) {
        $this->table = $table;
    }

    public function table(string|Expression $table): static
    {
        $this->table = $table;

        return $this;
    }

    public function from(string|Expression $table): static
    {
        return $this->table($table);
    }

    public function select(...$columns): static
    {
        $this->columns = [];

        return $this->addSelect(...$columns);
    }

    public function addSelect(...$columns): static
    {
        foreach ($columns as $column) {
            if (is_array($column)) {
                $this->columns = array_merge($this->columns, $column);

                continue;
            }

            $this->columns[] = $column;
        }

        return $this;
    }

    public function selectRaw(string $sql, array $bindings = []): static
    {
        $this->columns[] = new Expression($sql);
        $this->bindings['select'] = array_merge($this->bindings['select'], $bindings);

        return $this;
    }

    public function distinct(): static
    {
        $this->distinct = true;

        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second, string $type = 'inner'): static
    {
        $operator = $this->validateOperator($operator);

        $this->joins[] = compact('table', 'first', 'operator', 'second', 'type');

        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): static
    {
        return $this->join($table, $first, $operator, $second, 'left');
    }

    public function rightJoin(string $table, string $first, string $operator, string $second): static
    {
        return $this->join($table, $first, $operator, $second, 'right');
    }

    public function where($column, $operator = null, $value = null, string $boolean = 'and'): static
    {
        if ($column instanceof Closure) {
            $query = new Builder($this->driver);
            $column($query);

            return $this->addNestedWhereQuery($query, $boolean);
        }

        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        if ($value instanceof Closure) {
            $query = new Builder($this->driver);
            $value($query);
            $value = $query;
        }

        $operator = $this->validateOperator($operator);

        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => $boolean,
        ];

        return $this;
    }

    public function orWhere($column, $operator = null, $value = null): static
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        return $this->where($column, $operator, $value, 'or');
    }

    public function whereNested(Closure $callback, string $boolean = 'and'): static
    {
        $query = new Builder($this->driver);
        $callback($query);

        return $this->addNestedWhereQuery($query, $boolean);
    }

    public function whereIn(string $column, array|Closure|Builder $values, string $boolean = 'and', bool $not = false): static
    {
        if ($values instanceof Closure) {
            $query = new Builder($this->driver);
            $values($query);
            $values = $query;
        }

        $this->wheres[] = [
            'type' => 'in',
            'column' => $column,
            'values' => $values,
            'boolean' => $boolean,
            'not' => $not,
        ];

        return $this;
    }

    public function orWhereIn(string $column, array|Closure|Builder $values): static
    {
        return $this->whereIn($column, $values, 'or');
    }

    public function whereNotIn(string $column, array|Closure|Builder $values, string $boolean = 'and'): static
    {
        return $this->whereIn($column, $values, $boolean, true);
    }

    public function orWhereNotIn(string $column, array|Closure|Builder $values): static
    {
        return $this->whereIn($column, $values, 'or', true);
    }

    public function whereNull(string $column, string $boolean = 'and', bool $not = false): static
    {
        $this->wheres[] = [
            'type' => 'null',
            'column' => $column,
            'boolean' => $boolean,
            'not' => $not,
        ];

        return $this;
    }

    public function orWhereNull(string $column): static
    {
        return $this->whereNull($column, 'or');
    }

    public function whereNotNull(string $column, string $boolean = 'and'): static
    {
        return $this->whereNull($column, $boolean, true);
    }

    public function orWhereNotNull(string $column): static
    {
        return $this->whereNull($column, 'or', true);
    }

    public function whereBetween(string $column, array $values, string $boolean = 'and', bool $not = false): static
    {
        // BETWEEN needs both boundaries; anything else would silently compile
        // to "BETWEEN NULL AND ?" and hide the caller's mistake.
        if (count($values) !== 2) {
            throw new BuilderException('whereBetween() expects exactly two boundary values.');
        }

        $this->wheres[] = [
            'type' => 'between',
            'column' => $column,
            'min' => $values[0],
            'max' => $values[1],
            'boolean' => $boolean,
            'not' => $not,
        ];

        return $this;
    }

    public function orWhereBetween(string $column, array $values): static
    {
        return $this->whereBetween($column, $values, 'or');
    }

    public function whereRaw(string $sql, array $bindings = [], string $boolean = 'and'): static
    {
        $this->wheres[] = [
            'type' => 'raw',
            'sql' => $sql,
            'bindings' => $bindings,
            'boolean' => $boolean,
        ];

        return $this;
    }

    public function orWhereRaw(string $sql, array $bindings = []): static
    {
        return $this->whereRaw($sql, $bindings, 'or');
    }

    public function whereColumn(string $first, string $operator, ?string $second = null, string $boolean = 'and'): static
    {
        if ($second === null) {
            $second = $operator;
            $operator = '=';
        }

        $operator = $this->validateOperator($operator);

        $this->wheres[] = [
            'type' => 'column',
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
            'boolean' => $boolean,
        ];

        return $this;
    }

    public function orWhereColumn(string $first, string $operator, ?string $second = null): static
    {
        return $this->whereColumn($first, $operator, $second, 'or');
    }

    public function whereExists(Closure|Builder $query, string $boolean = 'and', bool $not = false): static
    {
        if ($query instanceof Closure) {
            $nested = new Builder($this->driver);
            $query($nested);
            $query = $nested;
        }

        $this->wheres[] = [
            'type' => 'exists',
            'query' => $query,
            'boolean' => $boolean,
            'not' => $not,
        ];

        return $this;
    }

    public function whereNotExists(Closure|Builder $query, string $boolean = 'and'): static
    {
        return $this->whereExists($query, $boolean, true);
    }

    public function groupBy(...$groups): static
    {
        foreach ($groups as $group) {
            $this->groups = array_merge($this->groups, is_array($group) ? $group : [$group]);
        }

        return $this;
    }

    public function groupByRaw(string $sql): static
    {
        $this->groups[] = new Expression($sql);

        return $this;
    }

    public function having(string|Expression $column, $operator = null, $value = null, string $boolean = 'and'): static
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $operator = $this->validateOperator($operator);

        $this->havings[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => $boolean,
        ];

        if (!($value instanceof Expression)) {
            $this->bindings['having'][] = $value;
        }

        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): static
    {
        $direction = strtolower($direction);

        if (!in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'asc';
        }

        $this->orders[] = [
            'type' => 'basic',
            'column' => $column,
            'direction' => $direction,
        ];

        return $this;
    }

    public function orderByRaw(string $sql, array $bindings = []): static
    {
        $this->orders[] = [
            'type' => 'raw',
            'sql' => $sql,
        ];

        $this->bindings['order'] = array_merge($this->bindings['order'], $bindings);

        return $this;
    }

    public function latest(string $column = 'created_at'): static
    {
        return $this->orderBy($column, 'desc');
    }

    public function oldest(string $column = 'created_at'): static
    {
        return $this->orderBy($column, 'asc');
    }

    public function reorder(?string $column = null, string $direction = 'asc'): static
    {
        $this->orders = [];

        return $column === null ? $this : $this->orderBy($column, $direction);
    }

    public function limit(int $value): static
    {
        $this->limit = $value;

        return $this;
    }

    public function take(int $value): static
    {
        return $this->limit($value);
    }

    public function offset(int $value): static
    {
        $this->offset = $value;

        return $this;
    }

    public function skip(int $value): static
    {
        return $this->offset($value);
    }

    public function forPage(int $page, int $perPage = 15): static
    {
        return $this->offset(($page - 1) * $perPage)->limit($perPage);
    }

    public function get(array|string $columns = ['*']): array
    {
        $builder = clone $this;

        if (is_string($columns)) {
            $columns = [$columns];
        }

        if ($columns !== ['*']) {
            $builder->columns = array_values(array_unique(array_merge($builder->columns, $columns)));
        }

        return $this->driver->select($builder->compileSelect(), $builder->mergeBindings());
    }

    public function first(array|string $columns = ['*']): ?array
    {
        $builder = clone $this;
        $builder->limit(1);

        $rows = $builder->get($columns);

        return $rows[0] ?? null;
    }

    public function value(string $column): mixed
    {
        $row = $this->first();

        return $row[$column] ?? null;
    }

    public function pluck(string $column, ?string $key = null): array
    {
        $result = [];

        foreach ($this->get() as $row) {
            $value = $row[$column] ?? null;

            if ($key !== null) {
                $result[$row[$key] ?? null] = $value;
            } else {
                $result[] = $value;
            }
        }

        return $result;
    }

    public function count(string $column = '*'): int
    {
        return (int) $this->aggregate('count', $column);
    }

    public function min(string $column): mixed
    {
        return $this->aggregate('min', $column);
    }

    public function max(string $column): mixed
    {
        return $this->aggregate('max', $column);
    }

    public function sum(string $column): mixed
    {
        return $this->aggregate('sum', $column);
    }

    public function avg(string $column): mixed
    {
        return $this->aggregate('avg', $column);
    }

    public function exists(): bool
    {
        $sql = $this->compileSelect();

        return (bool) $this->driver->selectValue('SELECT EXISTS(' . $sql . ')', $this->mergeBindings());
    }

    public function doesntExist(): bool
    {
        return !$this->exists();
    }

    public function insert(array $data): int
    {
        $this->validateTable();

        if ($data === []) {
            throw new BuilderException('Cannot insert an empty data set.');
        }

        if (array_is_list($data) && is_array($data[0] ?? null)) {
            return $this->insertMany($data);
        }

        $sql = 'INSERT INTO ' . $this->compileFrom()
            . ' (' . $this->wrapColumns(array_keys($data)) . ')'
            . ' VALUES (' . implode(', ', array_fill(0, count($data), '?')) . ')';

        return $this->driver->execute($sql, array_values($data));
    }

    public function insertGetId(array $data, ?string $sequence = null): int|string
    {
        $this->insert($data);

        return $this->driver->lastInsertId($sequence);
    }

    public function update(array $data): int
    {
        $this->validateTable();

        if ($data === []) {
            throw new BuilderException('Cannot update an empty data set.');
        }

        // Safety: like delete(), an update must carry an explicit where clause
        // so a missing condition can never wipe the whole table.
        if ($this->wheres === []) {
            throw new BuilderException('A query builder for update() is missing the required where clause.');
        }

        $set = implode(', ', array_map(
            fn (string $column): string => $this->wrapSegment($column) . ' = ?',
            array_keys($data),
        ));

        $sql = 'UPDATE ' . $this->compileFrom() . ' SET ' . $set . ' ' . $this->compileWheres();
        $bindings = array_merge(array_values($data), $this->collectWhereBindings());

        return $this->driver->execute($sql, $bindings);
    }

    public function delete(): int
    {
        $this->validateTable();

        if ($this->wheres === []) {
            throw new BuilderException('A query builder for delete() is missing the required where clause.');
        }

        $sql = 'DELETE FROM ' . $this->compileFrom() . ' ' . $this->compileWheres();

        return $this->driver->execute($sql, $this->collectWhereBindings());
    }

    public function truncate(): void
    {
        $this->validateTable();

        $protocol = $this->driver->getConfig()['protocol'] ?? '';
        $sql = $protocol === 'sqlite'
            ? 'DELETE FROM ' . $this->compileFrom()
            : 'TRUNCATE TABLE ' . $this->compileFrom();

        $this->driver->execute($sql);
    }

    public function paginate(int $perPage = 15, int $page = 1): array
    {
        $this->validateTable();

        // The page must be supplied by the caller (typically resolved from
        // the request outside this class) so the database layer stays free of
        // HTTP superglobal coupling.
        $page = max(1, $page);
        $total = $this->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min($page, $lastPage);

        $offset = ($page - 1) * $perPage;
        $items = (clone $this)->limit($perPage)->offset($offset)->get();

        return [
            'data' => $items,
            'total' => (int) $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $lastPage,
            'from' => $items === [] ? null : $offset + 1,
            'to' => $items === [] ? null : $offset + count($items),
        ];
    }

    public function toSql(): string
    {
        return $this->compileSelect();
    }

    public function getBindings(): array
    {
        return $this->mergeBindings();
    }

    private function addNestedWhereQuery(Builder $query, string $boolean): static
    {
        $this->wheres[] = [
            'type' => 'nested',
            'query' => $query,
            'boolean' => $boolean,
        ];

        return $this;
    }

    private function aggregate(string $function, string $column): mixed
    {
        $builder = clone $this;
        $builder->columns = [];

        $sql = $builder->compileAggregate($function . '(' . $this->wrapColumn($column) . ')');

        return $this->driver->selectValue($sql, $builder->mergeBindings());
    }

    private function insertMany(array $rows): int
    {
        $columns = array_keys($rows[0]);
        $placeholder = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        $values = [];

        $rowPlaceholders = [];

        foreach ($rows as $row) {
            $rowPlaceholders[] = $placeholder;

            foreach ($columns as $column) {
                $values[] = $row[$column] ?? null;
            }
        }

        $sql = 'INSERT INTO ' . $this->compileFrom()
            . ' (' . $this->wrapColumns($columns) . ')'
            . ' VALUES ' . implode(', ', $rowPlaceholders);

        return $this->driver->execute($sql, $values);
    }

    private function compileSelect(): string
    {
        $this->validateTable();

        $segments = [
            'SELECT ' . $this->compileColumns(),
            'FROM ' . $this->compileFrom(),
            $this->compileJoins(),
            $this->compileWheres(),
            $this->compileGroups(),
            $this->compileHavings(),
            $this->compileOrders(),
            $this->compileLimit(),
            $this->compileOffset(),
        ];

        return implode(' ', array_filter($segments));
    }

    private function compileAggregate(string $aggregate): string
    {
        $this->validateTable();

        $segments = [
            'SELECT ' . $aggregate,
            'FROM ' . $this->compileFrom(),
            $this->compileJoins(),
            $this->compileWheres(),
            $this->compileGroups(),
            $this->compileHavings(),
        ];

        return implode(' ', array_filter($segments));
    }

    private function compileColumns(): string
    {
        $prefix = $this->distinct ? 'DISTINCT ' : '';

        if ($this->columns === []) {
            return $prefix . '*';
        }

        $columns = array_map(
            fn ($column): string => $column instanceof Expression ? $column->getValue() : $this->wrapColumn((string) $column),
            $this->columns,
        );

        return $prefix . implode(', ', $columns);
    }

    private function compileFrom(): string
    {
        if ($this->table instanceof Expression) {
            return $this->table->getValue();
        }

        return $this->wrapColumn((string) $this->table);
    }

    private function compileJoins(): string
    {
        $segments = [];

        foreach ($this->joins as $join) {
            $table = $join['table'] instanceof Expression ? $join['table']->getValue() : $this->wrapColumn($join['table']);
            $segments[] = strtoupper($join['type']) . ' JOIN ' . $table
                . ' ON ' . $this->wrapValue($join['first'])
                . ' ' . $join['operator']
                . ' ' . $this->wrapValue($join['second']);
        }

        return implode(' ', $segments);
    }

    private function compileWheres(): string
    {
        if ($this->wheres === []) {
            return '';
        }

        return 'WHERE ' . $this->compileConditions();
    }

    private function compileConditions(): string
    {
        $segments = [];

        foreach ($this->wheres as $index => $where) {
            $sql = $this->{'compileWhere' . ucfirst($where['type'])}($where);
            $segments[] = ($index === 0 ? '' : strtoupper($where['boolean']) . ' ') . $sql;
        }

        return implode(' ', $segments);
    }

    private function compileWhereBasic(array $where): string
    {
        $column = $this->wrapValue($where['column']);
        $operator = $where['operator'];
        $value = $where['value'];

        if ($value instanceof Builder) {
            return $column . ' ' . $operator . ' (' . $value->compileSelect() . ')';
        }

        if ($value instanceof Expression) {
            return $column . ' ' . $operator . ' ' . $value->getValue();
        }

        if ($value === null) {
            if ($operator === '=') {
                return $column . ' IS NULL';
            }

            if (in_array($operator, ['!=', '<>'], true)) {
                return $column . ' IS NOT NULL';
            }

            return $column . ' ' . $operator . ' NULL';
        }

        return $column . ' ' . $operator . ' ?';
    }

    private function compileWhereNested(array $where): string
    {
        $sql = $where['query']->compileConditions();

        return $sql === '' ? '1 = 1' : '(' . $sql . ')';
    }

    private function compileWhereIn(array $where): string
    {
        $column = $this->wrapValue($where['column']);
        $operator = $where['not'] ? 'NOT IN' : 'IN';
        $values = $where['values'];

        if ($values instanceof Builder) {
            return $column . ' ' . $operator . ' (' . $values->compileSelect() . ')';
        }

        if ($values === []) {
            return $where['not'] ? '1 = 1' : '0 = 1';
        }

        $placeholders = array_map(
            fn ($value): string => $value instanceof Expression ? $value->getValue() : '?',
            $values,
        );

        return $column . ' ' . $operator . ' (' . implode(', ', $placeholders) . ')';
    }

    private function compileWhereNull(array $where): string
    {
        return $this->wrapValue($where['column']) . ($where['not'] ? ' IS NOT NULL' : ' IS NULL');
    }

    private function compileWhereBetween(array $where): string
    {
        $column = $this->wrapValue($where['column']);
        $operator = $where['not'] ? 'NOT BETWEEN' : 'BETWEEN';

        return $column . ' ' . $operator . ' ? AND ?';
    }

    private function compileWhereRaw(array $where): string
    {
        return $where['sql'];
    }

    private function compileWhereColumn(array $where): string
    {
        return $this->wrapValue($where['first'])
            . ' ' . $where['operator']
            . ' ' . $this->wrapValue($where['second']);
    }

    private function compileWhereExists(array $where): string
    {
        $prefix = $where['not'] ? 'NOT EXISTS' : 'EXISTS';

        return $prefix . ' (' . $where['query']->compileSelect() . ')';
    }

    private function compileGroups(): string
    {
        if ($this->groups === []) {
            return '';
        }

        $groups = array_map(
            fn ($group): string => $group instanceof Expression ? $group->getValue() : $this->wrapColumn((string) $group),
            $this->groups,
        );

        return 'GROUP BY ' . implode(', ', $groups);
    }

    private function compileHavings(): string
    {
        if ($this->havings === []) {
            return '';
        }

        $segments = [];

        foreach ($this->havings as $index => $having) {
            $value = $having['value'];
            $sql = $this->wrapValue($having['column']) . ' ' . $having['operator'] . ' ';

            if ($value instanceof Expression) {
                $sql .= $value->getValue();
            } else {
                $sql .= '?';
            }

            $segments[] = ($index === 0 ? '' : strtoupper($having['boolean']) . ' ') . $sql;
        }

        return 'HAVING ' . implode(' ', $segments);
    }

    private function compileOrders(): string
    {
        if ($this->orders === []) {
            return '';
        }

        $segments = [];

        foreach ($this->orders as $order) {
            if ($order['type'] === 'raw') {
                $segments[] = $order['sql'];

                continue;
            }

            $segments[] = $this->wrapColumn($order['column']) . ' ' . $order['direction'];
        }

        return 'ORDER BY ' . implode(', ', $segments);
    }

    private function compileLimit(): string
    {
        return $this->limit === null ? '' : 'LIMIT ' . $this->limit;
    }

    private function compileOffset(): string
    {
        return $this->offset === null ? '' : 'OFFSET ' . $this->offset;
    }

    private function mergeBindings(): array
    {
        return array_merge(
            $this->bindings['select'],
            $this->collectWhereBindings(),
            $this->bindings['having'],
            $this->bindings['order'],
        );
    }

    private function collectWhereBindings(): array
    {
        $bindings = [];

        foreach ($this->wheres as $where) {
            switch ($where['type']) {
                case 'basic':
                    if ($where['value'] instanceof Builder) {
                        $bindings = array_merge($bindings, $where['value']->mergeBindings());
                    } elseif (!($where['value'] instanceof Expression) && $where['value'] !== null) {
                        $bindings[] = $where['value'];
                    }

                    break;

                case 'nested':
                    $bindings = array_merge($bindings, $where['query']->collectWhereBindings());

                    break;

                case 'in':
                    if (is_array($where['values'])) {
                        foreach ($where['values'] as $value) {
                            if (!($value instanceof Expression)) {
                                $bindings[] = $value;
                            }
                        }
                    } elseif ($where['values'] instanceof Builder) {
                        $bindings = array_merge($bindings, $where['values']->mergeBindings());
                    }

                    break;

                case 'between':
                    $bindings[] = $where['min'];
                    $bindings[] = $where['max'];

                    break;

                case 'raw':
                    $bindings = array_merge($bindings, $where['bindings']);

                    break;

                case 'exists':
                    $bindings = array_merge($bindings, $where['query']->mergeBindings());

                    break;
            }
        }

        return $bindings;
    }

    private function wrapColumns(array $columns): string
    {
        // Same identifier validation as wrapSegment(): column names supplied
        // via insert/update data must be safe identifiers, never raw SQL.
        return implode(', ', array_map(fn (string $column): string => $this->wrapSegment($column), $columns));
    }

    private function wrapValue(mixed $value): string
    {
        if ($value instanceof Expression) {
            return $value->getValue();
        }

        return $this->wrapColumn((string) $value);
    }

    private function wrapColumn(string $column): string
    {
        $column = trim($column);

        if (preg_match('/^(.+?)\s+as\s+(.+)$/i', $column, $matches)) {
            return $this->wrapSegment($matches[1]) . ' AS ' . $this->wrapSegment($matches[2]);
        }

        if (str_contains($column, '.')) {
            return implode('.', array_map(fn (string $segment): string => $this->wrapSegment($segment), explode('.', $column)));
        }

        return $this->wrapSegment($column);
    }

    private function wrapSegment(string $segment): string
    {
        $segment = trim($segment);

        if ($segment === '*') {
            return '*';
        }

        // Security: never interpolate non-identifier text into SQL. Returning
        // the segment verbatim would let an attacker smuggle raw SQL (e.g. a
        // column like "name FROM users"). Raw expressions must be passed
        // explicitly as Expression, never as a plain string.
        if (!preg_match('/^[A-Za-z0-9_$]+$/', $segment)) {
            throw new BuilderException(sprintf(
                'Invalid SQL identifier [%s]. Use Expression for raw SQL instead of a plain string.',
                $segment,
            ));
        }

        return '`' . str_replace('`', '``', $segment) . '`';
    }

    private function validateOperator(mixed $operator): string
    {
        $operator = strtolower(trim((string) $operator));

        // Security: reject any operator outside the whitelist instead of
        // appending it to the compiled SQL, where it could alter the query.
        if (!in_array($operator, self::OPERATORS, true)) {
            throw new BuilderException("Unsupported SQL operator [{$operator}].");
        }

        return $operator;
    }

    private function validateTable(): void
    {
        if ($this->table === null) {
            throw new BuilderException('No table has been set for this query builder.');
        }
    }
}
