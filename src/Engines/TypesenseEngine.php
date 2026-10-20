<?php

declare(strict_types=1);

namespace Shopper\Search\Engines;

use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Collection;
use Laravel\Scout\EngineManager;
use Shopper\Search\Data\FacetValue;
use Shopper\Search\Data\Highlight;
use Shopper\Search\Data\SearchFacet;
use Shopper\Search\Data\SearchHit;
use Shopper\Search\Data\SearchResults;
use Typesense\Documents;
use Typesense\Exceptions\ServiceUnavailable;
use Typesense\Exceptions\TypesenseClientError;

final class TypesenseEngine extends AbstractEngine
{
    public function get(): SearchResults
    {
        try {
            $paginator = $this->getRawResults(function (Documents $documents, string $query, array $options): array {
                $engine = app(EngineManager::class)->engine('typesense');

                $response = $engine->getMultiSearch()->perform(
                    ['searches' => $this->buildSearch($options)],
                    ['collection' => (new $this->modelType)->searchableAs()],
                );

                foreach ($response['results'] as $result) {
                    if (isset($result['error'])) {
                        throw new TypesenseClientError((string) $result['error'], (int) ($result['code'] ?? 0));
                    }
                }

                $completeResults = $response['results'][0];

                unset($response['results'][0]);

                $facets = collect($completeResults['facet_counts'] ?? [])->mapWithKeys(
                    fn (array $facet): array => [$facet['field_name'] => $facet]
                );

                foreach ($response['results'] as $result) {
                    foreach ($result['facet_counts'] ?? [] as $facet) {
                        $facets->put($facet['field_name'], $facet);
                    }
                }

                return [
                    ...$completeResults,
                    'facet_counts' => $facets->values()->toArray(),
                ];
            });
        } catch (ConnectException|ServiceUnavailable $e) {
            report($e);

            return new SearchResults(
                query: $this->query,
                count: 0,
                page: $this->page,
                perPage: $this->perPage,
                totalPages: 0,
                hits: [],
                facets: [],
            );
        }

        $results = $paginator->items();

        return new SearchResults(
            query: $this->query,
            count: $paginator->total(),
            page: $paginator->currentPage(),
            perPage: $paginator->perPage(),
            totalPages: $paginator->lastPage(),
            hits: collect($results['hits'] ?? [])->map(
                fn (array $hit): SearchHit => new SearchHit(
                    document: $hit['document'],
                    highlights: collect($hit['highlights'] ?? [])->map(
                        fn (array $highlight): Highlight => new Highlight(
                            field: $highlight['field'],
                            matches: $highlight['matched_tokens'] ?? [],
                            snippet: $highlight['snippet'] ?? null,
                        )
                    )->all(),
                )
            )->all(),
            facets: $this->mapFacets($results['facet_counts'] ?? [])->all(),
        );
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<int, array<string, mixed>>
     */
    protected function buildSearch(array $options): array
    {
        $requests = [];

        foreach ($this->getSearchQueries() as $searchQuery) {
            $filters = collect((array) ($options['filter_by'] ?? []))->filter();

            foreach ($this->filters as $field => $value) {
                $filters->push($field.':'.collect($value)->map($this->sanitize(...))->join(','));
            }

            foreach ($searchQuery->facetFilters as $field => $values) {
                $values = collect($values)->map(
                    fn (string $value): string => in_array($value, ['true', 'false'], true)
                        ? $value
                        : '`'.$this->sanitize($value).'`'
                );

                $filters->push(
                    $values->count() > 1
                        ? $field.':=['.$values->join(',').']'
                        : $field.':='.$values->first()
                );
            }

            $params = [
                ...$options,
                'q' => (string) $searchQuery->query,
                'facet_by' => implode(',', $searchQuery->facets),
                'max_facet_values' => 50,
                'sort_by' => $this->sortRaw ?: ($this->sortByIsValid() ? $this->sort : '_text_match:desc'),
            ];

            if ($filters->isNotEmpty()) {
                $params['filter_by'] = $filters->join(' && ');
            }

            $requests[] = $params;
        }

        return $requests;
    }

    /**
     * @param  array<int, array<string, mixed>>  $facetCounts
     * @return Collection<int, SearchFacet>
     */
    protected function mapFacets(array $facetCounts): Collection
    {
        return collect($facetCounts)->map(function (array $facet): SearchFacet {
            $field = $facet['field_name'];
            $config = (array) $this->getFacetConfig($field);

            return new SearchFacet(
                field: $field,
                label: $config['label'] ?? $field,
                values: collect($facet['counts'] ?? [])->map(
                    fn (array $value): FacetValue => new FacetValue(
                        label: (string) $value['value'],
                        value: (string) $value['value'],
                        count: (int) $value['count'],
                        active: in_array((string) $value['value'], $this->facets[$field] ?? [], true),
                    )
                )->all(),
            );
        })->values();
    }

    /** @return array<int, array<string, mixed>> */
    protected function getFieldConfig(): array
    {
        return (array) config(
            'scout.typesense.model-settings.'.$this->modelType.'.collection-schema.fields',
            []
        );
    }

    private function sanitize(mixed $value): string
    {
        return str_replace(['`', '&&', '||'], '', (string) $value);
    }
}
