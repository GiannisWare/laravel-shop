<?php

namespace App\Filament\Resources\Attributes\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ValuesRelationManager extends RelationManager
{
    protected static string $relationship = 'values';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('value')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255),
                TextInput::make('swatch_hex')
                    ->label('Swatch hex')
                    ->regex('/^#[0-9A-Fa-f]{6}$/')
                    ->placeholder('#FF0000'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('value')
            ->columns([
                TextColumn::make('value')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('swatch_hex')
                    ->label('Swatch'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
