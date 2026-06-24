<?php

namespace App\Filament\Resources\Documents\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Illuminate\Support\Number;

class DocumentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('original_name')
                    ->label('File'),
                TextEntry::make('field_key')
                    ->label('Field')
                    ->badge(),
                TextEntry::make('mime_type')
                    ->label('Type')
                    ->placeholder('—'),
                TextEntry::make('size')
                    ->formatStateUsing(fn (?int $state): string => $state === null ? '—' : Number::fileSize($state)),
                TextEntry::make('application.applicant_email')
                    ->label('Applicant')
                    ->placeholder('—'),
                TextEntry::make('application.unit.label')
                    ->label('Unit')
                    ->placeholder('—'),
                TextEntry::make('created_at')
                    ->label('Uploaded')
                    ->dateTime(),
            ]);
    }
}
