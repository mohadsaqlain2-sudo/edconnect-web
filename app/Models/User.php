<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

/**
 * Implementing MustVerifyEmail turns on the "verify your e-mail" flow:
 * the Registered event sends the signed link (FR-1) and the profile page can
 * show the unverified banner. Until the address is confirmed the account is
 * still usable, so a flaky mail server never locks a student out.
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    // The three roles used in this application.
    public const ROLE_ADMIN = 'admin';

    public const ROLE_TEACHER = 'teacher';

    public const ROLE_STUDENT = 'student';

    // Roles a visitor may pick on the registration form (admin is never one of them).
    public const REGISTERABLE_ROLES = [self::ROLE_TEACHER, self::ROLE_STUDENT];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'bio',
        'photo',
        'subject',
        'is_blocked',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_blocked' => 'boolean',
        ];
    }

    /**
     * Clean up everything the database cascade cannot reach when an account is
     * deleted (self-service from the profile page or by an administrator).
     *
     * Posts, comments, likes, follows, enrollments, notifications and the
     * reports the user filed all disappear through foreign keys. The two
     * exceptions handled here are the polymorphic reports about the user's
     * content and the uploaded files on the public disk.
     */
    protected static function booted(): void
    {
        static::deleting(function (User $user): void {
            $postIds = $user->posts()->pluck('posts.id');

            // Reports about the user's own comments *and* about comments other
            // people left on the user's posts: the post cascade removes those
            // comments without firing their model events.
            $commentIds = Comment::query()
                ->where(function ($query) use ($user, $postIds): void {
                    $query->where('user_id', $user->id);

                    if ($postIds->isNotEmpty()) {
                        $query->orWhereIn('post_id', $postIds);
                    }
                })
                ->pluck('comments.id');

            if ($postIds->isNotEmpty()) {
                Report::query()
                    ->where('reportable_type', Post::class)
                    ->whereIn('reportable_id', $postIds)
                    ->delete();
            }

            if ($commentIds->isNotEmpty()) {
                Report::query()
                    ->where('reportable_type', Comment::class)
                    ->whereIn('reportable_id', $commentIds)
                    ->delete();
            }

            // Uploaded post images are files, not rows.
            $user->posts()
                ->whereNotNull('image')
                ->pluck('image')
                ->each(fn (string $image) => Storage::disk('public')->delete($image));

            // The profile photo (FR-4).
            if ($user->photo) {
                Storage::disk('public')->delete($user->photo);
            }
        });
    }

    /* ---------------------------------------------------------------
     | Role helpers - used in Blade views and in the middleware.
     * --------------------------------------------------------------- */

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isTeacher(): bool
    {
        return $this->role === self::ROLE_TEACHER;
    }

    public function isStudent(): bool
    {
        return $this->role === self::ROLE_STUDENT;
    }

    /**
     * Route name of the dashboard that belongs to this user's role (FR-2).
     * Used after login / registration to send everyone to the right place.
     */
    public function dashboardRoute(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'admin.dashboard',
            self::ROLE_TEACHER => 'teacher.dashboard',
            default => 'student.dashboard',
        };
    }

    /**
     * Send the branded password reset email (FR-3).
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Public URL of the uploaded profile photo, or the default avatar.
     */
    public function photoUrl(): string
    {
        return $this->photo
            ? Storage::disk('public')->url($this->photo)
            : asset('images/default-avatar.svg');
    }

    /**
     * Blade-friendly accessor: {{ $user->photo_url }} (Phase 2).
     */
    public function getPhotoUrlAttribute(): string
    {
        return $this->photoUrl();
    }

    /* ---------------------------------------------------------------
     | Follow counters (Phase 2)
     * --------------------------------------------------------------- */

    /**
     * Number of people following this user.
     * Reuses an eager-loaded withCount('followers') when available.
     */
    public function followersCount(): int
    {
        return (int) ($this->followers_count ?? $this->followers()->count());
    }

    /**
     * Number of people this user follows.
     * Reuses an eager-loaded withCount('following') when available.
     */
    public function followingCount(): int
    {
        return (int) ($this->following_count ?? $this->following()->count());
    }

    public function getFollowersCountAttribute(): int
    {
        return $this->followersCount();
    }

    public function getFollowingCountAttribute(): int
    {
        return $this->followingCount();
    }

    /* ---------------------------------------------------------------
     | Feed relationships
     * --------------------------------------------------------------- */

    /** Posts this user wrote. */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /** Comments this user wrote. */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /** Likes this user gave. */
    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    /** Reports this user filed (FR-32). */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    /* ---------------------------------------------------------------
     | Follow graph (FR-11)
     * --------------------------------------------------------------- */

    /** People who follow me. */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'following_id', 'follower_id')
            ->withTimestamps();
    }

    /** People I follow. */
    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'following_id')
            ->withTimestamps();
    }

    /**
     * Do I already follow this user?
     */
    public function isFollowing(?User $user): bool
    {
        // Nobody follows themselves.
        if (! $user || $this->is($user)) {
            return false;
        }

        if ($this->relationLoaded('following')) {
            return $this->following->contains('id', $user->id);
        }

        return $this->following()->whereKey($user->id)->exists();
    }

    /**
     * Follow another user.
     * Returns false when the target is the user themselves: self-following is
     * never allowed (FR-11). The pivot table also has a unique index, so
     * following twice is impossible.
     */
    public function follow(User $user): bool
    {
        if ($this->is($user)) {
            return false;
        }

        $this->following()->syncWithoutDetaching([$user->id]);

        return true;
    }

    /**
     * Stop following a user.
     */
    public function unfollow(User $user): void
    {
        $this->following()->detach($user->id);
    }

    /* ---------------------------------------------------------------
     | Classes (FR-15 ... FR-21)
     * --------------------------------------------------------------- */

    /** Classes this user teaches (only meaningful for teachers). */
    public function teachingClasses(): HasMany
    {
        return $this->hasMany(ClassRoom::class, 'teacher_id');
    }

    /**
     * Backwards-compatible alias used by the Phase 3-8 controllers.
     */
    public function classes(): HasMany
    {
        return $this->teachingClasses();
    }

    /** Classes this user is enrolled in (only meaningful for students). */
    public function enrolledClasses(): BelongsToMany
    {
        return $this->belongsToMany(ClassRoom::class, 'class_enrollments', 'student_id', 'class_id')
            ->withTimestamps();
    }

    /**
     * Is this student enrolled in the given class?
     */
    public function isEnrolledIn(ClassRoom $classRoom): bool
    {
        if ($this->relationLoaded('enrolledClasses')) {
            return $this->enrolledClasses->contains('id', $classRoom->id);
        }

        return $this->enrolledClasses()->whereKey($classRoom->id)->exists();
    }
}
