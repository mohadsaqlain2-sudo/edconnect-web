<?php

namespace App\Models;

use Database\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Comment extends Model
{
    /** @use HasFactory<CommentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'post_id',
        'user_id',
        'body',
        'is_hidden',
    ];

    protected function casts(): array
    {
        return [
            'is_hidden' => 'boolean',
        ];
    }

    /**
     * Delete the reports that point at this comment.
     *
     * "reports" is polymorphic (no foreign key), so the database cascade never
     * removes these rows. Comments are deleted by their author, by an admin
     * and through the post cascade, so the cleanup lives on the model.
     */
    protected static function booted(): void
    {
        static::deleting(function (Comment $comment): void {
            Report::query()
                ->where('reportable_type', self::class)
                ->where('reportable_id', $comment->id)
                ->delete();
        });
    }

    /* ---------------------------------------------------------------
     | Relationships
     * --------------------------------------------------------------- */

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Reports made against this comment (moderation). */
    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    /* ---------------------------------------------------------------
     | Scopes
     * --------------------------------------------------------------- */

    /**
     * Hide comments that an administrator has hidden.
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_hidden', false);
    }
}
