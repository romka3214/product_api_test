<?php

namespace App\V2\Products\Domain\Models;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Scout\Searchable;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory, Searchable;

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

    /**
     * Get the indexable data array for the model.
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => (float)$this->price,
            'rating' => (float)($this->rating ?? 0),
            'in_stock' => (bool)$this->in_stock,
            'category_id' => (int)$this->category_id,
            'created_at' => $this->created_at?->timestamp,
        ];
    }

    /**
     * Modify the query used to retrieve models when making all searchable.
     */
    protected function makeAllSearchableUsing($query)
    {
        return $query->with('category');
    }

    public function searchableOptions(): array
    {
        return [
            'sortableAttributes' => [
                'id',
                'name',
                'price',
                'rating',
                'in_stock',
                'category_id',
                'created_at',
            ],
        ];
    }
}
