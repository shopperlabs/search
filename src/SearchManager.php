<?php

declare(strict_types=1);

namespace Shopper\Search;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Manager;
use Illuminate\Support\Str;
use Shopper\Core\Models\Brand;
use Shopper\Core\Models\Category;
use Shopper\Core\Models\Collection as ProductCollection;
use Shopper\Core\Models\Product;
use Shopper\Enum\FeatureState;
use Shopper\Search\Contracts\SearchManagerContract;
use Shopper\Search\Data\SearchResults;
use Shopper\Search\Engines\AbstractEngine;
use Shopper\Search\Engines\DatabaseEngine;
use Shopper\Search\Engines\TypesenseEngine;
use Shopper\Search\Support\ModelConfig;

final class SearchManager extends Manager implements SearchManagerContract
{
    private const STOREFRONT_TYPES = [
        'products' => [Product::class, null],
        'brands' => [Brand::class, 'brand'],
        'categories' => [Category::class, 'category'],
        'collections' => [ProductCollection::class, 'collection'],
    ];

    /** @var class-string<Model>|null */
    protected ?string $model = null;

    /**
     * @param  class-string<Model>  $model
     */
    public function model(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function driver($driver = null): AbstractEngine
    {
        $model = $this->pullModel();

        $driver ??= ModelConfig::lookup('shopper.search.engine_map', $model)
            ?? $this->getDefaultDriver();

        return $this->createDriver($driver)->within($model);
    }

    /**
     * @return Collection<string, SearchResults>
     */
    public function storefront(string $query, int $perPage = 5): Collection
    {
        return collect(self::STOREFRONT_TYPES)
            ->filter(fn (array $type): bool => $type[1] === null || $this->featureEnabled($type[1]))
            ->map(fn (array $type): SearchResults => $this->model($type[0]::configuredClass())
                ->driver()
                ->query($query)
                ->perPage($perPage)
                ->get());
    }

    public function getDefaultDriver(): string
    {
        $driver = (string) config('scout.driver', 'database');

        return $this->supports($driver) ? $driver : 'database';
    }

    public function createDatabaseDriver(): DatabaseEngine
    {
        return $this->container->make(DatabaseEngine::class);
    }

    public function createTypesenseDriver(): TypesenseEngine
    {
        return $this->container->make(TypesenseEngine::class);
    }

    /**
     * @return class-string<Model>
     */
    private function pullModel(): string
    {
        $model = $this->model ?? Product::configuredClass();

        $this->model = null;

        return $model;
    }

    private function supports(string $driver): bool
    {
        return isset($this->customCreators[$driver])
            || method_exists($this, 'create'.Str::studly($driver).'Driver');
    }

    private function featureEnabled(string $feature): bool
    {
        return config('shopper.features.'.$feature, FeatureState::Enabled) === FeatureState::Enabled;
    }
}
