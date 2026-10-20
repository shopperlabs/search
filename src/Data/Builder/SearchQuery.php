<?php

declare(strict_types=1);

namespace Shopper\Search\Data\Builder;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class SearchQuery extends Data
{
    /**
     * @param  list<string>  $facets
     * @param  array<string, array<int, string>>  $facetFilters
     */
    public function __construct(
        public ?string $query = '',
        public array $facets = [],
        public array $facetFilters = [],
    ) {}
}
