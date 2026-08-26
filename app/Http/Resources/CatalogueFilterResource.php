<?php

namespace App\Http\Resources;

use App\Enums\ProductAvailability;
use App\Enums\ProductSort;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CatalogueFilterResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'categories' => $this->resource['categories']->map(fn (Category $category): array => [
                'name' => $category->name,
                'slug' => $category->slug,
                'products_count' => $category->products_count + $category->children->sum('products_count'),
                'children' => $category->children->map(fn (Category $child): array => [
                    'name' => $child->name,
                    'slug' => $child->slug,
                    'products_count' => $child->products_count,
                ])->all(),
            ])->all(),
            'brands' => $this->resource['brands']->map(fn (Brand $brand): array => [
                'name' => $brand->name,
                'slug' => $brand->slug,
                'products_count' => $brand->products_count,
            ])->all(),
            'attributes' => $this->resource['attributes']->map(fn (Attribute $attribute): array => [
                'name' => $attribute->name,
                'slug' => $attribute->slug,
                'values' => $attribute->values->map(fn (AttributeValue $value): array => [
                    'value' => $value->value,
                    'slug' => $value->slug,
                    'swatch_hex' => $value->swatch_hex,
                    'products_count' => $value->products_count,
                ])->all(),
            ])->all(),
            'availability' => $this->resource['availability']->map(fn (ProductAvailability $availability): array => [
                'value' => $availability->value,
                'label' => $availability->label(),
            ])->all(),
            'sorts' => $this->resource['sorts']->map(fn (ProductSort $sort): array => [
                'value' => $sort->value,
                'label' => $sort->label(),
            ])->all(),
        ];
    }
}
