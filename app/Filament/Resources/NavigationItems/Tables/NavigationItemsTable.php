<?php

namespace App\Filament\Resources\NavigationItems\Tables;

use App\Enums\NavigationLocation;
use App\Models\NavigationItem;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

final class NavigationItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')->searchable()->sortable(),
                TextColumn::make('location')->badge()->formatStateUsing(fn ($state): string => $state->label()),
                TextColumn::make('parent.label')->label('Parent')->placeholder('Top level'),
                TextColumn::make('destination')->badge()->formatStateUsing(fn ($state): string => $state->label()),
                TextColumn::make('category.name')->placeholder('—'),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                SelectFilter::make('location')->options([
                    'header' => 'Header',
                    'footer' => 'Footer',
                ])->default('header')->selectablePlaceholder(false),
                SelectFilter::make('parent_scope')
                    ->label('Menu level')
                    ->options(fn (): array => ['__top' => 'Top-level items'] + NavigationItem::query()
                        ->whereNull('parent_id')
                        ->orderBy('location')
                        ->orderBy('sort_order')
                        ->get()
                        ->mapWithKeys(fn (NavigationItem $item): array => [
                            $item->getKey() => NavigationLocation::from((string) $item->getRawOriginal('location'))->label().' — '.$item->label,
                        ])
                        ->all())
                    ->query(function (Builder $query, array $data): Builder {
                        $parent = $data['value'] ?? '__top';

                        return $parent === '__top'
                            ? $query->whereNull('parent_id')
                            : $query->where('parent_id', (int) $parent);
                    })
                    ->default('__top')
                    ->selectablePlaceholder(false),
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
