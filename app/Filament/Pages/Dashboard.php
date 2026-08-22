<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Filament\Widgets\CatalogCoverageChart;
use App\Filament\Widgets\CatalogStats;
use App\Filament\Widgets\RecentlyUpdatedProducts;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Enums\Width;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;

final class Dashboard extends BaseDashboard
{
    protected static string|BackedEnum|null $navigationIcon = 'phosphor-squares-four';

    protected static ?string $navigationLabel = 'Overview';

    public function getTitle(): string
    {
        return 'Catalog overview';
    }

    public function getHeading(): string
    {
        $name = Filament::auth()->user()?->name;
        $firstName = filled($name) ? str((string) $name)->before(' ')->toString() : 'there';

        return "Welcome back, {$firstName}";
    }

    public function getSubheading(): string
    {
        return 'A clear view of catalog readiness, structure, and recent activity.';
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    /**
     * @return array<class-string<Widget>|WidgetConfiguration>
     */
    public function getWidgets(): array
    {
        return [
            CatalogStats::class,
            CatalogCoverageChart::class,
            RecentlyUpdatedProducts::class,
        ];
    }

    /** @return array<string, int|null> */
    public function getColumns(): array
    {
        return [
            'default' => 1,
            'xl' => 12,
        ];
    }

    /** @return array<Action> */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('browseCatalog')
                ->label('Browse catalog')
                ->icon('phosphor-arrow-right')
                ->color('gray')
                ->url(ProductResource::getUrl()),
            Action::make('addProduct')
                ->label('Add product')
                ->icon('phosphor-plus-circle')
                ->url(ProductResource::getUrl('create')),
        ];
    }
}
