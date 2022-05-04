<?php

namespace Sashalenz\Searchable\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Searchable
{
    public function getSearchable():? array
    {
        return $this->searchable ?? [];
    }

    public function getSearchableRelations(): array
    {
        return $this->searchableRelations ?? [];
    }

    public static function searchQuery(): Builder
    {
        return self::query();
    }

    abstract public function getDisplayName(): string;
}
