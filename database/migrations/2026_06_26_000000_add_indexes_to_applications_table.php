<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            // Serves the landlord portfolio query, which filters by status and
            // orders by submitted_at, as well as status-only dashboard counts
            // (via the leftmost-prefix of the composite index).
            $table->index(['status', 'submitted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropIndex(['status', 'submitted_at']);
        });
    }
};
