<?php

declare(strict_types=1);

use Shopper\Core\Models\Order;
use Shopper\Core\Models\Product;

it('returns matching products as json:api resource objects', function (): void {
    $product = Product::factory()->publish()->create(['name' => 'Linen Shirt']);

    $this->getJson('/store/search?q=Linen')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('data.0.type', 'products')
        ->assertJsonPath('data.0.id', (string) $product->id)
        ->assertJsonPath('data.0.attributes.name', 'Linen Shirt')
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('meta.query', 'Linen');
});

it('clamps the requested page size to the configured maximum', function (): void {
    config(['shopper.api.pagination.max_per_page' => 2]);
    Product::factory()->publish()->count(3)->create(['name' => 'Clamped Shirt']);

    $this->getJson('/store/search?q=Shirt&page[size]=50')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.per_page', 2);
});

it('serves the requested page number', function (): void {
    $products = Product::factory()->publish()->count(3)->create(['name' => 'Paged Mug']);

    $this->getJson('/store/search?q=Mug&page[size]=2&page[number]=2')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', (string) $products->last()->id)
        ->assertJsonPath('meta.current_page', 2);
});

it('sorts by a sortable field in json:api notation', function (): void {
    $older = Product::factory()->publish()->create(['name' => 'Sorted Lamp', 'created_at' => now()->subDay()]);
    $newer = Product::factory()->publish()->create(['name' => 'Sorted Lamp', 'created_at' => now()]);

    $this->getJson('/store/search?q=Lamp&sort=-created_at')
        ->assertOk()
        ->assertJsonPath('data.0.id', (string) $newer->id)
        ->assertJsonPath('data.1.id', (string) $older->id);

    $this->getJson('/store/search?q=Lamp&sort=created_at')
        ->assertOk()
        ->assertJsonPath('data.0.id', (string) $older->id);
});

it('rejects malformed search parameters', function (string $query): void {
    $this->getJson('/store/search?'.$query)
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.status', '422');
})->with([
    'array query' => ['q[]=shirt'],
    'nested facet values' => ['facets[brand][nike][]=x'],
    'unknown sort' => ['sort=-price'],
    'non numeric page' => ['page[number]=abc'],
]);

it('never searches orders', function (): void {
    Order::factory()->create(['number' => 'SO-SEARCH']);

    $this->getJson('/store/search?q=SO-SEARCH')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});
