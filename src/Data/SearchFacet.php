<?php

declare(strict_types=1);

namespace Shopper\Search\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class SearchFacet extends Data
{
    /**
     * @param  array<int, FacetValue>  $values
     */
    public function __construct(
        public string $field,
        public string $label,
        #[DataCollectionOf(FacetValue::class)]
        public array $values = [],
    ) {}
}
