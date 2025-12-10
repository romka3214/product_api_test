<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->decimal('price', 10)->unsigned();
            $table->foreignId('category_id')
                ->constrained()
                ->onDelete('cascade');
            $table->boolean('in_stock')->default(true);
            $table->decimal('rating', 3)
                ->unsigned()
                ->default(0);
            $table->timestamps();

            $table->index(['category_id', 'in_stock']);
            $table->index(['price', 'rating']);
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
