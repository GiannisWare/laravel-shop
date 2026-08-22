<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Category;
use Filament\Widgets\ChartWidget;

final class CatalogCoverageChart extends ChartWidget
{
    protected ?string $heading = 'Catalog coverage';

    protected ?string $description = 'Product records by category. Thin sections are easy to spot at a glance.';

    protected ?string $maxHeight = '22rem';

    protected int|string|array $columnSpan = 'full';

    protected static bool $isLazy = false;

    /** @return array<string, mixed> */
    protected function getData(): array
    {
        $categories = Category::query()
            ->withCount('products')
            ->orderByDesc('products_count')
            ->orderBy('name')
            ->limit(12)
            ->get();

        if ($categories->isEmpty()) {
            return [];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Products',
                    'data' => $categories->pluck('products_count')->all(),
                    'backgroundColor' => '#f97316',
                    'borderColor' => '#fb923c',
                    'borderRadius' => 4,
                    'borderSkipped' => false,
                    'maxBarThickness' => 28,
                ],
            ],
            'labels' => $categories->pluck('name')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    /** @return array<string, mixed> */
    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }
}
