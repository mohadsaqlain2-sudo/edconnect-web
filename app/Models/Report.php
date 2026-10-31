<?php

namespace App\Models;

use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * A report against a post or a comment (FR-32 ... FR-34).
 */
class Report extends Model
{
    /** @use HasFactory<ReportFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_REVIEWED = 'reviewed';

    public const STATUS_DISMISSED = 'dismissed';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_REVIEWED,
        self::STATUS_DISMISSED,
    ];

    /**
     * The reasons shown in the report form. The key is stored in the database.
     *
     * @var array<string, string>
     */
    public const REASONS = [
        'spam' => 'Spam',
        'harassment' => 'Harassment',
        'inappropriate_content' => 'Inappropriate content',
        'hate_speech' => 'Hate speech',
        'other' => 'Other',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'reporter_id',
        'reportable_type',
        'reportable_id',
        'reason',
        'details',
        'status',
    ];

    /* ---------------------------------------------------------------
     | Relationships
     * --------------------------------------------------------------- */

    /** The user who filed the report. */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /** The reported content: a Post or a Comment. */
    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    /* ---------------------------------------------------------------
     | Scopes
     * --------------------------------------------------------------- */

    /**
     * Reports that still need a decision from an administrator (FR-34).
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Filter on one of the three statuses.
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /* ---------------------------------------------------------------
     | Readable labels for the admin pages
     * --------------------------------------------------------------- */

    public function getReasonLabelAttribute(): string
    {
        return self::REASONS[$this->reason]
            ?? Str::headline((string) $this->reason);
    }

    public function getStatusLabelAttribute(): string
    {
        return Str::headline((string) $this->status);
    }

    /**
     * "Post" or "Comment" - used in the moderation tables.
     */
    public function getContentTypeAttribute(): string
    {
        return $this->reportable_type === Post::class ? 'Post' : 'Comment';
    }

    /**
     * The author of the reported content. Null when the content was deleted.
     */
    public function getReportedUserAttribute(): ?User
    {
        return $this->reportable?->user;
    }

    /**
     * A short, already escaped-safe excerpt of the reported body.
     */
    public function getContentPreviewAttribute(): string
    {
        if (! $this->reportable) {
            return 'The reported content has been deleted.';
        }

        return Str::limit((string) $this->reportable->body, 140);
    }
}
