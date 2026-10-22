# Shopper Search

Ecommerce search with facets for [Shopper](https://shopperlabs.com), powered by Laravel Scout.

The Shopper core handles indexing: models expose their payload through indexer classes configured in `config/shopper/search.php`. This addon provides the search side: engines, disjunctive faceting, normalized results, and a Store API endpoint.

## Supported engines

- `typesense`: facets, filtering, sorting, highlighting
- `database`: fallback without facets, using the core search scope

## Installation

```bash
composer require shopper/search
php artisan shopper:search:install
```

Configure Scout for Typesense in your `.env`:

```dotenv
SCOUT_DRIVER=typesense
TYPESENSE_API_KEY=xyz
TYPESENSE_HOST=localhost
```

Then build the indexes:

```bash
php artisan scout:import "Shopper\Core\Models\Product"
```

A product is indexed when it is saved, visible and already published. Nothing saves it again when a future `published_at` comes due, so schedule `scout:import` (daily, for instance) if you plan publications ahead.

Collection schemas and `query_by` parameters are generated automatically from the core indexers. Searchable and filterable fields are declared as optional `string` fields, except `categories`, `channels` and `skus`, which are `string[]`; sortable fields are `int64`. A custom indexer with other list or numeric fields needs its own `scout.typesense.model-settings` entry, which also lets you take full control of any model.

## Usage

```php
use Shopper\Search\Facades\Search;

$results = Search::query('running shoes')
    ->setFacets(['brand' => ['Nike', 'Adidas']])
    ->sort('created_at:desc')
    ->perPage(24)
    ->get();

$results->hits;   // SearchHit[] with document + highlights
$results->facets; // SearchFacet[] with per-value counts and active state
```

Search another model:

```php
Search::model(config('shopper.models.order'))->query('SO-1234')->get();
```

Limit products to a sales channel:

```php
Search::query('running shoes')->channel($channel)->get();
```

Facets stay accurate when filters are applied: each active facet is re-queried without its own filter (disjunctive faceting), so the counts of the other values remain visible.

## Store API

The addon registers two routes under the Store API prefix (`SHOPPER_API_PREFIX`, `store` by default):

```
GET /store/search?q=shoes&facets[brand][]=Nike&sort=-created_at&page[number]=2&page[size]=24
GET /store/search/suggest?q=sho&page[size]=5
```

`/search` searches products. `sort` takes one of the product indexer's sortable fields (`created_at`, `published_at`), prefixed with `-` for descending order. `page[size]` defaults to `shopper.api.pagination.per_page` (15) and is capped by `shopper.api.pagination.max_per_page` (100).

`/search/suggest` searches products, brands, categories and collections at once, returning up to `page[size]` hits per type (5 by default, 20 at most).

Both routes follow the channel the Store API resolves from the `X-Shopper-Channel` header (or `SHOPPER_API_DEFAULT_CHANNEL`): products outside that channel are left out, as on the catalog endpoints. Brands, categories and collections are not scoped by channel. Without a channel, every published product is searched.

Both return a JSON:API document. Each hit is a resource object typed after its model and identified by its `public_id`, the same id the Store API catalog endpoints return, with the indexed document as attributes. Typesense highlights land in the resource `meta.highlights`:

```json
{
  "data": [
    {
      "id": "01K7Z9YH3N0Q8M4X2T6V5R1B9C",
      "type": "products",
      "attributes": { "name": "Running Shoes", "slug": "running-shoes", "brand": "Nike", "created_at": 1760000000 }
    }
  ],
  "links": { "first": "...", "last": "...", "prev": "...", "next": "..." },
  "meta": { "current_page": 2, "last_page": 3, "per_page": 24, "total": 57, "query": "shoes", "facets": [] }
}
```

`/search/suggest` returns `meta.query` and `meta.totals`, the number of matches per type.

A malformed parameter (an array `q`, nested facet values, an unknown `sort` field, a non numeric page) returns a `422` JSON:API error document.

## Facets

Facets are defined per model in `config/shopper/search.php` (published by the core):

```php
'facets' => [
    Shopper\Core\Models\Product::class => [
        'brand' => ['label' => 'Brand'],
        'categories' => ['label' => 'Categories'],
    ],
],
```
