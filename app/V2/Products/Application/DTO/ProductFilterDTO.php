<?php
declare(strict_types=1);

namespace App\V2\Products\Application\DTO;

use App\V2\Products\Domain\Enums\ProductSortType;

final readonly class ProductFilterDTO
{
    public function __construct(
        public ?string         $search = null,
        public ?float          $priceFrom = null,
        public ?float          $priceTo = null,
        public ?int            $categoryId = null,
        public ?bool           $inStock = null,
        public ?float          $ratingFrom = null,
        public ProductSortType $sort = ProductSortType::NEWEST,
        public int             $perPage = 15,
        public int             $page = 1,
    )
    {
    }

    public function hasSearch(): bool
    {
        return $this->search !== null && $this->search !== '';
    }

    public function hasCategory(): bool
    {
        return $this->categoryId !== null;
    }

    public function hasStockFilter(): bool
    {
        return $this->inStock !== null;
    }

    public function hasRatingFilter(): bool
    {
        return $this->ratingFrom !== null;
    }
}
