<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The social feed posts (FR-9 ... FR-12).
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();

            // Author. If the author is deleted, the post goes with them.
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('body', 280);                       // max 280 characters
            $table->string('image')->nullable();               // relative path on the "public" disk
            $table->boolean('is_hidden')->default(false);      // admin moderation (FR-27/FR-28)

            $table->timestamps();

            // Feeds always filter on this column.
            $table->index('is_hidden');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
