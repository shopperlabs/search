<?php

declare(strict_types=1);

use Shopper\Core\Models\Product;
use Shopper\Enum\FeatureState;
use Shopper\Search\Data\SearchResults;
use Shopper\Search\Facades\Search;

it('returns grouped results for every catalog type', function (): void {
    $results = Search::storefront('shirt');

    expect($results->keys()->all())->toBe(['products', 'brands', 'categories', 'collections'])
        ->and($results['products'])->toBeInstanceOf(SearchResults::class);
});

it('omits a catalog type when its feature is disabled', function (): void {
    config(['shopper.features.collection' => FeatureState::Disabled]);

    expect(Search::storefront('shirt')->keys()->all())
        ->toBe(['products', 'brands', 'categories']);
});

it('always searches products even when every feature is disabled', function (): void {
    config([
        'shopper.features.brand' => FeatureState::Disabled,
        'shopper.features.category' => FeatureState::Disabled,
        'shopper.features.collection' => FeatureState::Disabled,
    ]);

    Product::factory()->publish()->create(['name' => 'Storefront Shirt']);

    $results = Search::storefront('Shirt');

    expect($results->keys()->all())->toBe(['products'])
        ->and($results['products']->hits)->toHaveCount(1);
});

it('limits each catalog type to the given page size', function (): void {
    Product::factory()->publish()->count(3)->create(['name' => 'Sized Storefront Mug']);

    expect(Search::storefront('Mug', 2)['products']->hits)->toHaveCount(2);
});
