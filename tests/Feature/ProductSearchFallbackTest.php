<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\V2\Products\Application\DTO\ProductFilterDTO;
use App\V2\Products\Application\Services\ProductSearchService;
use App\V2\Products\Domain\Contracts\ProductSearchInterface;
use App\V2\Products\Infrastructure\Repositories\DatabaseProductRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ProductSearchFallbackTest extends TestCase
{
    use RefreshDatabase;

    private function setupFallbackToDatabase(bool $meilisearchAvailable = false, bool $throwException = false): void
    {
        $meilisearchMock = Mockery::mock(ProductSearchInterface::class);
        $meilisearchMock->shouldReceive('isAvailable')->andReturn($meilisearchAvailable);

        if ($throwException) {
            $meilisearchMock->shouldReceive('search')
                ->andThrow(new \RuntimeException('Connection refused'));
        }

        $databaseRepo = new DatabaseProductRepository();

        $this->app->instance(ProductSearchService::class, new ProductSearchService(
            $meilisearchMock,
            $databaseRepo
        ));
    }

    public function test_endpoint_works_when_meilisearch_is_unavailable_on_init(): void
    {
        $this->setupFallbackToDatabase(meilisearchAvailable: false);

        $category = Category::factory()->create();
        Product::factory()->count(3)->create(['category_id' => $category->id]);

        $response = $this->getJson('/api/products');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_endpoint_works_when_meilisearch_throws_exception_during_search(): void
    {
        $this->setupFallbackToDatabase(meilisearchAvailable: true, throwException: true);

        $category = Category::factory()->create();
        Product::factory()->count(5)->create(['category_id' => $category->id]);

        $response = $this->getJson('/api/products');

        $response->assertOk()
            ->assertJsonCount(5, 'data');
    }

    public function test_filters_work_correctly_after_fallback_to_database(): void
    {
        $this->setupFallbackToDatabase(meilisearchAvailable: true, throwException: true);

        $category = Category::factory()->create();

        Product::factory()->create([
            'name' => 'Expensive Product',
            'price' => 1000,
            'in_stock' => true,
            'category_id' => $category->id,
        ]);

        Product::factory()->create([
            'name' => 'Cheap Product',
            'price' => 50,
            'in_stock' => true,
            'category_id' => $category->id,
        ]);

        Product::factory()->create([
            'name' => 'Out of Stock Product',
            'price' => 500,
            'in_stock' => false,
            'category_id' => $category->id,
        ]);

        // Тест фильтра по цене
        $response = $this->getJson('/api/products?price_from=100');
        $response->assertOk()->assertJsonCount(2, 'data');

        // Тест фильтра по наличию
        $response = $this->getJson('/api/products?in_stock=1');
        $response->assertOk()->assertJsonCount(2, 'data');

        // Тест комбинированных фильтров
        $response = $this->getJson('/api/products?price_from=100&in_stock=1');
        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_search_by_name_works_after_fallback(): void
    {
        $this->setupFallbackToDatabase(meilisearchAvailable: true, throwException: true);

        $category = Category::factory()->create();

        Product::factory()->create([
            'name' => 'Gaming Laptop',
            'category_id' => $category->id,
        ]);

        Product::factory()->create([
            'name' => 'Office Mouse',
            'category_id' => $category->id,
        ]);

        $response = $this->getJson('/api/products?q=Laptop');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => 'Gaming Laptop']);
    }

    public function test_sorting_works_after_fallback(): void
    {
        $this->setupFallbackToDatabase(meilisearchAvailable: true, throwException: true);

        $category = Category::factory()->create();

        Product::factory()->create(['price' => 100, 'category_id' => $category->id]);
        Product::factory()->create(['price' => 300, 'category_id' => $category->id]);
        Product::factory()->create(['price' => 200, 'category_id' => $category->id]);

        $response = $this->getJson('/api/products?sort=price_asc');

        $response->assertOk();
        $this->assertEquals('100.00', $response->json('data.0.price'));
        $this->assertEquals('200.00', $response->json('data.1.price'));
        $this->assertEquals('300.00', $response->json('data.2.price'));
    }

    public function test_pagination_works_after_fallback(): void
    {
        $this->setupFallbackToDatabase(meilisearchAvailable: true, throwException: true);

        $category = Category::factory()->create();
        Product::factory()->count(25)->create(['category_id' => $category->id]);

        $response = $this->getJson('/api/products?per_page=10');

        $response->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 25)
            ->assertJsonPath('meta.last_page', 3);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
