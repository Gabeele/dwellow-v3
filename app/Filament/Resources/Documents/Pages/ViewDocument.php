<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Actions\DownloadDocumentAction;
use App\Filament\Resources\Documents\DocumentResource;
use Filament\Resources\Pages\ViewRecord;

class ViewDocument extends ViewRecord
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DownloadDocumentAction::make(),
        ];
    }
}
