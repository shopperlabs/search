<?php

declare(strict_types=1);

namespace Shopper\Search\Facades;

use Illuminate\Support\Facades\Facade;
use Shopper\Search\Contracts\SearchManagerContract;

/**
 * @method static \Shopper\Search\Contracts\SearchManagerContract model(string $model)
 * @method static \Shopper\Search\Engines\AbstractEngine driver(?string $driver = null)
 * @method static \Illuminate\Support\Collection<string, \Shopper\Search\Data\SearchResults> storefront(string $query, int $perPage = 5, (\Shopper\Core\Models\Contracts\Channel&\Illuminate\Database\Eloquent\Model)|null $channel = null)
 * @method static \Shopper\Search\Engines\AbstractEngine query(string $query)
 * @method static \Shopper\Search\Engines\AbstractEngine channel((\Shopper\Core\Models\Contracts\Channel&\Illuminate\Database\Eloquent\Model)|null $channel)
 * @method static \Shopper\Search\Engines\AbstractEngine filter(array $filters)
 * @method static \Shopper\Search\Engines\AbstractEngine setFacets(array $facets)
 * @method static \Shopper\Search\Engines\AbstractEngine sort(string $sort)
 * @method static \Shopper\Search\Engines\AbstractEngine perPage(int $perPage)
 * @method static \Shopper\Search\Engines\AbstractEngine page(int $page)
 *
 * @see \Shopper\Search\SearchManager
 */
final class Search extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SearchManagerContract::class;
    }
}
