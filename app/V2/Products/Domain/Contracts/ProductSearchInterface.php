<?php

namespace App\V2\Products\Domain\Contracts;

use App\V2\Products\Application\DTO\ProductFilterDTO;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProductSearchInterface
{
    /**
     * Search products with filters and pagination
     */
    public function search(ProductFilterDTO $filter): LengthAwarePaginator;

    /**
     * Check if resource is available
     */
    public function isAvailable(): bool;
}
