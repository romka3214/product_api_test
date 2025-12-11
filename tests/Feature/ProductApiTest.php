<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Laravel\Scout\EngineManager;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $client = app(\Meilisearch\Client::class);
        $params = [
            'id',
            'name',
            'description',
            'price',
            'rating',
            'in_stock',
            'category_id',
            'created_at'
        ];
        $index = $client->index('test_products');
        $index->deleteAllDocuments();
        $index->updateSettings([
            'sortableAttributes' => $params,
            'filterableAttributes' => $params,
        ]);
    }

    // Успешные сценарии
    public function test_returns_paginated_products(): void
    {
        Product::factory()->count(20)->create();
        sleep(2);
        $response = $this->getJson('/api/products');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['*' => ['id', 'name', 'price', 'in_stock', 'rating', 'category']],
                'links',
                'meta',
            ])
            ->assertJsonCount(15, 'data');
    }

    public function test_filters_by_name_search(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['name' => 'Laptop Pro', 'category_id' => $category->id]);
        Product::factory()->create(['name' => 'Mouse Wireless', 'category_id' => $category->id]);
        Product::factory()->create(['name' => 'Keyboard', 'category_id' => $category->id]);
        sleep(3);
        $response = $this->getJson('/api/products?q=Laptop');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Laptop Pro');
    }

    public function test_filters_by_price_range(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['price' => 50, 'category_id' => $category->id]);
        Product::factory()->create(['price' => 150, 'category_id' => $category->id]);
        Product::factory()->create(['price' => 250, 'category_id' => $category->id]);
        sleep(3);
        $response = $this->getJson('/api/products?price_from=100&price_to=200');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_filters_by_category(): void
    {
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();

        Product::factory()->count(2)->create(['category_id' => $category1->id]);
        Product::factory()->count(3)->create(['category_id' => $category2->id]);
        sleep(3);
        $response = $this->getJson("/api/products?category_id={$category1->id}");

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_filters_by_stock_status(): void
    {
        $category = Category::factory()->create();
        Product::factory()->count(2)->create(['in_stock' => true, 'category_id' => $category->id]);
        Product::factory()->count(3)->create(['in_stock' => false, 'category_id' => $category->id]);
        sleep(3);
        $response = $this->getJson('/api/products?in_stock=1');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_filters_by_minimum_rating(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['rating' => 3.5, 'category_id' => $category->id]);
        Product::factory()->create(['rating' => 4.5, 'category_id' => $category->id]);
        sleep(3);
        $response = $this->getJson('/api/products?rating_from=4.0');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_sorts_by_price_ascending(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['price' => 100, 'category_id' => $category->id]);
        Product::factory()->create(['price' => 50, 'category_id' => $category->id]);
        sleep(3);
        $response = $this->getJson('/api/products?sort=price_asc');

        $response->assertOk();
        $this->assertEquals('50.00', $response->json('data.0.price'));
    }

    public function test_sorts_by_price_descending(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['price' => 50, 'category_id' => $category->id]);
        Product::factory()->create(['price' => 100, 'category_id' => $category->id]);
        sleep(3);
        $response = $this->getJson('/api/products?sort=price_desc');

        $response->assertOk();
        $this->assertEquals('100.00', $response->json('data.0.price'));
    }

    public function test_sorts_by_rating_descending(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['rating' => 3.5, 'category_id' => $category->id]);
        Product::factory()->create(['rating' => 4.5, 'category_id' => $category->id]);
        sleep(3);
        $response = $this->getJson('/api/products?sort=rating_desc');

        $response->assertOk();
        $this->assertEquals('4.50', $response->json('data.0.rating'));
    }

    public function test_sorts_by_newest(): void
    {
        $category = Category::factory()->create();
        $old = Product::factory()->create(['category_id' => $category->id]);
        sleep(1);
        $new = Product::factory()->create(['category_id' => $category->id]);
        sleep(1);
        $response = $this->getJson('/api/products?sort=newest');

        $response->assertOk();
        $this->assertEquals($new->id, $response->json('data.0.id'));
    }

    public function test_combines_multiple_filters(): void
    {
        $category = Category::factory()->create();

        Product::factory()->create([
            'name' => 'Laptop Pro',
            'price' => 1500,
            'in_stock' => true,
            'rating' => 4.5,
            'category_id' => $category->id,
        ]);

        Product::factory()->create([
            'name' => 'Laptop Basic',
            'price' => 800,
            'in_stock' => false,
            'rating' => 3.5,
            'category_id' => $category->id,
        ]);
        sleep(3);
        $response = $this->getJson(
            "/api/products?q=Laptop&price_from=1000&in_stock=1&rating_from=4.0&category_id={$category->id}"
        );

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Laptop Pro');
    }

    public function test_respects_per_page_parameter(): void
    {
        Product::factory()->count(50)->create();

        $response = $this->getJson('/api/products?per_page=5');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.per_page', 5);
    }

    // Валидация
    public function test_validates_invalid_sort(): void
    {
        $response = $this->getJson('/api/products?sort=invalid_sort');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('sort')
            ->assertJson([
                'message' => 'Validation failed',
                'errors' => [
                    'sort' => ['The selected sort is invalid.']
                ]
            ]);
    }

    public function test_validates_price_to_greater_than_price_from(): void
    {
        $response = $this->getJson('/api/products?price_from=200&price_to=100');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('price_to');
    }

    public function test_validates_negative_price_from(): void
    {
        $response = $this->getJson('/api/products?price_from=-10');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('price_from');
    }

    public function test_validates_negative_price_to(): void
    {
        $response = $this->getJson('/api/products?price_to=-10');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('price_to');
    }

    public function test_validates_invalid_category_id(): void
    {
        $response = $this->getJson('/api/products?category_id=99999');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('category_id');
    }

    public function test_validates_non_numeric_category_id(): void
    {
        $response = $this->getJson('/api/products?category_id=abc');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('category_id');
    }

    public function test_validates_invalid_in_stock(): void
    {
        $response = $this->getJson('/api/products?in_stock=2');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('in_stock')
            ->assertJson([
                'message' => 'Validation failed',
            ]);
    }

    public function test_validates_rating_from_below_zero(): void
    {
        $response = $this->getJson('/api/products?rating_from=-1');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('rating_from');
    }

    public function test_validates_rating_from_above_five(): void
    {
        $response = $this->getJson('/api/products?rating_from=6');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('rating_from');
    }

    public function test_validates_per_page_below_minimum(): void
    {
        $response = $this->getJson('/api/products?per_page=0');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');
    }

    public function test_validates_per_page_above_maximum(): void
    {
        $response = $this->getJson('/api/products?per_page=101');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');
    }

    public function test_validates_non_numeric_price_from(): void
    {
        $response = $this->getJson('/api/products?price_from=abc');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('price_from');
    }

    public function test_validates_non_numeric_rating_from(): void
    {
        $response = $this->getJson('/api/products?rating_from=abc');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('rating_from');
    }

    public function test_validates_search_query_max_length(): void
    {
        $longQuery = str_repeat('a', 256);

        $response = $this->getJson("/api/products?q={$longQuery}");

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('q');
    }

    public function test_filters_with_exact_price_match(): void
    {
        $category = Category::factory()->create();

        $product = Product::factory()->create(['price' => 100, 'category_id' => $category->id]);
        $product->searchable();
        sleep(1);
        $response = $this->getJson('/api/products?price_from=100&price_to=100');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_response_structure_includes_category(): void
    {
        $category = Category::factory()->create(['name' => 'Electronics']);
        Product::factory()->create(['category_id' => $category->id]);
        sleep(2);
        $response = $this->getJson('/api/products');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'price',
                        'in_stock',
                        'rating',
                        'category' => ['id', 'name'],
                        'created_at',
                        'updated_at',
                    ]
                ]
            ])
            ->assertJsonPath('data.0.category.name', 'Electronics');
    }

    public function test_pagination_metadata_is_correct(): void
    {
        Product::factory()->count(50)->create();

        sleep(3);
        $response = $this->getJson('/api/products?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'from', 'last_page', 'per_page', 'to', 'total'],
            ])
            ->assertJsonPath('meta.total', 50)
            ->assertJsonPath('meta.last_page', 5);
    }

    public function test_search_finds_partial_matches(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['name' => 'Laptop Pro Gaming', 'category_id' => $category->id]);
        $product->searchable();
        sleep(3);
        $response = $this->getJson('/api/products?q=Gaming');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
