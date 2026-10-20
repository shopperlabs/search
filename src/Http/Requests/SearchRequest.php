<?php

declare(strict_types=1);

namespace Shopper\Search\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Shopper\Core\Models\Product;

final class SearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $sortable = (new (Product::configuredClass()))->indexer()->getSortableFields();

        return [
            'q' => ['sometimes', 'nullable', 'string', 'max:255'],
            'facets' => ['sometimes', 'array'],
            'facets.*' => ['array', 'max:50'],
            'facets.*.*' => ['string', 'max:255'],
            'page' => ['sometimes', 'array'],
            'page.number' => ['sometimes', 'integer'],
            'page.size' => ['sometimes', 'integer'],
            'sort' => ['sometimes', 'string', Rule::in([...$sortable, ...array_map(fn (string $field): string => '-'.$field, $sortable)])],
        ];
    }

    public function sort(): ?string
    {
        $sort = $this->string('sort')->toString();

        if ($sort === '') {
            return null;
        }

        return str_starts_with($sort, '-') ? mb_substr($sort, 1).':desc' : $sort.':asc';
    }
}
