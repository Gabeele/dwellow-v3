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
