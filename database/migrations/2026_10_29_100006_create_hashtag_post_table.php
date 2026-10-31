<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivot table between posts and hashtags (FR-15).
     * A plain pivot: no id, no timestamps.
     */
    public function up(): void
    {
        Schema::create('hashtag_post', function (Blueprint $table) {
            $table->foreignId('hashtag_id')->constrained('hashtags')->cascadeOnDelete();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();

            // Composite primary key: the same tag cannot be attached to the
            // same post twice, and the pivot needs no id of its own.
            $table->primary(['hashtag_id', 'post_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hashtag_post');
    }
};
