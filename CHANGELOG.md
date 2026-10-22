# Changelog

All notable changes to `shopper/search` will be documented in this file.

## v1.0.0 - 2026-10-22

> [!IMPORTANT]
First release of Search, a free add-on for Shopper 3. Faceted product search for your storefront through the Store API, powered by Laravel Scout and Typesense, with a database fallback.

### Installation

Shopper 3 is still a release candidate, so your application needs:

```json
"minimum-stability": "RC",
"prefer-stable": true

```
```bash
composer require shopper/search
php artisan shopper:search:install

```
The install command registers the add-on in `AppServiceProvider`. To register it by hand:

```php
use Shopper\Facades\Shopper;
use Shopper\Search\SearchAddon;

Shopper::addons([
    new SearchAddon,
]);

```
Point Scout at Typesense in your `.env`, then build the indexes:

```dotenv
SCOUT_DRIVER=typesense
TYPESENSE_API_KEY=xyz
TYPESENSE_HOST=localhost

```
```bash
php artisan scout:import "Shopper\Core\Models\Product"

```
A product is indexed when it is saved, visible and already published. Nothing saves it again when a future `published_at` comes due, so schedule `scout:import` if you plan publications ahead. Facets are declared per model in `config/shopper/search.php`, published by the core.

### New Features

- feat(api): `GET /store/search` searches products with facets, sorting (`sort=-created_at`) and JSON:API pagination (`page[number]`, `page[size]`). A malformed parameter returns a `422` JSON:API error document.
- feat(api): `GET /store/search/suggest` searches products, brands, categories and collections in one request, up to `page[size]` hits per type, with the number of matches per type in `meta.totals`
- feat(api): hits are JSON:API resources identified by their `public_id`, the same id the catalog endpoints return, with Typesense highlights in `meta.highlights`
- feat(api): both routes follow the sales channel the Store API resolves from the `X-Shopper-Channel` header, and products outside that channel are left out ([#3](https://github.com/shopperlabs/search/pull/3))
- feat(search): `Search` facade with a fluent builder (`query`, `setFacets`, `filter`, `sort`, `perPage`, `page`, `channel`), and `Search::model()` to search orders or any other indexed model
- feat(search): disjunctive faceting: each active facet is queried again without its own filter, so the counts of the other values stay visible
- feat(typesense): collection schemas and `query_by` built from the core indexers, with typed fields: `string`, `string[]` for categories, channels and SKUs, `int64` for sortable fields ([#3](https://github.com/shopperlabs/search/pull/3))
- feat(typesense): a model with no indexed document yet returns no results instead of an error, and an unreachable Typesense server is reported while the search returns empty results ([#3](https://github.com/shopperlabs/search/pull/3))
- feat(engines): `typesense` engine with facets, filtering, sorting and highlighting, and a `database` engine without facets built on the core search scope
- feat(console): `shopper:search:install` registers the add-on in `AppServiceProvider` and is safe to run twice

### Requirements

- PHP `8.3+`
- Laravel `12.68+` or `13.27+`
- Shopper `^3.0.0-rc.4`, which also matches `3.0.0` stable
- Laravel Scout `10.13+`
- A Typesense server for facets and highlights (the `database` engine works without one)

### Contributors

@mckenziearts

**Full Changelog**: https://github.com/shopperlabs/search/commits/v1.0.0
