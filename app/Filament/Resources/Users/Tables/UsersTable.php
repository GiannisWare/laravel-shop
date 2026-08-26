<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('role')
                    ->state(fn (User $record): string => $record->is_super_admin ? 'Super admin' : 'Admin')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Super admin' ? 'warning' : 'gray'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('is_super_admin')
                    ->label('Super admins'),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make()
                    ->icon('phosphor-pencil-simple'),
            ]);
    }
}
