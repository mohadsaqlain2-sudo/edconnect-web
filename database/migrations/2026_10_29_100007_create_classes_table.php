<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Live classes. The model is ClassRoom because "Class" is a reserved word
     * in PHP, but the table stays "classes" as required by the SRS (FR-18 ... FR-24).
     */
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id();

            // The teacher who runs the class.
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->string('subject')->nullable();
            $table->date('date');
            $table->time('time');
            $table->unsignedSmallInteger('duration');           // in minutes

            // Jitsi room name - each class has its own unique room.
            $table->string('room_name')->unique();

            $table->enum('status', ['scheduled', 'live', 'completed'])->default('scheduled');

            // Admin moderation (FR-28).
            $table->boolean('is_hidden')->default(false);

            $table->timestamps();

            // We list classes by status and by day all the time.
            $table->index('status');
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
