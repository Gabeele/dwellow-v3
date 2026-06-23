<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    /**
     * Determine whether the user can view the document.
     */
    public function view(User $user, Document $document): bool
    {
        return $this->owns($user, $document);
    }

    /**
     * Determine whether the user can download the document.
     */
    public function download(User $user, Document $document): bool
    {
        return $this->owns($user, $document);
    }

    /**
     * A landlord may only act on documents for units of properties they own.
     */
    private function owns(User $user, Document $document): bool
    {
        return $user->isLandlord() && $document->application->unit->property->landlord_id === $user->id;
    }
}
