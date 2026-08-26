<?php

namespace App\Filament\Resources\Attributes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;

class AttributesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('values_count')
                    ->counts('values')
                    ->label('Values'),
                IconColumn::make('is_filterable')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_filterable'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->reorderAnimationDuration(200)
            ->afterReordering(fn (): bool => Cache::forget('storefront.catalogue-filters.v1'))
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
