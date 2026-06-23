<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Backfill the single backing unit + default form for whole-rental
     * properties that predate the PropertyObserver. Idempotent — the command
     * skips any whole rental that already has a unit.
     */
    public function up(): void
    {
        Artisan::call('properties:backfill-backing-units');
    }

    /**
     * Irreversible data backfill: the provisioned backing units are
     * indistinguishable from those created by the observer, so there is
     * nothing safe to remove here.
     */
    public function down(): void
    {
        //
    }
};
