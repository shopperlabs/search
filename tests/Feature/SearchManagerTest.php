<?php

declare(strict_types=1);

use Shopper\Core\Models\Product;
use Shopper\Search\Engines\DatabaseEngine;
use Shopper\Search\Engines\TypesenseEngine;
use Shopper\Search\Facades\Search;

it('defaults to the database engine', function (): void {
    expect(Search::driver())->toBeInstanceOf(DatabaseEngine::class);
});

it('falls back to the database engine for unsupported scout drivers', function (): void {
    config(['scout.driver' => 'collection']);

    expect(Search::driver())->toBeInstanceOf(DatabaseEngine::class);
});

it('resolves the engine mapped to the model', function (): void {
    config(['shopper.search.engine_map' => [Product::class => 'typesense']]);

    expect(Search::model(Product::class)->driver())->toBeInstanceOf(TypesenseEngine::class);
});

it('resolves the mapped engine for subclasses of the mapped model', function (): void {
    config(['shopper.search.engine_map' => [Product::class => 'typesense']]);

    expect(Search::model(Shopper\Models\Product::class)->driver())->toBeInstanceOf(TypesenseEngine::class);
});

it('builds a fresh engine on every call', function (): void {
    expect(Search::driver())->not->toBe(Search::driver());
});

it('resets the model after each call so the next search falls back to products', function (): void {
    config(['shopper.search.engine_map' => [Shopper\Core\Models\Order::class => 'typesense']]);

    Search::model(Shopper\Core\Models\Order::class)->driver();

    expect(Search::driver())->toBeInstanceOf(DatabaseEngine::class);
});
