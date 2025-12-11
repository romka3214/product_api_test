<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductFilterRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductSearchService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function index(ProductFilterRequest $request, ProductSearchService $searchService): AnonymousResourceCollection
    {
        $products = $searchService->search($request)
            ->paginate($request->getPerPage())
            ->through(fn ($product) => $product->load('category'));

        return ProductResource::collection($products);
    }
}
