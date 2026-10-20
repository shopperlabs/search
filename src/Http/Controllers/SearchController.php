<?php

declare(strict_types=1);

namespace Shopper\Search\Http\Controllers;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Shopper\Search\Data\SearchHit;
use Shopper\Search\Facades\Search;
use Shopper\Search\Http\Requests\SearchRequest;
use Shopper\Search\Http\Resources\SearchHitResource;

final class SearchController
{
    public function __invoke(SearchRequest $request): AnonymousResourceCollection
    {
        $default = (int) config('shopper.api.pagination.per_page', 15);
        $max = (int) config('shopper.api.pagination.max_per_page', 100);
        $maxPage = (int) config('shopper.api.pagination.max_page', 100);

        $engine = Search::query((string) $request->string('q'))
            ->setFacets(array_filter((array) $request->input('facets', [])))
            ->perPage(min(max($request->integer('page.size', $default), 1), $max))
            ->page(min(max($request->integer('page.number', 1), 1), $maxPage));

        if (($sort = $request->sort()) !== null) {
            $engine->sort($sort);
        }

        $results = $engine->get();

        $hits = new LengthAwarePaginator(
            items: array_map(fn (SearchHit $hit): SearchHitResource => new SearchHitResource($hit, 'products'), $results->hits),
            total: $results->count,
            perPage: $results->perPage,
            currentPage: $results->page,
            options: ['path' => $request->url(), 'pageName' => 'page[number]'],
        );

        return SearchHitResource::collection($hits->withQueryString())->additional([
            'meta' => [
                'query' => $results->query,
                'facets' => collect($results->facets)->toArray(),
            ],
        ]);
    }
}
