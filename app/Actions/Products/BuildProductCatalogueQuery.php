<?php

namespace App\Actions\Products;

use App\Enums\ProductSort;
use App\Models\AttributeValue;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

final class BuildProductCatalogueQuery
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Product>
     */
    public function handle(array $filters): Builder
    {
        $query = Product::query()
            ->published()
            ->with(['brand', 'category', 'media']);

        $search = trim((string) Arr::get($filters, 'q', ''));
        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhereHas('brand', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('category', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"));
            });
        }

        $category = trim((string) Arr::get($filters, 'category', ''));
        if ($category !== '') {
            $query->whereHas('category', function (Builder $query) use ($category): void {
                $query->where('slug', $category)
                    ->orWhereHas('parent', fn (Builder $query) => $query->where('slug', $category));
            });
        }

        $brand = trim((string) Arr::get($filters, 'brand', ''));
        if ($brand !== '') {
            $query->whereHas('brand', fn (Builder $query) => $query->where('slug', $brand));
        }

        $availability = trim((string) Arr::get($filters, 'availability', ''));
        if ($availability !== '') {
            $query->where('availability', $availability);
        }

        $this->applyAttributeFilters($query, Arr::wrap(Arr::get($filters, 'attributes', [])));

        return match (ProductSort::tryFrom((string) Arr::get($filters, 'sort', 'recommended'))) {
            ProductSort::NameDescending => $query->orderByDesc('name')->orderByDesc('id'),
            ProductSort::Newest => $query->latest('created_at')->orderByDesc('id'),
            default => $query->orderBy('name')->orderBy('id'),
        };
    }

    /**
     * Values within one attribute use OR logic. Different attributes use AND logic.
     *
     * @param  Builder<Product>  $query
     * @param  array<int, mixed>  $tokens
     */
    private function applyAttributeFilters(Builder $query, array $tokens): void
    {
        $requested = collect($tokens)
            ->filter(fn (mixed $token): bool => is_string($token) && str_contains($token, ':'))
            ->map(fn (string $token): array => array_pad(explode(':', $token, 2), 2, ''))
            ->filter(fn (array $parts): bool => $parts[0] !== '' && $parts[1] !== '');

        if ($requested->isEmpty()) {
            return;
        }

        $attributeSlugs = $requested->pluck(0)->unique();
        $valueSlugs = $requested->pluck(1)->unique();

        /** @var Collection<int, AttributeValue> $values */
        $values = AttributeValue::query()
            ->with('attribute:id,slug')
            ->whereIn('slug', $valueSlugs)
            ->whereHas('attribute', fn (Builder $query) => $query->whereIn('slug', $attributeSlugs)->where('is_filterable', true))
            ->get()
            ->filter(fn (AttributeValue $value): bool => $requested->contains(
                fn (array $parts): bool => $parts[0] === $value->attribute->slug && $parts[1] === $value->slug,
            ));

        foreach ($values->groupBy('attribute_id') as $attributeValues) {
            $valueIds = $attributeValues->pluck('id')->all();
            $query->whereHas('attributeValues', fn (Builder $query) => $query->whereKey($valueIds));
        }
    }
}
