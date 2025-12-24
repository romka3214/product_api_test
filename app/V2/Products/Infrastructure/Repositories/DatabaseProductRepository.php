<?php

declare(strict_types=1);

namespace App\V2\Products\Infrastructure\Repositories;

use App\V2\Products\Application\DTO\ProductFilterDTO;
use App\V2\Products\Domain\Contracts\ProductSearchInterface;
use App\V2\Products\Domain\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class DatabaseProductRepository implements ProductSearchInterface
{
    public function search(ProductFilterDTO $filter): LengthAwarePaginator
    {
        $query = Product::query()->with('category');

        if ($filter->search !== null && $filter->search !== '') {
            $query->where(function (Builder $q) use ($filter) {
                $q->where('name', 'LIKE', "%{$filter->search}%");
            });
        }

        $this->applyFilters($query, $filter);
        $this->applySorting($query, $filter);

        return $query->paginate($filter->perPage, ['*'], 'page', $filter->page);
    }

    public function isAvailable(): bool
    {
        return true;
    }

    private function applyFilters(Builder $query, ProductFilterDTO $filter): void
    {
        if ($filter->priceFrom !== null) {
            $query->where('price', '>=', $filter->priceFrom);
        }

        if ($filter->priceTo !== null) {
            $query->where('price', '<=', $filter->priceTo);
        }

        if ($filter->categoryId !== null) {
            $query->where('category_id', $filter->categoryId);
        }

        if ($filter->inStock !== null) {
            $query->where('in_stock', $filter->inStock);
        }

        if ($filter->ratingFrom !== null) {
            $query->where('rating', '>=', $filter->ratingFrom);
        }
    }

    private function applySorting(Builder $query, ProductFilterDTO $filter): void
    {
        [$column, $direction] = $filter->sort->toOrderBy();
        $query->orderBy($column, $direction);
    }
}
