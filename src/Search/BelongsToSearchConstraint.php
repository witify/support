<?php

namespace Witify\Support\Search;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;

class BelongsToSearchConstraint implements SearchConstraintInterface
{
    /**
     * @param  array<int, string|Expression<string>>  $columns
     */
    public function __construct(
        private string $relation,
        private array $columns
    ) {}

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function handle(Builder $query): Builder
    {
        return $query->whereHas($this->relation, function (Builder $query) {
            if (! method_exists($query, 'search')) {
                throw new \RuntimeException('The related model does not support searching.');
            }
            $query->search($this->columns);
        });
    }
}
