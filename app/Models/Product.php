<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'category_id',
        'in_stock',
        'rating',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'in_stock' => 'boolean',
        'rating' => 'decimal:2',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeSearch(Builder $query, ?string $search): void
    {
        if (!$search) return;

        $query->where('name', 'LIKE', "%{$search}%");
    }

    public function scopePriceBetween(Builder $query, ?float $from, ?float $to): void
    {
        if ($from !== null) {
            $query->where('price', '>=', $from);
        }

        if ($to !== null) {
            $query->where('price', '<=', $to);
        }
    }

    public function scopeInCategory(Builder $query, ?int $categoryId): void
    {
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
    }

    public function scopeAvailable(Builder $query, ?bool $inStock): void
    {
        if ($inStock !== null) {
            $query->where('in_stock', $inStock);
        }
    }

    public function scopeMinRating(Builder $query, ?float $rating): void
    {
        if ($rating !== null) {
            $query->where('rating', '>=', $rating);
        }
    }
}
