<?php

namespace App\Services;

use App\Http\Requests\ProductFilterRequest;
use App\Models\Product;
use Laravel\Scout\Builder;

class ProductSearchService
{
    public function search(ProductFilterRequest $request): Builder
    {
        $query = $request->getSearch() ?? '';

        return Product::search($query, function ($meilisearch, string $query, array $options) use ($request) {
            $options = $this->applyFilters($options, $request);
            $options = $this->applySorting($options, $request);

            return $meilisearch->search($query, $options);
        });
    }

    private function applyFilters(array $options, ProductFilterRequest $request): array
    {
        $filters = array_filter([
            $this->priceFilter($request),
            $this->categoryFilter($request),
            $this->stockFilter($request),
            $this->ratingFilter($request),
        ]);

        if (!empty($filters)) {
            $options['filter'] = implode(' AND ', $filters);
        }

        return $options;
    }

    private function priceFilter(ProductFilterRequest $request): ?string
    {
        $conditions = [];

        if ($request->getPriceFrom() !== null) {
            $conditions[] = "price >= {$request->getPriceFrom()}";
        }

        if ($request->getPriceTo() !== null) {
            $conditions[] = "price <= {$request->getPriceTo()}";
        }

        return !empty($conditions) ? '(' . implode(' AND ', $conditions) . ')' : null;
    }

    private function categoryFilter(ProductFilterRequest $request): ?string
    {
        return $request->getCategoryId()
            ? "category_id = {$request->getCategoryId()}"
            : null;
    }

    private function stockFilter(ProductFilterRequest $request): ?string
    {
        if ($request->getInStock() === null) {
            return null;
        }

        $inStock = $request->getInStock() ? 'true' : 'false';
        return "in_stock = {$inStock}";
    }

    private function ratingFilter(ProductFilterRequest $request): ?string
    {
        return $request->getRatingFrom() !== null
            ? "rating >= {$request->getRatingFrom()}"
            : null;
    }

    private function applySorting(array $options, ProductFilterRequest $request): array
    {
        $sort = match($request->getSort()) {
            'price_asc' => ['price:asc'],
            'price_desc' => ['price:desc'],
            'rating_desc' => ['rating:desc'],
            'newest' => ['created_at:desc'],
            default => null,
        };

        if ($sort) {
            $options['sort'] = $sort;
        }

        return $options;
    }
}
