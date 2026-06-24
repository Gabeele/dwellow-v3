<?php

namespace App\Filament\Resources\Applications\RelationManagers;

use App\Filament\Actions\DownloadDocumentAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Number;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Documents';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('original_name')
            ->columns([
                TextColumn::make('original_name')
                    ->label('File')
                    ->searchable(),
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
            ->recordActions([
                DownloadDocumentAction::make(),
            ]);
    }
}
