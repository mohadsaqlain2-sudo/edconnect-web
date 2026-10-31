<?php

namespace App\Models;

use Database\Factories\ClassRoomFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A scheduled live class (FR-15 ... FR-21).
 * The class is called ClassRoom because "Class" is a reserved word in PHP;
 * the database table is "classes".
 */
class ClassRoom extends Model
{
    /** @use HasFactory<ClassRoomFactory> */
    use HasFactory;

    // The three possible states of a class.
    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_LIVE = 'live';

    public const STATUS_COMPLETED = 'completed';

    public const STATUSES = [
        self::STATUS_SCHEDULED,
        self::STATUS_LIVE,
        self::STATUS_COMPLETED,
    ];

    /**
     * Which status changes a teacher is allowed to make (FR-21).
     * A class always starts as "scheduled"; from there the teacher can start it
     * or cancel it, a running class can be ended or pushed back to scheduled,
     * and a class that was ended by mistake can be reopened.
     */
    public const TRANSITIONS = [
        self::STATUS_SCHEDULED => [self::STATUS_LIVE, self::STATUS_COMPLETED],
        self::STATUS_LIVE => [self::STATUS_COMPLETED, self::STATUS_SCHEDULED],
        self::STATUS_COMPLETED => [self::STATUS_LIVE],
    ];

    /**
     * The table name does not follow the model name, so it is set explicitly.
     *
     * @var string
     */
    protected $table = 'classes';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'teacher_id',
        'title',
        'description',
        'subject',
        'date',
        'time',
        'duration',
        'room_name',
        'status',
        'is_hidden',
        // Set by classes:notify-starting-soon (FR-23).
        'starting_soon_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'duration' => 'integer',
            'is_hidden' => 'boolean',
            'starting_soon_notified_at' => 'datetime',
        ];
    }

    /**
     * Every class gets an unguessable Jitsi room name, even when it is created
     * from code or a factory instead of the controller (Phase 2).
     */
    protected static function booted(): void
    {
        static::creating(function (ClassRoom $classRoom) {
            if (blank($classRoom->room_name)) {
                $classRoom->room_name = static::generateRoomName();
            }
        });
    }

    /* ---------------------------------------------------------------
     | Relationships
     * --------------------------------------------------------------- */

    /** The teacher who runs the class. */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /** The enrolled students. */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_enrollments', 'class_id', 'student_id')
            ->withTimestamps();
    }

    /** Raw enrollment rows (used for attendance in Phase 5). */
    public function enrollments(): HasMany
    {
        return $this->hasMany(ClassEnrollment::class, 'class_id');
    }

    /* ---------------------------------------------------------------
     | Scopes and helpers
     * --------------------------------------------------------------- */

    /**
     * Classes that are still ahead of us (FR-17):
     * status "scheduled" and a date/time in the future, soonest first.
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        $today = now()->toDateString();
        $now = now()->format('H:i:s');

        // whereDate/whereTime compare only the date or the time part, so the
        // result is the same on MySQL and on the SQLite database used by tests.
        return $query->where('status', self::STATUS_SCHEDULED)
            ->where(function (Builder $query) use ($today, $now) {
                $query->whereDate('date', '>', $today)
                    // Same day: the start time must not have passed yet.
                    ->orWhere(function (Builder $query) use ($today, $now) {
                        $query->whereDate('date', $today)->whereTime('time', '>', $now);
                    });
            })
            ->orderBy('date')
            ->orderBy('time');
    }

    /**
     * Hide classes that an administrator has hidden (FR-28).
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_hidden', false);
    }

    /**
     * Phase 2: filter by one of the three statuses.
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Was this class given by the given teacher?
     */
    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && $this->teacher_id === $user->id;
    }

    /** Is the class running right now? (FR-21) */
    public function isLive(): bool
    {
        return $this->status === self::STATUS_LIVE;
    }

    /**
     * The exact moment the class starts (date + time merged into one Carbon).
     */
    public function startsAt(): Carbon
    {
        // "date" is cast to a Carbon date, "time" stays a plain string.
        $date = $this->date instanceof \DateTimeInterface
            ? $this->date->format('Y-m-d')
            : (string) $this->date;

        $time = $this->time instanceof \DateTimeInterface
            ? $this->time->format('H:i:s')
            : substr((string) $this->time, 0, 8);

        return Carbon::parse($date.' '.$time);
    }

    /** The moment the class ends (start + duration). */
    public function endsAt(): Carbon
    {
        return $this->startsAt()->addMinutes((int) $this->duration);
    }

    /**
     * Phase 2 accessor: {{ $classRoom->start_datetime }}.
     * Returns the merged date + time as a Carbon instance.
     */
    public function getStartDateTimeAttribute(): Carbon
    {
        return $this->startsAt();
    }

    /**
     * Has the class not started yet?
     */
    public function isInTheFuture(): bool
    {
        return $this->startsAt()->isFuture();
    }

    /**
     * Short timing hint for a class card (FR-21): "Live now",
     * "Starts in 2h 15m" or "Ended". No JavaScript needed, so the card stays
     * readable on a slow connection.
     */
    public function timelineLabel(): string
    {
        if ($this->isLive()) {
            return __('Live now');
        }

        // A class the teacher ended (or that the scheduler closed) is over.
        if ($this->status === self::STATUS_COMPLETED) {
            return __('Ended');
        }

        if ($this->startsAt()->isFuture()) {
            return __('Starts in :time', ['time' => $this->humanGap($this->startsAt())]);
        }

        // Still scheduled but its slot has already passed.
        return __('Ended');
    }

    /**
     * "2h 15m" style gap between now and the given moment.
     * Deliberately coarse: the card only needs a hint, not the seconds.
     */
    private function humanGap(Carbon $moment): string
    {
        // "true" asks for the absolute value, which is the same on Carbon 2 and 3.
        $minutes = (int) now()->diffInMinutes($moment, true);

        $parts = [];

        if ($minutes >= 1440) {
            $parts[] = intdiv($minutes, 1440).'d';
        }

        if (intdiv($minutes % 1440, 60) > 0) {
            $parts[] = intdiv($minutes % 1440, 60).'h';
        }

        if ($minutes % 60 > 0) {
            $parts[] = ($minutes % 60).'m';
        }

        return $parts === [] ? '< 1m' : implode(' ', $parts);
    }

    /* ---------------------------------------------------------------
     | Status transitions (FR-21)
     * --------------------------------------------------------------- */

    /**
     * The statuses this class may move to next.
     *
     * @return list<string>
     */
    public function allowedTransitions(): array
    {
        return self::TRANSITIONS[$this->status] ?? [];
    }

    /**
     * May the class switch to the given status?
     */
    public function canTransitionTo(string $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    /* ---------------------------------------------------------------
     | Jitsi room (FR-19)
     * --------------------------------------------------------------- */

    /**
     * A new room name nobody can guess: "educonnect-" plus 20 random
     * characters. The loop guarantees the name is not used yet, and the
     * unique index on the column is the final safety net.
     */
    public static function generateRoomName(): string
    {
        do {
            $name = 'educonnect-'.Str::random(20);
        } while (static::query()->where('room_name', $name)->exists());

        return $name;
    }
}
