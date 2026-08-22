<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class CatalogStats extends StatsOverviewWidget
{
    protected ?string $heading = 'Catalog pulse';

    protected ?string $description = 'The key signals staff need before working through the catalog.';

    protected int|array|null $columns = 3;

    protected static bool $isLazy = false;

    /** @return array<Stat> */
    protected function getStats(): array
    {
        $productCount = Product::query()->count();
        $publishedCount = Product::query()->published()->count();
        $createdThisMonth = Product::query()
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
        $publishedPercentage = $productCount > 0
            ? (int) round(($publishedCount / $productCount) * 100)
            : 0;
        $categoryCount = Category::query()->count();
        $brandCount = Brand::query()->count();
        $attributeCount = Attribute::query()->count();

        return [
            Stat::make('Catalog products', number_format($productCount))
                ->description("{$createdThisMonth} added this month")
                ->descriptionIcon('phosphor-trend-up')
                ->icon('phosphor-package')
                ->color('primary')
                ->url(ProductResource::getUrl()),
            Stat::make('Published', number_format($publishedCount))
                ->description("{$publishedPercentage}% of the catalog is visible")
                ->descriptionIcon('phosphor-globe-hemisphere-west')
                ->icon('phosphor-check-circle')
                ->color($publishedPercentage === 100 ? 'success' : 'primary')
                ->url(ProductResource::getUrl()),
            Stat::make('Categories', number_format($categoryCount))
                ->description("{$brandCount} brands · {$attributeCount} filters")
                ->descriptionIcon('phosphor-tree-structure')
                ->icon('phosphor-folders')
                ->color('primary')
                ->url(CategoryResource::getUrl()),
        ];
    }
}
