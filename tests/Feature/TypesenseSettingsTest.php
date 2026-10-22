<?php

declare(strict_types=1);

it('generates typesense model settings from the indexers', function (): void {
    $model = Shopper\Core\Models\Product::configuredClass();

    $settings = config('scout.typesense.model-settings.'.$model);

    expect($settings)->not->toBeNull()
        ->and($settings['search-parameters']['query_by'])->toContain('name')
        ->and(collect($settings['collection-schema']['fields'])->firstWhere('name', 'brand'))
        ->toMatchArray(['type' => 'string', 'facet' => true, 'optional' => true])
        ->and(collect($settings['collection-schema']['fields'])->firstWhere('name', 'channels'))
        ->toMatchArray(['type' => 'string[]', 'facet' => true, 'optional' => true])
        ->and(collect($settings['collection-schema']['fields'])->firstWhere('name', 'skus'))
        ->toMatchArray(['type' => 'string[]', 'optional' => true])
        ->and(collect($settings['collection-schema']['fields'])->firstWhere('name', 'description'))
        ->toMatchArray(['type' => 'string', 'optional' => true])
        ->and(collect($settings['collection-schema']['fields'])->firstWhere('name', 'created_at'))
        ->toMatchArray(['type' => 'int64', 'sort' => true]);
});

it('generates typesense settings for the customer indexer too', function (): void {
    config(['shopper.search.indexers' => [
        ...config('shopper.search.indexers', []),
        'App\Models\FakeCustomer' => Shopper\Core\Search\CustomerIndexer::class,
    ]]);

    (new Shopper\Search\SearchServiceProvider(app()))->packageBooted();

    $settings = config('scout.typesense.model-settings.App\Models\FakeCustomer');

    expect($settings)->not->toBeNull()
        ->and($settings['search-parameters']['query_by'])->toBe('name,email');
});

it('keeps user-defined typesense model settings untouched', function (): void {
    config([
        'scout.typesense.model-settings.'.Shopper\Core\Models\Brand::class => ['custom' => true],
    ]);

    (new Shopper\Search\SearchServiceProvider(app()))->packageBooted();

    expect(config('scout.typesense.model-settings.'.Shopper\Core\Models\Brand::class))
        ->toBe(['custom' => true]);
});
