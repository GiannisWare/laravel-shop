<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\ProductAvailability;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

final class RecentlyUpdatedProducts extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static bool $isLazy = false;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recently updated')
            ->description('Latest records changed manually or through a spreadsheet import.')
            ->query(Product::query()->with(['category', 'brand']))
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->description(fn (Product $record): string => $record->source_id ?? $record->barcode ?? 'No reference ID')
                    ->weight(FontWeight::Medium)
                    ->searchable(),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->placeholder('Uncategorized'),
                TextColumn::make('brand.name')
                    ->label('Brand')
                    ->placeholder('No brand')
                    ->toggleable(),
                TextColumn::make('availability')
                    ->badge()
                    ->formatStateUsing(fn (ProductAvailability $state): string => $state->label())
                    ->color(fn (ProductAvailability $state): string => match ($state) {
                        ProductAvailability::InStock => 'success',
                        ProductAvailability::LowStock => 'warning',
                        ProductAvailability::OutOfStock => 'danger',
                        ProductAvailability::Archived => 'gray',
                    }),
                IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean()
                    ->trueIcon('phosphor-check-circle-fill')
                    ->falseIcon('phosphor-x-circle'),
                TextColumn::make('updated_at')
                    ->label('Last change')
                    ->since()
                    ->sortable(),
            ])
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('No products yet')
            ->emptyStateDescription('Create a product manually or import a spreadsheet to begin.')
            ->emptyStateIcon('phosphor-package')
            ->recordUrl(fn (Product $record): string => ProductResource::getUrl('edit', ['record' => $record]))
            ->headerActions([
                Action::make('viewAll')
                    ->label('View all products')
                    ->icon('phosphor-arrow-right')
                    ->color('gray')
                    ->url(ProductResource::getUrl()),
            ])
            ->recordActions([
                EditAction::make()
                    ->icon('phosphor-pencil-simple'),
            ]);
    }
}
