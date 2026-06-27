<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\ApplicationFormController;
use App\Http\Controllers\ApplicationLinkController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\PublicScreeningController;
use App\Http\Controllers\PublicScreeningDraftController;
use App\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Route;

// Public marketing surface.
Route::get('/', [MarketingController::class, 'home'])->name('home');
Route::get('pricing', [MarketingController::class, 'pricing'])->name('pricing');
Route::get('docs', [MarketingController::class, 'docs'])->name('docs');
Route::get('roadmap', [MarketingController::class, 'roadmap'])->name('roadmap');

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

// Draft autosave so an applicant can resume after closing their browser. Keyed
// to a per-link cookie; throttled like the rest of the account-free flow.
Route::middleware('throttle:30,1')
    ->put('screening/{link:token}/draft', [PublicScreeningDraftController::class, 'save'])
    ->name('screening.draft.save');
Route::middleware('throttle:20,1')
    ->post('screening/{link:token}/draft/files/{fieldKey}', [PublicScreeningDraftController::class, 'storeFile'])
    ->name('screening.draft.file.store');
Route::middleware('throttle:20,1')
    ->delete('screening/{link:token}/draft/files/{fieldKey}', [PublicScreeningDraftController::class, 'destroyFile'])
    ->name('screening.draft.file.destroy');

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

    Route::get('properties/{property}/applicants', [ApplicationController::class, 'indexForProperty'])->name('properties.applicants.index');
    Route::get('units/{unit}/applicants', [ApplicationController::class, 'index'])->name('units.applicants.index');
    Route::get('applicants/{application}', [ApplicationController::class, 'show'])->name('applicants.show');
    Route::put('applicants/{application}', [ApplicationController::class, 'update'])->name('applicants.update');
    Route::post('applicants/{application}/approve', [ApplicationController::class, 'approve'])->name('applicants.approve');
    Route::post('applicants/{application}/reject', [ApplicationController::class, 'reject'])->name('applicants.reject');
    Route::delete('applicants/{application}', [ApplicationController::class, 'destroy'])->name('applicants.destroy');

    Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');

    Route::put('links/{link}', [ApplicationLinkController::class, 'update'])->name('links.update');
});

require __DIR__.'/settings.php';
