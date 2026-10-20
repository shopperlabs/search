<?php

declare(strict_types=1);

namespace Shopper\Search\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\SanctumServiceProvider;
use Laravel\Scout\ScoutServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Shopper\Core\CoreServiceProvider;
use Shopper\Core\Database\Seeders\CurrenciesTableSeeder;
use Shopper\Http\HttpServiceProvider;
use Shopper\Search\SearchServiceProvider;
use Spatie\LaravelData\LaravelDataServiceProvider;
use Spatie\Permission\PermissionServiceProvider;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = CurrenciesTableSeeder::class;

    protected function getPackageProviders($app): array
    {
        return [
            PermissionServiceProvider::class,
            SanctumServiceProvider::class,
            ScoutServiceProvider::class,
            LaravelDataServiceProvider::class,
            CoreServiceProvider::class,
            HttpServiceProvider::class,
            SearchServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('scout.driver', 'database');

        $app['config']->set('shopper.models', [
            'address' => \Shopper\Core\Models\Address::class,
            'attribute_product' => \Shopper\Core\Models\AttributeProduct::class,
            'brand' => \Shopper\Core\Models\Brand::class,
            'category' => \Shopper\Core\Models\Category::class,
            'channel' => \Shopper\Core\Models\Channel::class,
            'collection' => \Shopper\Core\Models\Collection::class,
            'inventory' => \Shopper\Core\Models\Inventory::class,
            'order' => \Shopper\Core\Models\Order::class,
            'product' => \Shopper\Core\Models\Product::class,
            'supplier' => \Shopper\Core\Models\Supplier::class,
            'tax_rate' => \Shopper\Core\Models\TaxRate::class,
            'tax_zone' => \Shopper\Core\Models\TaxZone::class,
            'variant' => \Shopper\Core\Models\ProductVariant::class,
        ]);

        $app->afterResolving('migrator', function ($migrator): void {
            $migrator->path(\Orchestra\Testbench\default_migration_path());
        });
    }
}
