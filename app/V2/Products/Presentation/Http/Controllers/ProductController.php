<?php

declare(strict_types=1);

namespace App\V2\Products\Presentation\Http\Controllers;

use App\V2\Products\Application\Services\ProductSearchService;
use App\V2\Products\Presentation\Http\Requests\ProductFilterRequest;
use App\V2\Products\Presentation\Http\Resources\ProductResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

readonly class ProductController
{
    public function __construct(
        private ProductSearchService $searchService
    )
    {
    }

    public function index(ProductFilterRequest $request): AnonymousResourceCollection
    {
        $products = $this->searchService->search($request->toDTO());

        return ProductResource::collection($products);
    }
}
