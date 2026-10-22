<?php

declare(strict_types=1);

namespace Shopper\Search\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Shopper\Search\Data\SearchHit;
use Shopper\Search\Data\SearchResults;
use Shopper\Search\Facades\Search;
use Shopper\Search\Http\Resources\SearchHitResource;

final class SuggestController
{
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'max:255'],
            'page' => ['sometimes', 'array'],
            'page.size' => ['sometimes', 'integer'],
        ]);

        $query = (string) $request->string('q');
        $sections = Search::storefront(
            $query,
            min(max($request->integer('page.size', 5), 1), 20),
            $request->attributes->get('shopper_channel'),
        );

        $hits = $sections->flatMap(fn (SearchResults $results, string $type): array => array_map(
            fn (SearchHit $hit): SearchHitResource => new SearchHitResource($hit, $type),
            $results->hits,
        ));

        return SearchHitResource::collection($hits->values())->additional([
            'meta' => [
                'query' => $query,
                'totals' => $sections->map(fn (SearchResults $results): int => $results->count)->all(),
            ],
        ]);
    }
}
