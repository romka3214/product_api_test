<?php

namespace App\V2\Products\Presentation\Http\Requests;

use App\V2\Products\Application\DTO\ProductFilterDTO;
use App\V2\Products\Domain\Enums\ProductSortType;
use App\V2\Shared\Traits\HTTP\Requests\ValidationErrors;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductFilterRequest extends FormRequest
{
    use ValidationErrors;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['sometimes', 'string', 'max:255'],
            'price_from' => ['sometimes', 'numeric', 'min:0'],
            'price_to' => ['sometimes', 'numeric', 'min:0', 'gte:price_from'],
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'in_stock' => ['sometimes', 'in:0,1,true,false'],
            'rating_from' => ['sometimes', 'numeric', 'between:0,5'],
            'sort' => ['sometimes', 'string', Rule::enum(ProductSortType::class)],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function toDTO(): ProductFilterDTO
    {
        return new ProductFilterDTO(
            search: $this->input('q'),
            priceFrom: $this->has('price_from') ? (float)$this->input('price_from') : null,
            priceTo: $this->has('price_to') ? (float)$this->input('price_to') : null,
            categoryId: $this->has('category_id') ? (int)$this->input('category_id') : null,
            inStock: $this->has('in_stock')
                ? filter_var($this->input('in_stock'), FILTER_VALIDATE_BOOLEAN)
                : null,
            ratingFrom: $this->has('rating_from') ? (float)$this->input('rating_from') : null,
            sort: $this->enum('sort', ProductSortType::class) ?? ProductSortType::default(),
            perPage: (int)$this->input('per_page', 15),
            page: (int)$this->input('page', 1),
        );
    }
}
