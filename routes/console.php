<?php

use App\Models\ApplicationDraft;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Clear out abandoned screening drafts (and their uploaded files) so storage
// doesn't grow with applications that were started but never submitted.
Schedule::command('model:prune', ['--model' => [ApplicationDraft::class]])->daily();

// The lean landlord onboarding sequence: a Day-2 adaptive nudge and a Day-10
// check-in, sent to landlords who haven't yet scored an applicant.
Schedule::command('dwellow:send-onboarding-emails')->daily();
