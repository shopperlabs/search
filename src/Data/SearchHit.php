<?php

declare(strict_types=1);

namespace Shopper\Search\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class SearchHit extends Data
{
    /**
     * @param  array<string, mixed>  $document
     * @param  array<int, Highlight>  $highlights
     */
    public function __construct(
        public array $document,
        #[DataCollectionOf(Highlight::class)]
        public array $highlights = [],
    ) {}
}
