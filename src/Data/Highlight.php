<?php

declare(strict_types=1);

namespace Shopper\Search\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class Highlight extends Data
{
    /**
     * @param  array<int, string>  $matches
     */
    public function __construct(
        public string $field,
        public array $matches = [],
        public ?string $snippet = null,
    ) {}
}
