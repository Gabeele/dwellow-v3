<?php

namespace App\Filament\Resources\SentEmails;

use App\Filament\Resources\SentEmails\Pages\ListSentEmails;
use App\Filament\Resources\SentEmails\Pages\ViewSentEmail;
use App\Filament\Resources\SentEmails\Schemas\SentEmailInfolist;
use App\Filament\Resources\SentEmails\Tables\SentEmailsTable;
use App\Models\SentEmail;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SentEmailResource extends Resource
{
    protected static ?string $model = SentEmail::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?string $recordTitleAttribute = 'subject';

    protected static ?string $navigationLabel = 'Sent Emails';

    public static function infolist(Schema $schema): Schema
    {
        return SentEmailInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SentEmailsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSentEmails::route('/'),
            'view' => ViewSentEmail::route('/{record}'),
        ];
    }
}
