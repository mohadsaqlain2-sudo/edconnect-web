<?php

namespace App\Models;

use Database\Factories\FollowFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

/**
 * One row of the follow graph (FR-11).
 * follower_id follows following_id.
 */
class Follow extends Model
{
    /** @use HasFactory<FollowFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'follower_id',
        'following_id',
    ];

    /**
     * Nobody can follow themselves, even when a row is created directly
     * instead of through User::follow() (Phase 2 / FR-11).
     */
    protected static function booted(): void
    {
        static::creating(function (Follow $follow) {
            if ((int) $follow->follower_id === (int) $follow->following_id) {
                throw new InvalidArgumentException('A user cannot follow themselves.');
            }
        });
    }

    /* ---------------------------------------------------------------
     | Relationships
     * --------------------------------------------------------------- */

    /** The user who does the following. */
    public function follower(): BelongsTo
    {
        return $this->belongsTo(User::class, 'follower_id');
    }

    /** The user who is followed. */
    public function following(): BelongsTo
    {
        return $this->belongsTo(User::class, 'following_id');
    }
}
