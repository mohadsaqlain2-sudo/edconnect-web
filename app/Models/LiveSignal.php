<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One signaling message of the app's own WebRTC room (FR-20, FR-22).
 * A row is either a session message (offer / answer / ice), a chat line or a
 * "bye" notice. The client polls them and deletes nothing: the controller
 * prunes rows older than config('live.signal_ttl').
 */
class LiveSignal extends Model
{
    /** The kinds of message that may live in this table. */
    public const TYPES = ['offer', 'answer', 'ice', 'chat', 'bye'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'class_id',
        'sender_id',
        'target_id',
        'type',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    /** Who wrote the message. */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /** The class the message belongs to. */
    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }
}
