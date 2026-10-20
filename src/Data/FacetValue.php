<?php

declare(strict_types=1);

namespace Shopper\Search\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class FacetValue extends Data
{
    public function __construct(
        public string $label,
        public string $value,
        public int $count,
        public bool $active = false,
    ) {}
}
