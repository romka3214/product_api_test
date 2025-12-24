<?php

declare(strict_types=1);

namespace App\V2\Products\Infrastructure\Providers;

use App\V2\Products\Application\Services\ProductSearchService;
use App\V2\Products\Infrastructure\Repositories\DatabaseProductRepository;
use App\V2\Products\Infrastructure\Repositories\MeilisearchProductRepository;
use Illuminate\Support\ServiceProvider;

class ProductServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ProductSearchService::class, function ($app) {
            return new ProductSearchService(
                $app->make(MeilisearchProductRepository::class),
                $app->make(DatabaseProductRepository::class),
            );
        });
    }
}
