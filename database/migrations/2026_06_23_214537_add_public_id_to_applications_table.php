<?php

use App\Models\Application;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->ulid('public_id')->nullable()->after('id');
        });

        // Backfill any existing rows before enforcing uniqueness.
        Application::query()->whereNull('public_id')->each(function (Application $application): void {
            $application->forceFill(['public_id' => (string) Str::ulid()])->saveQuietly();
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->unique('public_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropUnique(['public_id']);
            $table->dropColumn('public_id');
        });
    }
};
