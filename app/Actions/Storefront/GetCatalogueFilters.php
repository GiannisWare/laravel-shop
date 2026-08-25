<?php

namespace App\Actions\Storefront;

use App\Enums\ProductAvailability;
use App\Enums\ProductSort;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

final class GetCatalogueFilters
{
    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        return Cache::remember('storefront.catalogue-filters.v1', now()->addSeconds(60), function (): array {
            $publishedProducts = fn (Builder $query): Builder => $query
                ->where('products.is_published', true)
                ->where('products.availability', '!=', ProductAvailability::Archived->value)
                ->whereNull('products.deleted_at');

            return [
                'categories' => Category::query()
                    ->whereNull('parent_id')
                    ->where('is_active', true)
                    ->withCount(['products' => $publishedProducts])
                    ->with([
                        'children' => fn ($query) => $query
                            ->where('is_active', true)
                            ->withCount(['products' => $publishedProducts]),
                    ])
                    ->orderBy('sort_order')
                    ->get(),
                'brands' => Brand::query()
                    ->where('is_active', true)
                    ->whereHas('products', $publishedProducts)
                    ->withCount(['products' => $publishedProducts])
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get(),
                'attributes' => Attribute::query()
                    ->where('is_filterable', true)
                    ->whereHas('values.products', $publishedProducts)
                    ->with([
                        'values' => fn ($query) => $query
                            ->whereHas('products', $publishedProducts)
                            ->withCount(['products' => $publishedProducts]),
                    ])
                    ->orderBy('sort_order')
                    ->get(),
                'availability' => collect([
                    ProductAvailability::InStock,
                    ProductAvailability::LowStock,
                    ProductAvailability::OutOfStock,
                ]),
                'sorts' => collect(ProductSort::cases()),
            ];
        });
    }
}
