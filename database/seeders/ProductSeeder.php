<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::factory()->count(50)->create();
        $categoryIds = $categories->pluck('id')->toArray();

        $batchSize = 1000;
        $totalProducts = 1000000;
        $batches = ceil($totalProducts / $batchSize);

        $bar = $this->command->getOutput()->createProgressBar($batches);
        $bar->start();

        for ($i = 0; $i < $batches; $i++) {
            $products = [];
            $now = now();

            for ($j = 0; $j < $batchSize; $j++) {
                $products[] = [
                    'name' => fake()->words(3, true),
                    'price' => fake()->randomFloat(2, 10, 10000),
                    'rating' => fake()->randomFloat(2, 0, 5),
                    'in_stock' => fake()->boolean(80),
                    'category_id' => fake()->randomElement($categoryIds),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('products')->insert($products);

            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();

        $this->command->warn('Run "php artisan scout:import App\\\Models\\\Product" to index products in Meilisearch');
    }
}
