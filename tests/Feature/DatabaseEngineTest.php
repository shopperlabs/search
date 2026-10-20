<?php

declare(strict_types=1);

use Shopper\Core\Models\Product;
use Shopper\Search\Data\SearchResults;
use Shopper\Search\Facades\Search;

it('returns matching products as search results', function (): void {
    Product::factory()->publish()->create(['name' => 'Air Max Sneakers']);
    Product::factory()->publish()->create(['name' => 'Leather Wallet']);

    $results = Search::query('air max')->get();

    expect($results)->toBeInstanceOf(SearchResults::class)
        ->and($results->count)->toBe(1)
        ->and($results->hits[0]->document['name'])->toBe('Air Max Sneakers')
        ->and($results->facets)->toBe([]);
});

it('paginates results with the configured page size', function (): void {
    Product::factory()->publish()->count(3)->create(['name' => 'Blue Shirt']);

    $results = Search::query('blue')->perPage(2)->get();

    expect($results->count)->toBe(3)
        ->and($results->hits)->toHaveCount(2)
        ->and($results->totalPages)->toBe(2);
});

it('does not surface unpublished products in the results', function (): void {
    Product::factory()->publish()->create(['name' => 'Published Sneaker']);
    Product::factory()->create(['name' => 'Draft Sneaker', 'is_visible' => false]);

    $results = Search::query('Sneaker')->get();

    expect($results->count)->toBe(1)
        ->and($results->hits[0]->document['name'])->toBe('Published Sneaker');
});

it('filters models without a matching scope on their searchable columns', function (): void {
    Shopper\Core\Models\Brand::factory()->create(['name' => 'Nike', 'is_enabled' => true]);
    Shopper\Core\Models\Brand::factory()->create(['name' => 'Adidas', 'is_enabled' => true]);

    $results = Search::model(Shopper\Core\Models\Brand::class)->query('Nike')->get();

    expect($results->count)->toBe(1)
        ->and($results->hits[0]->document['name'])->toBe('Nike');
});

it('only keeps facet selections defined in the config', function (): void {
    $engine = Search::query('shirt')->setFacets([
        'brand' => ['Nike'],
        'evil_field' => ['x'],
    ]);

    expect($engine->getFacets())->toBe(['brand' => ['Nike']]);
});
