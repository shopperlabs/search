<?php

declare(strict_types=1);

namespace Shopper\Search;

use Illuminate\Support\Facades\Route;
use Shopper\Http\Facades\ShopperApi;
use Shopper\Search\Console\InstallCommand;
use Shopper\Search\Contracts\SearchManagerContract;
use Shopper\Search\Http\Controllers\SearchController;
use Shopper\Search\Http\Controllers\SuggestController;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class SearchServiceProvider extends PackageServiceProvider
{
    private const LIST_FIELDS = ['categories', 'channels', 'skus'];

    public function configurePackage(Package $package): void
    {
        $package
            ->name('shopper-search')
            ->hasCommand(InstallCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(SearchManagerContract::class, fn ($app): SearchManager => new SearchManager($app));
    }

    public function packageBooted(): void
    {
        $this->configureTypesenseModelSettings();
        $this->registerStoreRoutes();
    }

    private function configureTypesenseModelSettings(): void
    {
        foreach ((array) config('shopper.search.indexers', []) as $model => $indexerClass) {
            $concrete = method_exists($model, 'configuredClass') ? $model::configuredClass() : $model;

            if (config('scout.typesense.model-settings.'.$concrete)) {
                continue;
            }

            $indexer = $this->app->make($indexerClass);

            $fields = [];
            $filterable = $indexer->getFilterableFields();
            $sortable = $indexer->getSortableFields();

            foreach ($filterable as $field) {
                $fields[] = ['name' => $field, 'type' => $this->typesenseType($field), 'facet' => true, 'optional' => true, 'sort' => in_array($field, $sortable, true)];
            }

            foreach (array_diff($sortable, $filterable) as $field) {
                $fields[] = ['name' => $field, 'type' => 'int64', 'optional' => true, 'sort' => true];
            }

            foreach (array_diff($indexer->getSearchableFields(), $filterable, $sortable) as $field) {
                $fields[] = ['name' => $field, 'type' => $this->typesenseType($field), 'optional' => true];
            }

            $fields[] = ['name' => '.*', 'type' => 'auto'];

            config([
                'scout.typesense.model-settings.'.$concrete => [
                    'collection-schema' => ['fields' => $fields],
                    'search-parameters' => ['query_by' => implode(',', $indexer->getSearchableFields())],
                ],
            ]);
        }
    }

    private function typesenseType(string $field): string
    {
        return in_array($field, self::LIST_FIELDS, true) ? 'string[]' : 'string';
    }

    private function registerStoreRoutes(): void
    {
        ShopperApi::store(function (): void {
            Route::get('/search', SearchController::class);
            Route::get('/search/suggest', SuggestController::class);
        });
    }
}
