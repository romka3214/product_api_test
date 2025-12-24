<?php

declare(strict_types=1);

namespace App\V2\Products\Infrastructure\Repositories;

use App\V2\Products\Application\DTO\ProductFilterDTO;
use App\V2\Products\Domain\Contracts\ProductSearchInterface;
use App\V2\Products\Domain\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Laravel\Scout\Builder as ScoutBuilder;

class MeilisearchProductRepository implements ProductSearchInterface
{
    public function search(ProductFilterDTO $filter): LengthAwarePaginator
    {
        $searchQuery = Product::search($filter->search ?? '');

        $searchQuery->options($this->buildOptions($filter));

        return $searchQuery
            ->query(fn($q) => $q->with('category'))
            ->paginate($filter->perPage, 'page', $filter->page);
    }

    public function isAvailable(): bool
    {
        try {
            $client = app(\Meilisearch\Client::class);
            $client->health();
            return true;
        } catch (\Throwable $e) {
            Log::warning('Meilisearch unavailable: ' . $e->getMessage());
            return false;
        }
    }

    private function buildOptions(ProductFilterDTO $filter): array
    {
        return [
            'filter' => $this->buildFilters($filter),
            'sort' => $this->buildSort($filter),
        ];
    }

    private function buildFilters(ProductFilterDTO $filter): ?string
    {
        $filters = [];

        if ($filter->priceFrom !== null) {
            $filters[] = "price >= {$filter->priceFrom}";
        }

        if ($filter->priceTo !== null) {
            $filters[] = "price <= {$filter->priceTo}";
        }

        if ($filter->categoryId !== null) {
            $filters[] = "category_id = {$filter->categoryId}";
        }

        if ($filter->inStock !== null) {
            $filters[] = 'in_stock = ' . ($filter->inStock ? 'true' : 'false');
        }

        if ($filter->ratingFrom !== null) {
            $filters[] = "rating >= {$filter->ratingFrom}";
        }

        return $filters ? implode(' AND ', $filters) : null;
    }

    private function buildSort(ProductFilterDTO $filter): array
    {
        [$column, $direction] = $filter->sort->toOrderBy();

        return ["{$column}:{$direction}"];
    }
}
