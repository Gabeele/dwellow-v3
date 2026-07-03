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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            // The subject the activity happened to (an Application today).
            $table->morphs('subject');
            // The user who caused it; null for system/AI events (e.g. analysis).
            $table->foreignId('causer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type');
            $table->string('description');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
