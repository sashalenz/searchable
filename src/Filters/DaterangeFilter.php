<?php

namespace Sashalenz\Searchable\Filters;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\Filters\Filter;

class DaterangeFilter implements Filter
{
    private string $property;

    public function __construct(string $property)
    {
        $this->property = $property;
    }

    public function __invoke(Builder $query, $value = null, $property = null): Builder
    {
        $range = Str::of($value)->explode(' ');
        $table = $query->getModel()->getTable();

        return $query
            ->when(
                count($range) === 3,
                fn (Builder $query) => $query->whereBetween(
                    $table . '.' . is_null($property) ? $this->property : $property,
                    [
                        Carbon::createFromFormat('Y-m-d', $range[0])->startOfDay(),
                        Carbon::createFromFormat('Y-m-d', $range[2])->endOfDay(),
                    ]
                )
            )
            ->when(
                count($range) === 1,
                fn (Builder $query) => $query->whereBetween(
                    $table . '.' . is_null($property) ? $this->property : $property,
                    [
                        Carbon::createFromFormat('Y-m-d', $range[0])->startOfDay(),
                        Carbon::createFromFormat('Y-m-d', $range[0])->endOfDay(),
                    ]
                )
            );
    }
}
