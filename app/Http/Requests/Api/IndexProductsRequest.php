<?php

namespace App\Http\Requests\Api;

use App\Enums\ProductAvailability;
use App\Enums\ProductSort;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class IndexProductsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:24'],
            'q' => ['sometimes', 'nullable', 'string', 'max:100'],
            'category' => ['sometimes', 'nullable', 'string', 'max:255'],
            'brand' => ['sometimes', 'nullable', 'string', 'max:255'],
            'attributes' => ['sometimes', 'array', 'max:20'],
            'attributes.*' => ['string', 'max:511', 'regex:/^[a-z0-9-]+:[a-z0-9-]+$/'],
            'availability' => [
                'sometimes',
                'nullable',
                Rule::in([
                    ProductAvailability::InStock->value,
                    ProductAvailability::LowStock->value,
                    ProductAvailability::OutOfStock->value,
                ]),
            ],
            'sort' => ['sometimes', 'nullable', Rule::enum(ProductSort::class)],
        ];
    }
}
