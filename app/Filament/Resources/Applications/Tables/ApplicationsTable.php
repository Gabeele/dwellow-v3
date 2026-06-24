<?php

namespace App\Filament\Resources\Applications\Tables;

use App\Enums\ApplicationStatus;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ApplicationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('unit.property'))
            ->columns([
                TextColumn::make('applicant_name')
                    ->label('Applicant')
                    ->state(fn ($record): string => trim("{$record->applicant_first_name} {$record->applicant_last_name}"))
                    ->description(fn ($record): ?string => $record->applicant_email)
                    ->searchable(['applicant_first_name', 'applicant_last_name', 'applicant_email'])
                    ->sortable(['applicant_last_name']),
                TextColumn::make('unit.property.name')
                    ->label('Property')
                    ->formatStateUsing(fn ($state, $record): string => $state ?? $record->unit->property->address_line1)
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('unit.label')
                    ->label('Unit')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (ApplicationStatus $state): string => $state->label()),
                TextColumn::make('documents_count')
                    ->label('Documents')
                    ->counts('documents')
                    ->badge(),
                TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->defaultSort('submitted_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(fn (): array => collect(ApplicationStatus::cases())
                        ->mapWithKeys(fn (ApplicationStatus $status): array => [$status->value => $status->label()])
                        ->all()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
