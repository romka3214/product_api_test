<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductFilterRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function index(ProductFilterRequest $request): AnonymousResourceCollection
    {
        $products = Product::query()
            ->with('category')
            ->search($request->getSearch())
            ->priceBetween($request->getPriceFrom(), $request->getPriceTo())
            ->inCategory($request->getCategoryId())
            ->available($request->getInStock())
            ->minRating($request->getRatingFrom())
            ->when($request->getSort() === 'price_asc', fn($q) => $q->orderBy('price'))
            ->when($request->getSort() === 'price_desc', fn($q) => $q->orderByDesc('price'))
            ->when($request->getSort() === 'rating_desc', fn($q) => $q->orderByDesc('rating'))
            ->when($request->getSort() === 'newest', fn($q) => $q->latest())
            ->paginate($request->getPerPage());

        return ProductResource::collection($products);
    }
}
