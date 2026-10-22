<?php

declare(strict_types=1);

namespace Shopper\Search\Engines;

use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Shopper\Core\Models\Contracts\Channel;
use Shopper\Core\Models\Product;
use Shopper\Search\Data\Builder\SearchQuery;
use Shopper\Search\Data\SearchResults;
use Shopper\Search\Support\ModelConfig;

abstract class AbstractEngine
{
    /** @var class-string<Model> */
    protected string $modelType;

    protected ?string $query = null;

    protected (Channel&Model)|null $channel = null;

    /** @var array<string, mixed> */
    protected array $filters = [];

    /** @var array<string, array<int, string>> */
    protected array $facets = [];

    protected int $perPage = 50;

    protected int $page = 1;

    protected string $sort = '';

    protected string $sortRaw = '';

    /** @var list<Closure> */
    protected array $queryExtenders = [];

    public function __construct()
    {
        $this->modelType = Product::configuredClass();
    }

    abstract public function get(): SearchResults;

    /**
     * @param  class-string<Model>  $model
     */
    public function within(string $model): static
    {
        $this->modelType = $model;

        return $this;
    }

    public function query(string $query): static
    {
        $this->query = $query;

        return $this;
    }

    public function getQuery(): ?string
    {
        return $this->query;
    }

    public function channel((Channel&Model)|null $channel): static
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function filter(array $filters): static
    {
        foreach ($filters as $key => $value) {
            $this->addFilter($key, $value);
        }

        return $this;
    }

    public function addFilter(string $key, mixed $value): static
    {
        $this->filters[$key] = $value;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @param  array<string, array<int, string>>  $facets
     */
    public function setFacets(array $facets): static
    {
        $this->facets = array_intersect_key(
            $facets,
            (array) $this->getFacetConfig()
        );

        return $this;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function getFacets(): array
    {
        return $this->facets;
    }

    public function removeFacet(string $field, mixed $value = null): static
    {
        if (empty($this->facets[$field])) {
            return $this;
        }

        if (! $value) {
            unset($this->facets[$field]);

            return $this;
        }

        $this->facets[$field] = collect($this->facets[$field])
            ->reject(fn ($facetValue): bool => $facetValue === $value)
            ->values()
            ->toArray();

        return $this;
    }

    public function sort(string $sort): static
    {
        $this->sort = $sort;

        return $this;
    }

    public function sortRaw(string $sort): static
    {
        $this->sortRaw = $sort;

        return $this;
    }

    public function perPage(int $perPage): static
    {
        $this->perPage = $perPage;

        return $this;
    }

    public function page(int $page): static
    {
        $this->page = $page;

        return $this;
    }

    public function extendQuery(Closure $callback): static
    {
        $this->queryExtenders[] = $callback;

        return $this;
    }

    protected function getRawResults(Closure $builder): LengthAwarePaginator
    {
        return $this->modelType::search($this->query, $builder)
            ->paginateRaw(perPage: $this->perPage, page: $this->page);
    }

    /** @return array<string, mixed>|null */
    protected function getFacetConfig(?string $field = null): ?array
    {
        $config = (array) ModelConfig::lookup('shopper.search.facets', $this->modelType, []);

        if (! $field) {
            return $config;
        }

        return $config[$field] ?? [];
    }

    /** @return Collection<int, SearchQuery> */
    protected function getSearchQueries(): Collection
    {
        $facets = (array) $this->getFacetConfig();

        $queries = [
            new SearchQuery(
                query: $this->query,
                facets: array_keys($facets),
                facetFilters: $this->facets,
            ),
        ];

        foreach (array_keys($this->facets) as $facetField) {
            $queries[] = new SearchQuery(
                query: $this->query,
                facets: [$facetField],
                facetFilters: collect($this->facets)
                    ->reject(fn ($values, string $field): bool => $field === $facetField)
                    ->toArray(),
            );
        }

        foreach ($this->queryExtenders as $extender) {
            $extender($this, $queries);
        }

        return collect($queries);
    }

    protected function sortByIsValid(): bool
    {
        if (! $this->sort) {
            return true;
        }

        $parts = explode(':', $this->sort);

        if (! isset($parts[1]) || ! in_array($parts[1], ['asc', 'desc'], true)) {
            return false;
        }

        $field = collect($this->getFieldConfig())->first(
            fn (array $field): bool => $field['name'] === $parts[0]
        );

        return $field && ($field['sort'] ?? false);
    }

    /** @return array<int, array<string, mixed>> */
    abstract protected function getFieldConfig(): array;
}
