<?php

namespace App\Filament\Resources\Applications\Schemas;

use App\Enums\ApplicationStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ApplicationForm
{
    /**
     * Admins manage an application's review state — its status and private
     * notes. The applicant's submission (answers, documents, snapshot) is an
     * immutable record and is never editable here.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('status')
                    ->required()
                    ->options(fn (): array => collect(ApplicationStatus::cases())
                        ->mapWithKeys(fn (ApplicationStatus $status): array => [$status->value => $status->label()])
                        ->all()),
                Textarea::make('landlord_notes')
                    ->label('Notes')
                    ->rows(5)
                    ->maxLength(5000)
                    ->columnSpanFull(),
            ]);
    }
}
