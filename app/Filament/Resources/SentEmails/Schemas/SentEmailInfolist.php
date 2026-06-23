<?php

namespace App\Filament\Resources\SentEmails\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SentEmailInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('subject')
                    ->placeholder('(no subject)'),
                TextEntry::make('sent_at')
                    ->label('Sent')
                    ->dateTime(),
                TextEntry::make('from')
                    ->placeholder('—'),
                TextEntry::make('mailer')
                    ->badge()
                    ->placeholder('—'),
                TextEntry::make('to')
                    ->label('Recipients')
                    ->badge(),
                TextEntry::make('cc')
                    ->label('Cc')
                    ->badge()
                    ->placeholder('—'),
                TextEntry::make('bcc')
                    ->label('Bcc')
                    ->badge()
                    ->placeholder('—'),
                TextEntry::make('body')
                    ->label('Body')
                    ->html()
                    ->placeholder('(empty)')
                    ->columnSpanFull(),
            ]);
    }
}
