<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Who follows whom (FR-17).
     * follower_id follows following_id.
     */
    public function up(): void
    {
        Schema::create('follows', function (Blueprint $table) {
            $table->id();

            $table->foreignId('follower_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('following_id')->constrained('users')->cascadeOnDelete();

            $table->timestamps();

            // Nobody can follow the same person twice.
            // (Self-following is blocked in User::follow() as well.)
            $table->unique(['follower_id', 'following_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follows');
    }
};
