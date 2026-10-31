<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reports against a post or a comment (FR-16, FR-26).
     * Polymorphic: reportable_type is App\Models\Post or App\Models\Comment.
     */
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();

            // The user who reported the content.
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();

            // reportable_type + reportable_id + an index on both.
            $table->morphs('reportable');

            $table->text('reason');

            $table->enum('status', ['pending', 'reviewed', 'dismissed'])->default('pending');

            $table->timestamps();

            // The moderation queue is filtered by status.
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
