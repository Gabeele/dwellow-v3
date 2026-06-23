<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\Role;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),
                DateTimePicker::make('email_verified_at'),
                Select::make('roles')
                    ->label('Roles')
                    ->multiple()
                    ->options(Collection::make(Role::cases())->mapWithKeys(
                        fn (Role $role): array => [$role->value => $role->label()],
                    ))
                    ->dehydrated(false)
                    ->helperText('Roles control what the user can do across Dwellow.'),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->helperText('Leave blank to keep the current password.')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create'),
            ]);
    }
}
