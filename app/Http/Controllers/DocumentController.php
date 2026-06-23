<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    /**
     * Stream a document from its private disk to the owning landlord only. These files
     * live on the non-public `local` disk, so they are never served from a public URL.
     */
    public function download(Document $document): StreamedResponse
    {
        $this->authorize('download', $document);

        return Storage::disk($document->disk)->download($document->path, $document->original_name);
    }
}
