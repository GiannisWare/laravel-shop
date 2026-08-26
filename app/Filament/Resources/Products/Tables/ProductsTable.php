<?php

namespace App\Filament\Resources\Products\Tables;

use App\Enums\ProductAvailability;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('images')
                    ->label('Image')
                    ->collection('images')
                    ->conversion('thumb')
                    ->circular()
                    ->size(48),
                TextColumn::make('name')
                    ->placeholder('Uncatalogued product')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('source_id')
                    ->label('Source ID')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('barcode')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('category.name')
                    ->sortable(),
                TextColumn::make('brand.name')
                    ->sortable(),
                TextColumn::make('availability')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof ProductAvailability ? $state->label() : (string) $state),
                IconColumn::make('is_published')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('brand')
                    ->relationship('brand', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('availability')
                    ->options(collect(ProductAvailability::cases())
                        ->mapWithKeys(fn (ProductAvailability $availability): array => [
                            $availability->value => $availability->label(),
                        ])),
                TernaryFilter::make('is_published'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
