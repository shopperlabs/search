<?php

declare(strict_types=1);

namespace Shopper\Search\Contracts;

use Illuminate\Support\Collection;
use Shopper\Search\Data\SearchResults;
use Shopper\Search\Engines\AbstractEngine;

interface SearchManagerContract
{
    public function model(string $model): self;

    public function driver(?string $driver = null): AbstractEngine;

    /**
     * @return Collection<string, SearchResults>
     */
    public function storefront(string $query, int $perPage = 5): Collection;
}
