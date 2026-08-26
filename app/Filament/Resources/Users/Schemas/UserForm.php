<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->autofocus(),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('password')
                    ->password()
                    ->revealable(filament()->arePasswordsRevealable())
                    ->autocomplete('new-password')
                    ->rule(Password::default())
                    ->same('passwordConfirmation')
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->live(),
                TextInput::make('passwordConfirmation')
                    ->label('Confirm password')
                    ->password()
                    ->revealable(filament()->arePasswordsRevealable())
                    ->autocomplete('new-password')
                    ->requiredWith('password')
                    ->dehydrated(false),
                Toggle::make('is_super_admin')
                    ->label('Super admin')
                    ->helperText('Super admins can create, edit, and revoke other administrators.')
                    ->default(false)
                    ->disabled(fn (?User $record): bool => $record?->getKey() === Filament::auth()->id()),
            ]);
    }
}
