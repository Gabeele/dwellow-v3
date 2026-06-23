<?php

namespace App\Filament\Resources\Properties\RelationManagers;

use App\Enums\OccupancyStatus;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UnitsRelationManager extends RelationManager
{
    protected static string $relationship = 'units';

    protected static ?string $title = 'Units';

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('label'),
                TextEntry::make('bedrooms')
                    ->placeholder('—'),
                TextEntry::make('bathrooms')
                    ->placeholder('—'),
                TextEntry::make('rent_amount')
                    ->money('usd')
                    ->placeholder('—'),
                TextEntry::make('status')
                    ->badge()
                    ->formatStateUsing(fn (OccupancyStatus $state): string => $state->label()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->columns([
                TextColumn::make('label')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('bedrooms')
                    ->numeric()
                    ->placeholder('—'),
                TextColumn::make('bathrooms')
                    ->numeric(decimalPlaces: 1)
                    ->placeholder('—'),
                TextColumn::make('rent_amount')
                    ->money('usd')
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (OccupancyStatus $state): string => $state->label()),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
