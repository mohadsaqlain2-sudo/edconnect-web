<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The signaling mailbox of the app's own WebRTC room (FR-20, FR-22).
     * Peers never talk to each other directly to negotiate a session: every
     * offer, answer, ICE candidate and chat line is stored here and picked up
     * by the other participant through a short poll.
     */
    public function up(): void
    {
        Schema::create('live_signals', function (Blueprint $table) {
            $table->id();

            // The class the message belongs to; deleting a class clears it.
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            // Who sent the message.
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            // The recipient; null means "everybody" (chat and ICE broadcast).
            $table->foreignId('target_id')->nullable()->constrained('users')->cascadeOnDelete();

            // offer / answer / ice / chat / bye (see LiveSignal::TYPES).
            $table->string('type', 16);
            // The SDP, the ICE candidate or the chat body.
            $table->json('payload')->nullable();

            $table->timestamps();

            // The poll always asks for "everything newer than X in this class".
            $table->index(['class_id', 'id']);
            // Signals addressed to one peer.
            $table->index(['class_id', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_signals');
    }
};
