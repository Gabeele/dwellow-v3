<?php

namespace App\Filament\Actions;

use App\Models\Document;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Stream a {@see Document} from its private disk for an admin.
 *
 * Admins are not the owning landlord, so the policy-gated public download route
 * is unavailable to them; panel access already authorizes them, so they
 * download straight from the disk here.
 */
class DownloadDocumentAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'download';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->icon(Heroicon::OutlinedArrowDownTray)
            ->action(fn (Document $record): StreamedResponse => Storage::disk($record->disk)
                ->download($record->path, $record->original_name));
    }
}
