<?php

namespace App\Filament\Resources\HeroSlides\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;

final class HeroSlidesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('image')
                    ->collection('image')
                    ->conversion('hero')
                    ->square(),
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('eyebrow')->placeholder('—'),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->reorderAnimationDuration(200)
            ->afterReordering(fn (): bool => Cache::forget('storefront.configuration.v1'))
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
