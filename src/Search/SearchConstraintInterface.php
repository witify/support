<?php

namespace Witify\Support\Search;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

interface SearchConstraintInterface
{
    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function handle(Builder $query): Builder;
}
