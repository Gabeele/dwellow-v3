<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\ApplicationFormController;
use App\Http\Controllers\ApplicationLinkController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\PublicScreeningController;
use App\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return Inertia::render('Welcome', [
        'seo' => [
            'title' => 'Dwellow — Tenant screening for small landlords',
            'description' => 'Dwellow turns every rental application into a clear, comparable Score — reading documents, checking references, and ranking applicants against your criteria. No bureau accounts, no spreadsheets.',
            'url' => route('home'),
            'image' => asset('apple-touch-icon.png'),
        ],
        'steps' => [
            [
                'title' => 'Add your property',
                'description' => 'List a property and its units in a couple of minutes.',
            ],
            [
                'title' => 'Build the application',
                'description' => 'Customize a form for each unit — ask exactly what you need.',
            ],
            [
                'title' => 'Share one link',
                'description' => 'Applicants apply and upload documents — no account required.',
            ],
            [
                'title' => 'Let AI do the legwork',
                'description' => 'Dwellow reads every submission, emails references, and scores it.',
            ],
            [
                'title' => 'Compare and decide',
                'description' => 'Review applicants side by side and pick the right tenant.',
            ],
        ],
        'features' => [
            [
                'title' => 'Document-based, not bureau-based',
                'description' => 'Applicants provide their own documents, so you skip credit-bureau accounts and the compliance overhead.',
            ],
            [
                'title' => 'References, handled',
                'description' => 'Dwellow emails references for you and folds their responses into the Score.',
            ],
            [
                'title' => 'One Score, easy to compare',
                'description' => 'Every applicant gets a consistent Score against your criteria — no gut feel, no apples to oranges.',
            ],
        ],
        'roadmap' => [
            [
                'phase' => 'Now',
                'title' => 'Tenant screening',
                'current' => true,
                'items' => [
                    'Custom application forms per unit',
                    'Link-only applicants, no accounts',
                    'Automated references and AI scoring',
                    'Compare-and-decide dashboard',
                ],
            ],
            [
                'phase' => 'Next',
                'title' => 'Best-in-class screening',
                'current' => false,
                'items' => [
                    'Reusable form templates',
                    'Portfolio-wide applicant view',
                    'Optional verified bureau checks',
                    'Landlord subscriptions',
                ],
            ],
            [
                'phase' => 'Later',
                'title' => 'The full rental lifecycle',
                'current' => false,
                'items' => [
                    'Leases and onboarding',
                    'Online rent collection',
                    'Maintenance requests',
                    'Per-property accounting',
                ],
            ],
        ],
    ]);
})->name('home');

// Public applicant flow — no account; the link is resolved by its unguessable token.
// These endpoints are account-free, so a per-IP throttle is the floor of abuse
// protection (paired with the honeypot in StoreApplicationRequest).
Route::middleware('throttle:30,1')
    ->get('screening/{link:token}', [PublicScreeningController::class, 'show'])
    ->name('screening.show');
Route::middleware('throttle:10,1')
    ->post('screening/{link:token}', [PublicScreeningController::class, 'store'])
    ->name('screening.store');
Route::get('screening/{link:token}/submitted', [PublicScreeningController::class, 'submitted'])->name('screening.submitted');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('properties', PropertyController::class);
    Route::resource('properties.units', UnitController::class)
        ->shallow()
        ->only(['create', 'store', 'edit', 'update', 'destroy']);

    Route::get('units/{unit}/form', [ApplicationFormController::class, 'edit'])->name('units.form.edit');
    Route::put('units/{unit}/form', [ApplicationFormController::class, 'update'])->name('units.form.update');

    Route::get('applications', [ApplicationController::class, 'indexAll'])->name('applications.index');
    Route::get('applications/export', [ApplicationController::class, 'exportAll'])->name('applications.export');

    Route::get('units/{unit}/applicants', [ApplicationController::class, 'index'])->name('units.applicants.index');
    Route::get('applicants/{application}', [ApplicationController::class, 'show'])->name('applicants.show');
    Route::put('applicants/{application}', [ApplicationController::class, 'update'])->name('applicants.update');
    Route::delete('applicants/{application}', [ApplicationController::class, 'destroy'])->name('applicants.destroy');

    Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');

    Route::post('units/{unit}/links', [ApplicationLinkController::class, 'store'])->name('units.links.store');
    Route::put('links/{link}', [ApplicationLinkController::class, 'update'])->name('links.update');
    Route::delete('links/{link}', [ApplicationLinkController::class, 'destroy'])->name('links.destroy');
});

require __DIR__.'/settings.php';
