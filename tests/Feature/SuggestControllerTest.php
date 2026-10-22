<?php

declare(strict_types=1);

use Shopper\Core\Models\Brand;
use Shopper\Core\Models\Channel;
use Shopper\Core\Models\Product;

it('returns suggestions typed by catalog resource', function (): void {
    Product::factory()->publish()->create(['name' => 'Suggested Shirt']);
    Brand::factory()->create(['name' => 'Suggested Brand', 'is_enabled' => true]);

    $this->getJson('/store/search/suggest?q=Suggested')
        ->assertOk()
        ->assertJsonPath('data.0.type', 'products')
        ->assertJsonPath('data.1.type', 'brands')
        ->assertJsonPath('meta.totals', ['products' => 1, 'brands' => 1, 'categories' => 0, 'collections' => 0]);
});

it('clamps the per-type size', function (): void {
    Product::factory()->publish()->count(3)->create(['name' => 'Clamped Suggestion']);

    $this->getJson('/store/search/suggest?q=Suggestion&page[size]=2')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.totals.products', 3);
});

it('scopes product suggestions to the channel sent in the header', function (): void {
    $web = Channel::factory()->create(['slug' => 'web']);

    Product::factory()->publish()->create(['name' => 'Channel Suggestion'])->channels()->attach($web);
    Product::factory()->publish()->create(['name' => 'Channel Suggestion']);
    Brand::factory()->create(['name' => 'Channel Suggestion', 'is_enabled' => true]);

    $this->getJson('/store/search/suggest?q=Channel', ['X-Shopper-Channel' => 'web'])
        ->assertOk()
        ->assertJsonPath('meta.totals', ['products' => 1, 'brands' => 1, 'categories' => 0, 'collections' => 0]);
});

it('rejects a malformed suggest query', function (): void {
    $this->getJson('/store/search/suggest?q[]=shirt')->assertUnprocessable();
});
