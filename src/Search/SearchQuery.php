<?php

namespace Witify\Support\Search;

use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;

/**
 * @template TBuilder of \Illuminate\Database\Eloquent\Builder
 */
class SearchQuery
{
    private const LIKE_ESCAPE = '\\';

    /**
     * @var TBuilder
     */
    private $query;

    private ?string $keywords;

    /**
     * @param  TBuilder  $query
     */
    public function __construct(?string $keywords, $query)
    {
        $this->query = $query;
        $this->keywords = $keywords;
    }

    /**
     * Search the given columns for the given keywords.
     *
     * @param  array<string|callable|SearchConstraintInterface|Expression<string>>  $items
     * @return TBuilder
     */
    public function handle(?array $items = [])
    {
        if ($this->keywords === null) {
            return $this->query;
        }

        $search = strtolower($this->keywords);

        if (empty(trim($search))) {
            return $this->query;
        }

        if (count($items) == 0) {
            return $this->query;
        }

        // A keyword is a literal: its own `%` and `_` must not become wildcards.
        // The escape character is bound, since SQLite has no default one.
        $fuzzySearch = '%' . addcslashes($search, '%_\\') . '%';
        $bindings = [$fuzzySearch, self::LIKE_ESCAPE];

        return $this->query->where(function ($query) use (
            $items,
            $bindings,
            $search
        ) {
            foreach ($items as $column) {
                if ($column instanceof SearchConstraintInterface) {
                    $query->orWhere(function ($query) use ($column) {
                        $column->handle($query);
                    });
                } elseif (is_callable($column) && ! is_string($column)) {
                    $column($query, $search);
                } elseif ($column instanceof Expression) {
                    $query->orWhereRaw(
                        $column->getValue(DB::connection()->getQueryGrammar()) . ' LIKE ? ESCAPE ?',
                        $bindings
                    );
                } elseif (is_string($column)) {
                    // The collation already ignores case; LOWER() would only stop
                    // the database from using an index on the column.
                    $column = $this->query->getGrammar()->wrap($column);
                    $query->orWhereRaw($column . ' LIKE ? ESCAPE ?', $bindings);
                } else {
                    throw new \InvalidArgumentException('Invalid search item');
                }
            }
        });
    }
}
