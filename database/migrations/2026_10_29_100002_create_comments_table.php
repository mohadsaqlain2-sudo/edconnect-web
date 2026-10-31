<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Comments that users write under a post (FR-13).
     */
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();

            // Deleting the post removes its comments.
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();

            // Deleting the author removes their comments.
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->text('body');
            $table->boolean('is_hidden')->default(false);      // admin moderation

            $table->timestamps();

            $table->index('is_hidden');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
