<?php

declare(strict_types=1);

namespace Shopper\Search\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Shopper\Core\Models\Contracts\Channel;
use Shopper\Search\Data\SearchResults;
use Shopper\Search\Engines\AbstractEngine;

interface SearchManagerContract
{
    public function model(string $model): self;

    public function driver(?string $driver = null): AbstractEngine;

    /**
     * @return Collection<string, SearchResults>
     */
    public function storefront(string $query, int $perPage = 5, (Channel&Model)|null $channel = null): Collection;
}
