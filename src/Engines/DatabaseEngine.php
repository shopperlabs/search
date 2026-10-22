<?php

declare(strict_types=1);

namespace Shopper\Search\Engines;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Shopper\Search\Data\SearchHit;
use Shopper\Search\Data\SearchResults;

final class DatabaseEngine extends AbstractEngine
{
    public function get(): SearchResults
    {
        $query = $this->modelType::query();

        foreach (['publish', 'published', 'enabled'] as $scope) {
            if ($query->hasNamedScope($scope)) {
                $query->{$scope}();

                break;
            }
        }

        if ($this->channel !== null && $query->hasNamedScope('channel')) {
            $query->scopes(['channel' => [[$this->channel->getKey()]]]);
        }

        if ($this->query) {
            $this->applySearchTerm($query);
        }

        if ($this->sort && $this->sortByIsValid()) {
            [$field, $direction] = explode(':', $this->sort);

            $query->orderBy($field, $direction);
        }

        $paginator = $query
            ->orderBy($query->getModel()->getQualifiedKeyName())
            ->paginate($this->perPage, page: $this->page);

        return new SearchResults(
            query: $this->query,
            count: $paginator->total(),
            page: $paginator->currentPage(),
            perPage: $paginator->perPage(),
            totalPages: $paginator->lastPage(),
            hits: collect($paginator->items())
                ->map(fn (Model $model): SearchHit => new SearchHit(
                    document: method_exists($model, 'toSearchableArray')
                        ? $model->toSearchableArray()
                        : $model->toArray(),
                ))
                ->all(),
            facets: [],
        );
    }

    /** @return array<int, array<string, mixed>> */
    protected function getFieldConfig(): array
    {
        $model = new $this->modelType;

        if (! method_exists($model, 'indexer')) {
            return [];
        }

        return array_map(
            fn (string $field): array => ['name' => $field, 'sort' => true],
            $model->indexer()->getSortableFields(),
        );
    }

    /**
     * @param  Builder<covariant Model>  $query
     */
    private function applySearchTerm(Builder $query): void
    {
        if ($query->hasNamedScope('matching')) {
            $query->scopes(['matching' => $this->query]);

            return;
        }

        $model = $query->getModel();

        $columns = array_intersect(
            method_exists($model, 'indexer') ? $model->indexer()->getSearchableFields() : ['name'],
            $model->getConnection()->getSchemaBuilder()->getColumnListing($model->getTable()),
        );

        if ($columns === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $term = '%'.mb_strtolower((string) $this->query).'%';

        $query->where(function (Builder $builder) use ($columns, $term): void {
            foreach ($columns as $column) {
                $builder->orWhereRaw("LOWER({$column}) LIKE ?", [$term]);
            }
        });
    }
}
