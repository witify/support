<?php

namespace Witify\Support\QueryBuilder;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Witify\Support\Search\SearchConstraintInterface;
use Witify\Support\Search\SearchQuery;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends Builder<TModel>
 */
class BaseQueryBuilder extends Builder
{
    /**
     * Scope a query to only exclude specific Columns.
     *
     * @return $this
     */
    public function exclude(mixed ...$columns): static
    {
        if ($columns !== []) {
            if (count($columns) !== count($columns, COUNT_RECURSIVE)) {
                $columns = iterator_to_array(
                    new \RecursiveIteratorIterator(
                        new \RecursiveArrayIterator($columns)
                    )
                );
            }

            $this->select(
                array_diff($this->getTableColumns(), $columns)
            );

            return $this;
        }

        return $this;
    }

    /**
     * Shows All the columns of the Corresponding Table of Model
     *
     * @return list<string>
     **/
    private function getTableColumns(): array
    {
        $table = $this->model->getTable();
        $key = 'table_column_list:' . $table;
        $ttl = 5; // 5 seconds

        return Cache::remember($key, $ttl, function () {
            return $this->model
                ->getConnection()
                ->getSchemaBuilder()
                ->getColumnListing($this->model->getTable());
        });
    }

    /**
     * Filter out models unaccessible to the current
     * authenticated user.
     *
     * @return $this
     */
    public function protect(): static
    {
        $user = Auth::user();

        // Block access

        if ($user == null) {
            throw new AuthenticationException;
        }

        if (! Gate::forUser($user)->allows('viewAny', $this->model)) {
            throw new AuthorizationException(
                (string) __('support::messages.unauthorized_action')
            );
        }

        return $this;
    }

    /**
     * Search from request
     *
     * @param  array<int, string|callable|SearchConstraintInterface|Expression<float|int|string>>|null  $items
     * @return $this
     */
    public function search(?array $items = []): static
    {
        $searchQuery = (new SearchQuery(request('search'), $this));

        return $searchQuery->handle($items);
    }

    /**
     * Transform a value to boolean
     */
    protected function toBoolean(mixed $value): ?bool
    {
        if (
            $value === 'true' ||
            $value === true ||
            $value === 1 ||
            $value === '1'
        ) {
            return true;
        }

        if (
            $value === 'false' ||
            $value === false ||
            $value === 0 ||
            $value === '0'
        ) {
            return false;
        }

        return null;
    }

    /**
     * Paginate the given query.
     *
     * @param  int|null|\Closure  $perPage
     * @param  array<int, string>|string  $columns
     * @param  string  $pageName
     * @param  int|null  $page
     * @param  \Closure|int|null  $total
     * @return LengthAwarePaginator<int, TModel>
     *
     * @throws \InvalidArgumentException
     */
    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null, $total = null)
    {
        $this->enforceDeterministicSorting();

        return parent::paginate($perPage, $columns, $pageName, $page, $total);
    }

    /**
     * Simple paginate the application's models and enforce deterministic sorting.
     *
     * @param  int|null  $perPage
     * @param  array<int, string>|string  $columns
     * @param  string  $pageName
     * @param  int|null  $page
     * @return Paginator<int, TModel>
     */
    public function simplePaginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $this->enforceDeterministicSorting();

        return parent::simplePaginate($perPage, $columns, $pageName, $page);
    }

    /**
     * Adds the primary key as a secondary sort order if not already present.
     * Force order by 'id' to avoid non-deterministic order behavior with pagination
     * Read more about this here : https://tighten.com/blog/a-cautionary-tale-of-nondeterministic-laravel-pagination/
     */
    protected function enforceDeterministicSorting(): void
    {
        $primaryKeyName = $this->getModel()->getQualifiedKeyName();
        $orders = $this->getQuery()->orders;

        $hasPrimaryKeyOrder = collect($orders)->contains(function ($order) use ($primaryKeyName) {
            return ($order['column'] ?? null) === $primaryKeyName;
        });

        if (! $hasPrimaryKeyOrder) {
            // Get the direction of the *last* existing order, defaulting to 'asc'
            $lastDirection = ! empty($orders) ? (end($orders)['direction'] ?? 'asc') : 'asc';

            // Add the primary key as a secondary tie-breaker
            $this->orderBy($primaryKeyName, $lastDirection);
        }
    }

    /**
     * Filter by date range
     *
     * @param  array<int, string>|null  $range
     * @return $this
     */
    public function dateBetween(string $column, ?array $range): static
    {
        if ($range === null) {
            return $this;
        }

        if (count($range) === 1) {
            $this->whereDate($column, $range[0]);

            return $this;
        }

        if (count($range) === 2) {
            $start = $range[0];
            $start = Carbon::parse($start)->startOfDay();

            $end = $range[1];
            $end = Carbon::parse($end)->endOfDay();

            $this->whereBetween($column, [$start, $end]);

            return $this;
        }

        return $this;
    }
}
