<?php

namespace App\Filament\Resources\Documents\Tables;

use App\Filament\Actions\DownloadDocumentAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;

class DocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('application'))
            ->columns([
                TextColumn::make('original_name')
                    ->label('File')
                    ->searchable(),
                TextColumn::make('application.applicant_email')
                    ->label('Applicant')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('field_key')
                    ->label('Field')
                    ->badge(),
                TextColumn::make('mime_type')
                    ->label('Type')
                    ->placeholder('—'),
                TextColumn::make('size')
                    ->formatStateUsing(fn (?int $state): string => $state === null ? '—' : Number::fileSize($state)),
                TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                DownloadDocumentAction::make(),
            ]);
    }
}
