<?php

namespace App\Http\Controllers;

use App\Models\ApplicationLink;
use Inertia\Inertia;
use Inertia\Response;

class PublicScreeningController extends Controller
{
    /**
     * Show the public application page for a shareable link, gating on the link's open state.
     *
     * Applicants have no account; the link is resolved by its unguessable token. When the link
     * is no longer open (revoked / expired / not accepting) a friendly closed state is rendered
     * instead of the form.
     */
    public function show(ApplicationLink $link): Response
    {
        $link->load(['unit.property', 'unit.applicationForm']);

        $isOpen = $link->isOpen();
        $unit = $link->unit;
        $property = $unit->property;

        return Inertia::render('screening/Apply', [
            'isOpen' => $isOpen,
            'unit' => [
                'label' => $unit->label,
                'address' => [
                    'line1' => $property->address_line1,
                    'line2' => $property->address_line2,
                    'city' => $property->city,
                    'region' => $property->region,
                    'postal_code' => $property->postal_code,
                    'country' => $property->country,
                ],
            ],
            'fields' => $isOpen ? ($unit->applicationForm?->fields ?? []) : [],
        ]);
    }
}
