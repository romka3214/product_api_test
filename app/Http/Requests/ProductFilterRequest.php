<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class ProductFilterRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => 'sometimes|string|max:255',
            'price_from' => 'sometimes|numeric|min:0',
            'price_to' => 'sometimes|numeric|min:0|gte:price_from',
            'category_id' => 'sometimes|integer|exists:categories,id',
            'in_stock' => 'sometimes|in:0,1,true,false',
            'rating_from' => 'sometimes|numeric|between:0,5',
            'sort' => ['sometimes', 'string', Rule::in(['price_asc', 'price_desc', 'rating_desc', 'newest'])],
            'per_page' => 'sometimes|integer|between:1,100',
        ];
    }

    public function getSearch(): ?string
    {
        return $this->input('q');
    }

    public function getPriceFrom(): ?float
    {
        return $this->has('price_from') ? (float) $this->input('price_from') : null;
    }

    public function getPriceTo(): ?float
    {
        return $this->has('price_to') ? (float) $this->input('price_to') : null;
    }

    public function getCategoryId(): ?int
    {
        return $this->has('category_id') ? (int) $this->input('category_id') : null;
    }

    public function getInStock(): ?bool
    {
        if (!$this->has('in_stock')) {
            return null;
        }

        $value = $this->input('in_stock');

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function getRatingFrom(): ?float
    {
        return $this->has('rating_from') ? (float) $this->input('rating_from') : null;
    }

    public function getSort(): string
    {
        return $this->input('sort', 'newest');
    }

    public function getPerPage(): int
    {
        return (int) $this->input('per_page', 15);
    }
}
