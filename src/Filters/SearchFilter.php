<?php

namespace Sashalenz\Searchable\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class SearchFilter
{
    public function __construct(
        private readonly ?string $value,
        private readonly bool $strict = false
    ) { }

    public function __invoke(Builder $query): Builder
    {
        $model = $query->getModel();
        $table = $model->getTable();
        $keyName = $model->getKeyName();

        return $query->when(
            $this->value,
            fn (Builder $query) => $query
                ->when(
                    method_exists($model, 'getSearchable') && $model->getSearchable()->count(),
                    fn (Builder $query) => $query->where(
                        fn (Builder $query) => $model->getSearchable()
                            ->map(fn ($field) => $table . '.' . $field)
                            ->each(
                                fn ($field) => $query->orWhere(
                                    fn (Builder $query) => $query->when(
                                        $this->strict,
                                        fn (Builder $query) => $query->where($field, 'LIKE', $this->value),
                                        fn (Builder $query) => Str::of($this->value)
                                            ->explode(' ')
                                            ->map(fn ($word) => Str::of($word)
                                                ->lower()
                                                ->prepend('"%')
                                                ->append('%"')
                                                ->toString()
                                            )
                                            ->each(fn ($word) => $query->whereRaw('LOWER('.$field.') LIKE ' . $word))
                                    )
                                )
                            )
                    )
                )
                ->when(
                    method_exists($model, 'getSearchableRelations') && $model->getSearchableRelations()->count(),
                    fn (Builder $query) => $query->orWhere(
                        fn (Builder $query) => $model->getSearchableRelations()
                            ->filter(fn ($relation) => $model->isRelation($relation))
                            ->each(
                                fn ($relation) => $query->orWhereHas(
                                    $relation,
                                    fn (Builder $query) => $query->tap(new self($this->value, $this->strict))
                                )
                            )
                    )
                )
                ->when(
                    $keyName,
                    fn (Builder $query) => $query->orWhere(
                        fn (Builder $query) => Str::of($this->value)
                            ->explode(',')
                            ->filter(fn ($value) => is_numeric($value))
                            ->each(fn ($value) => $query->orWhere($table . '.' . $keyName, (int)$value))
                    )
                )
        );
    }
}
