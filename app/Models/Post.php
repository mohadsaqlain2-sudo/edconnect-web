<?php

namespace App\Models;

use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'body',
        'image',
        'is_hidden',
    ];

    protected function casts(): array
    {
        return [
            'is_hidden' => 'boolean',
        ];
    }

    /* ---------------------------------------------------------------
     | Relationships
     * --------------------------------------------------------------- */

    /** The author of the post. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function hashtags(): BelongsToMany
    {
        return $this->belongsToMany(Hashtag::class, 'hashtag_post');
    }

    /** Reports made against this post (moderation). */
    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    /* ---------------------------------------------------------------
     | Scopes and helpers
     * --------------------------------------------------------------- */

    /**
     * Hide posts that an administrator has hidden (FR-28).
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_hidden', false);
    }

    /**
     * Everything a feed card needs, in one go (FR-12).
     * Eager loads the author and the hashtags, counts the likes/comments and
     * adds a "liked_by_me" flag - so rendering 10 cards stays at 3 queries
     * instead of 30+.
     */
    public function scopeWithFeedData(Builder $query, ?User $viewer): Builder
    {
        return $query
            ->with([
                'user:id,name,role,photo,subject',
                'hashtags:id,name',
            ])
            // Hidden comments do not count towards the visible total (FR-10).
            ->withCount([
                'likes',
                'comments' => static fn (Builder $comments) => $comments->visible(),
            ])
            ->withExists([
                // 1 when the viewer liked the post, 0 otherwise.
                'likes as liked_by_me' => static fn (Builder $likes) => $likes->where('user_id', $viewer?->id ?? 0),
            ]);
    }

    /**
     * Phase 2 scope: add likes_count and comments_count in the same query.
     */
    public function scopeWithCounts(Builder $query): Builder
    {
        return $query->withCount(['likes', 'comments']);
    }

    /**
     * Has this post been liked by the given user? (FR-9)
     * Uses the eager loaded "likes" relation when it is available, so a feed
     * with 20 posts stays at one query instead of twenty.
     */
    public function isLikedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($this->relationLoaded('likes')) {
            return $this->likes->contains('user_id', $user->id);
        }

        return $this->likes()->where('user_id', $user->id)->exists();
    }

    /**
     * Phase 2 alias with the exact name from the SRS.
     */
    public function likedBy(?User $user): bool
    {
        return $this->isLikedBy($user);
    }
}
