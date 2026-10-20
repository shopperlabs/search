<?php

declare(strict_types=1);

namespace Shopper\Search\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;
use Illuminate\Support\Arr;
use Shopper\Search\Data\SearchHit;

/**
 * @property SearchHit $resource
 */
final class SearchHitResource extends JsonApiResource
{
    public function __construct(SearchHit $hit, private readonly string $resourceType)
    {
        parent::__construct($hit);
    }

    public function toId(Request $request): string
    {
        return (string) $this->resource->document['id'];
    }

    public function toType(Request $request): string
    {
        return $this->resourceType;
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(Request $request): array
    {
        return Arr::except($this->resource->document, 'id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toMeta(Request $request): array
    {
        return array_filter([
            'highlights' => collect($this->resource->highlights)->toArray(),
        ]);
    }
}
