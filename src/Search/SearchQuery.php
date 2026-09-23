<?php

namespace Witify\Support\Search;

use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;

/**
 * @template TBuilder of \Illuminate\Database\Eloquent\Builder
 */
class SearchQuery
{
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

        $fuzzySearch = '%' . $search . '%';

        return $this->query->where(function ($query) use (
            $items,
            $fuzzySearch,
            $search
        ) {
            foreach ($items as $column) {
                if ($column instanceof SearchConstraintInterface) {
                    $query->orWhere(function ($query) use ($column) {
                        $query = $column->handle($query);
                    });
                } elseif (is_callable($column) && ! is_string($column)) {
                    $query = $column($query, $search);
                } elseif ($column instanceof Expression) {
                    $query->orWhereRaw(
                        $column->getValue(DB::connection()->getQueryGrammar()) .
                            ' LIKE ?',
                        [$fuzzySearch]
                    );
                } elseif (is_string($column)) {
                    $column = $this->query->getGrammar()->wrap($column);
                    $query->orWhereRaw('LOWER(' . $column . ') LIKE ?', [
                        $fuzzySearch,
                    ]);
                } else {
                    throw new \InvalidArgumentException('Invalid search item');
                }
            }
        });
    }
}
