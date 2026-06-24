<?php

namespace App\Filament\Resources\Applications\Schemas;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ApplicationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Applicant')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('applicant_first_name')
                            ->label('First name'),
                        TextEntry::make('applicant_last_name')
                            ->label('Last name'),
                        TextEntry::make('applicant_email')
                            ->label('Email')
                            ->copyable(),
                        TextEntry::make('applicant_phone')
                            ->label('Phone')
                            ->placeholder('—'),
                    ]),
                Section::make('Review')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn (ApplicationStatus $state): string => $state->label()),
                        TextEntry::make('public_id')
                            ->label('Reference')
                            ->copyable(),
                        TextEntry::make('submitted_at')
                            ->label('Submitted')
                            ->dateTime()
                            ->placeholder('—'),
                        TextEntry::make('status_changed_at')
                            ->label('Status changed')
                            ->dateTime()
                            ->placeholder('—'),
                        TextEntry::make('landlord_notes')
                            ->label('Notes')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),
                Section::make('Property & unit')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('unit.property.name')
                            ->label('Property')
                            ->formatStateUsing(fn ($state, Application $record): string => $state ?? $record->unit->property->address_line1),
                        TextEntry::make('unit.label')
                            ->label('Unit'),
                        TextEntry::make('unit.property.landlord.name')
                            ->label('Landlord')
                            ->placeholder('—'),
                        TextEntry::make('applicationLink.label')
                            ->label('Application link')
                            ->placeholder('—'),
                    ]),
                Section::make('Submitted answers')
                    ->collapsible()
                    ->schema([
                        TextEntry::make('answers_summary')
                            ->hiddenLabel()
                            ->listWithLineBreaks()
                            ->bulleted()
                            ->state(fn (Application $record): array => self::answerLines($record))
                            ->placeholder('No answers were submitted.'),
                    ]),
            ]);
    }

    /**
     * Flatten the immutable form snapshot + answers into readable "Label: value"
     * lines, so an admin sees exactly what the applicant submitted without
     * decoding the raw JSON.
     *
     * @return list<string>
     */
    private static function answerLines(Application $record): array
    {
        $answers = $record->answers ?? [];
        $lines = [];

        foreach ($record->form_snapshot ?? [] as $field) {
            $key = $field['key'] ?? null;

            if ($key === null) {
                continue;
            }

            $label = $field['label'] ?? $key;
            $lines[] = "{$label}: ".self::formatValue($answers[$key] ?? null);
        }

        return $lines;
    }

    /**
     * Render a single answer value (scalar, list, or structured reference) as a
     * compact human-readable string.
     */
    private static function formatValue(mixed $value): string
    {
        if (is_array($value)) {
            $parts = [];

            foreach ($value as $key => $item) {
                $parts[] = is_string($key) ? "{$key}: ".self::formatValue($item) : self::formatValue($item);
            }

            return implode(', ', array_filter($parts, fn (string $part): bool => $part !== ''));
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        return (string) ($value ?? '—');
    }
}
