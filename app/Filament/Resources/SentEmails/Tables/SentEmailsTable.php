<?php

namespace App\Filament\Resources\SentEmails\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SentEmailsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('subject')
                    ->searchable()
                    ->wrap()
                    ->placeholder('(no subject)'),
                TextColumn::make('to')
                    ->label('Recipients')
                    ->badge()
                    ->searchable(),
                TextColumn::make('mailer')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('sent_at')
                    ->label('Sent')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('sent_at', 'desc')
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
