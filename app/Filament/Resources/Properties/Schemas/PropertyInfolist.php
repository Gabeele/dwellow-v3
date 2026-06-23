<?php

namespace App\Filament\Resources\Properties\Schemas;

use App\Enums\OccupancyStatus;
use App\Enums\PropertyType;
use App\Enums\RentalType;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PropertyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->placeholder('(unnamed)'),
                TextEntry::make('landlord.name')
                    ->label('Landlord')
                    ->placeholder('—'),
                TextEntry::make('address_line1')
                    ->label('Address line 1'),
                TextEntry::make('address_line2')
                    ->label('Address line 2')
                    ->placeholder('—'),
                TextEntry::make('city'),
                TextEntry::make('region'),
                TextEntry::make('postal_code')
                    ->label('Postal code'),
                TextEntry::make('country'),
                TextEntry::make('type')
                    ->badge()
                    ->formatStateUsing(fn (PropertyType $state): string => $state->label()),
                TextEntry::make('rental_type')
                    ->label('Rental type')
                    ->badge()
                    ->formatStateUsing(fn (RentalType $state): string => $state->label()),
                TextEntry::make('status')
                    ->badge()
                    ->formatStateUsing(fn (OccupancyStatus $state): string => $state->label()),
                TextEntry::make('bedrooms')
                    ->placeholder('—'),
                TextEntry::make('bathrooms')
                    ->placeholder('—'),
                TextEntry::make('rent_amount')
                    ->label('Rent amount')
                    ->money('CAD')
                    ->placeholder('—'),
                TextEntry::make('created_at')
                    ->dateTime(),
            ]);
    }
}
