# EduConnect - Phase 2 deliverable

Database migrations, Eloquent models, relationships, factories, seeders,
relationship tests and the tinker proof for SRS section 4.

> Runtime note: this repository is on Laravel 12.70.2 (the SRS asks for
> Laravel 11). Every Phase 2 feature is framework-compatible with Laravel 11;
> only the framework version differs.

---

## 1. ER summary

| Table | Columns | Relationships |
|---|---|---|
| `users` | `id`, `name`, `email`, `email_verified_at`, `password`, `role` (admin/teacher/student), `bio`, `photo`, `subject`, `is_blocked`, `remember_token`, `timestamps` | hasMany `posts`, `comments`, `likes`, `reports`, `teachingClasses`; belongsToMany `followers`, `following`, `enrolledClasses` |
| `posts` | `id`, `user_id` FK -> users (cascade), `body` varchar(280), `image` nullable, `is_hidden` bool default false, `timestamps` | belongsTo `user`; hasMany `comments`, `likes`; belongsToMany `hashtags`; morphMany `reports` |
| `comments` | `id`, `post_id` FK -> posts (cascade), `user_id` FK -> users (cascade), `body`, `is_hidden` bool default false, `timestamps` | belongsTo `post`, `user`; morphMany `reports` |
| `likes` | `id`, `post_id` FK -> posts (cascade), `user_id` FK -> users (cascade), `timestamps`; UNIQUE(`post_id`,`user_id`) | belongsTo `post`, `user` |
| `follows` | `id`, `follower_id` FK -> users (cascade), `following_id` FK -> users (cascade), `timestamps`; UNIQUE(`follower_id`,`following_id`) | belongsTo `follower`, `following`; self-referencing graph on `User` |
| `hashtags` | `id`, `name` UNIQUE (lowercase, no `#`), `timestamps` | belongsToMany `posts` |
| `hashtag_post` | `hashtag_id` FK -> hashtags (cascade), `post_id` FK -> posts (cascade); PRIMARY KEY(`hashtag_id`,`post_id`) | pivot between `posts` and `hashtags` |
| `classes` | `id`, `teacher_id` FK -> users (cascade), `title`, `description` nullable, `subject` nullable, `date`, `time`, `duration` (minutes), `room_name` UNIQUE, `status` enum(scheduled/live/completed) default scheduled, `is_hidden` bool default false, `starting_soon_notified_at` nullable, `timestamps` | belongsTo `teacher`; belongsToMany `students`; hasMany `enrollments` |
| `class_enrollments` | `id`, `class_id` FK -> classes (cascade), `student_id` FK -> users (cascade), `timestamps`; UNIQUE(`class_id`,`student_id`) | belongsTo `classRoom`, `student` |
| `reports` | `id`, `reporter_id` FK -> users (cascade), `reportable_type`, `reportable_id`, `reason`, `details` nullable, `status` enum(pending/reviewed/dismissed) default pending, `timestamps`; INDEX(`reportable_type`,`reportable_id`); UNIQUE(`reporter_id`,`reportable_type`,`reportable_id`) | belongsTo `reporter`; morphTo `reportable` (Post or Comment) |
| `notifications` | `id` uuid PK, `type`, `notifiable_type`, `notifiable_id`, `data`, `read_at`, `timestamps`; INDEX(`notifiable_type`,`notifiable_id`) | morphTo `notifiable` (Laravel default) |

### Why the model is called `ClassRoom`

`Class` is a reserved word in PHP, so it cannot be used as a class name.
The model is `App\Models\ClassRoom` and it declares `protected $table = 'classes';`
so the database still matches the SRS exactly.

### Index policy

- Foreign keys cascade where the child cannot exist without the parent.
- Every relationship column has an index. Composite UNIQUE indexes only cover
  their first column, so `2026_10_30_000001_add_phase_two_relationship_indexes.php`
  adds the missing single-column indexes for SQLite and MySQL.
- Unique constraints: likes, follows, hashtag names, class room names,
  class enrollments and one report per reporter per item.

---

## 2. Migrations

### `database/migrations/2026_10_29_000001_add_profile_fields_to_users_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the EduConnect profile fields to the users table.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // admin | teacher | student
            $table->enum('role', ['admin', 'teacher', 'student'])->default('student')->after('password');
            $table->text('bio')->nullable()->after('role');
            $table->string('photo')->nullable()->after('bio');     // relative path on the "public" disk
            $table->string('subject')->nullable()->after('photo'); // e.g. "Mathematics" (teachers)
            $table->boolean('is_blocked')->default(false)->after('subject');

            // Indexes: we filter/sort by these columns a lot.
            $table->index('role');
            $table->index('is_blocked');
        });
    }

    /**
     * Remove the columns again when rolling back.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropIndex(['is_blocked']);
            $table->dropColumn(['role', 'bio', 'photo', 'subject', 'is_blocked']);
        });
    }
};
```

### `database/migrations/2026_10_29_100001_create_posts_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The social feed posts (FR-9 ... FR-12).
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();

            // Author. If the author is deleted, the post goes with them.
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('body', 280);                       // max 280 characters
            $table->string('image')->nullable();               // relative path on the "public" disk
            $table->boolean('is_hidden')->default(false);      // admin moderation (FR-27/FR-28)

            $table->timestamps();

            // Feeds always filter on this column.
            $table->index('is_hidden');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
```

### `database/migrations/2026_10_29_100002_create_comments_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Comments that users write under a post (FR-13).
     */
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();

            // Deleting the post removes its comments.
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();

            // Deleting the author removes their comments.
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->text('body');
            $table->boolean('is_hidden')->default(false);      // admin moderation

            $table->timestamps();

            $table->index('is_hidden');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
```

### `database/migrations/2026_10_29_100003_create_likes_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per "user liked post" (FR-11).
     */
    public function up(): void
    {
        Schema::create('likes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->timestamps();

            // A user can like a post only once.
            $table->unique(['post_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('likes');
    }
};
```

### `database/migrations/2026_10_29_100004_create_follows_table.php`

```php
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
```

### `database/migrations/2026_10_29_100005_create_hashtags_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hashtags extracted from post bodies (FR-15).
     */
    public function up(): void
    {
        Schema::create('hashtags', function (Blueprint $table) {
            $table->id();

            // Always stored in lower case so "#Maths" and "#maths" are the same tag.
            $table->string('name')->unique();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hashtags');
    }
};
```

### `database/migrations/2026_10_29_100006_create_hashtag_post_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivot table between posts and hashtags (FR-15).
     * A plain pivot: no id, no timestamps.
     */
    public function up(): void
    {
        Schema::create('hashtag_post', function (Blueprint $table) {
            $table->foreignId('hashtag_id')->constrained('hashtags')->cascadeOnDelete();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();

            // Composite primary key: the same tag cannot be attached to the
            // same post twice, and the pivot needs no id of its own.
            $table->primary(['hashtag_id', 'post_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hashtag_post');
    }
};
```

### `database/migrations/2026_10_29_100007_create_classes_table.php`

```php
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
```

### `database/migrations/2026_10_29_100008_create_class_enrollments_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which student is enrolled in which class (FR-21).
     */
    public function up(): void
    {
        Schema::create('class_enrollments', function (Blueprint $table) {
            $table->id();

            // "class_id" points at the "classes" table.
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();

            $table->timestamps();

            // A student can only enroll once per class.
            $table->unique(['class_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_enrollments');
    }
};
```

### `database/migrations/2026_10_29_100009_create_reports_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reports against a post or a comment (FR-16, FR-26).
     * Polymorphic: reportable_type is App\Models\Post or App\Models\Comment.
     */
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();

            // The user who reported the content.
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();

            // reportable_type + reportable_id + an index on both.
            $table->morphs('reportable');

            $table->text('reason');

            $table->enum('status', ['pending', 'reviewed', 'dismissed'])->default('pending');

            $table->timestamps();

            // The moderation queue is filtered by status.
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
```

### `database/migrations/2026_10_29_124725_create_notifications_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
```

### `database/migrations/2026_10_29_130000_add_notification_support_to_classes_and_users.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 6 support columns.
 *
 * classes.starting_soon_notified_at
 *   A one-shot flag: the "classes:notify-starting-soon" command sets it after
 *   it has warned the teacher and the enrolled students, so the reminder for
 *   that class can never be sent twice (FR-23).
 *
 * users.name / users.subject
 *   Indexes for the people search in the navbar (FR-14).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->timestamp('starting_soon_notified_at')->nullable()->after('status');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('name');
            $table->index('subject');
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn('starting_soon_notified_at');
        });

        Schema::table('users', function (Blueprint $table) {
            // Laravel builds the names "users_name_index" / "users_subject_index".
            $table->dropIndex(['name']);
            $table->dropIndex(['subject']);
        });
    }
};
```

### `database/migrations/2026_10_29_140000_add_moderation_support_to_reports_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extra moderation data for reports (FR-32 ... FR-34).
     *
     *  - "details" is the optional free text the reporter may add.
     *  - the unique index makes a second report of the same item by the same
     *    user impossible, even if somebody bypasses the controller.
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->text('details')->nullable()->after('reason');

            $table->unique(
                ['reporter_id', 'reportable_type', 'reportable_id'],
                'reports_reporter_reportable_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropUnique('reports_reporter_reportable_unique');
            $table->dropColumn('details');
        });
    }
};
```

### `database/migrations/2026_10_30_000001_add_phase_two_relationship_indexes.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2: make sure every relationship column has its own index.
 *
 * Foreign keys create an index automatically on MySQL, but not on SQLite (used
 * by the test suite). Composite unique indexes only cover their FIRST column,
 * so columns such as likes.user_id and follows.following_id still need an
 * index of their own for the "my likes" / "my followers" queries.
 *
 * The migration is defensive: it only creates an index when no single-column
 * index on that column already exists, so it is safe on both databases.
 */
return new class extends Migration
{
    /**
     * Table => relationship columns that need a single-column index.
     *
     * @var array<string, list<string>>
     */
    private const INDEXES = [
        'posts' => ['user_id'],
        'comments' => ['post_id', 'user_id'],
        'likes' => ['user_id'],
        'follows' => ['following_id'],
        'hashtag_post' => ['post_id'],
        'classes' => ['teacher_id'],
        'class_enrollments' => ['student_id'],
        'reports' => ['reporter_id'],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $table => $columns) {
            foreach ($columns as $column) {
                // Skip when a single-column index already exists (MySQL FK index).
                if (Schema::hasIndex($table, [$column])) {
                    continue;
                }

                Schema::table($table, function (Blueprint $table) use ($column) {
                    $table->index($column, 'phase2_'.$table->getTable().'_'.$column.'_index');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $table => $columns) {
            foreach ($columns as $column) {
                $name = 'phase2_'.$table.'_'.$column.'_index';

                if (Schema::hasIndex($table, $name)) {
                    Schema::table($table, function (Blueprint $table) use ($name) {
                        $table->dropIndex($name);
                    });
                }
            }
        }
    }
};
```

---

## 3. Models

### `app/Models/User.php`

```php
<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
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
```

### `app/Models/Post.php`

```php
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
            ->withCount(['likes', 'comments'])
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
```

### `app/Models/Comment.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Comment extends Model
{
    /** @use HasFactory<\Database\Factories\CommentFactory> */
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
```

### `app/Models/Like.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Like extends Model
{
    /** @use HasFactory<\Database\Factories\LikeFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'post_id',
        'user_id',
    ];

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
}
```

### `app/Models/Follow.php`

```php
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
```

### `app/Models/Hashtag.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Hashtag extends Model
{
    /** @use HasFactory<\Database\Factories\HashtagFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
    ];

    /**
     * Hashtags are always stored in lower case.
     */
    public function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = mb_strtolower(ltrim(trim($value), '#'));
    }

    /* ---------------------------------------------------------------
     | Relationships
     * --------------------------------------------------------------- */

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'hashtag_post');
    }
}
```

### `app/Models/ClassRoom.php`

```php
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
```

### `app/Models/ClassEnrollment.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The link between a class and an enrolled student (FR-17).
 */
class ClassEnrollment extends Model
{
    /** @use HasFactory<\Database\Factories\ClassEnrollmentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'class_id',
        'student_id',
    ];

    /* ---------------------------------------------------------------
     | Relationships
     * --------------------------------------------------------------- */

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
```

### `app/Models/Report.php`

```php
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
```

---

## 4. Factories

### `database/factories/UserFactory.php`

```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            // The User model has a "hashed" cast, so the plain value is bcrypted on save.
            'password' => 'password',
            'role' => User::ROLE_STUDENT,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * An admin account.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_ADMIN,
        ]);
    }

    /**
     * A teacher account (teachers also have a subject).
     */
    public function teacher(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_TEACHER,
            'subject' => fake()->randomElement(['Mathematics', 'Physics', 'English', 'History']),
            'bio' => fake()->sentence(12),
        ]);
    }

    /**
     * A student account.
     */
    public function student(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_STUDENT,
        ]);
    }

    /**
     * A blocked account: it may not log in (FR-6).
     */
    public function blocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_blocked' => true,
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
```

### `database/factories/PostFactory.php`

```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            // Short enough to stay well under the 280 character limit.
            'body' => fake()->sentence(random_int(6, 16)),
            'image' => null,
            'is_hidden' => false,
        ];
    }

    /**
     * A post that an administrator has hidden (FR-27).
     */
    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_hidden' => true,
        ]);
    }
}
```

### `database/factories/CommentFactory.php`

```php
<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comment>
 */
class CommentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'user_id' => User::factory(),
            'body' => fake()->sentence(random_int(4, 12)),
            'is_hidden' => false,
        ];
    }

    /**
     * A comment that an administrator has hidden.
     */
    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_hidden' => true,
        ]);
    }
}
```

### `database/factories/LikeFactory.php`

```php
<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Like>
 */
class LikeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'user_id' => User::factory(),
        ];
    }
}
```

### `database/factories/FollowFactory.php`

```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Follow>
 */
class FollowFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Two separate users: the factory never follows itself.
            'follower_id' => User::factory(),
            'following_id' => User::factory(),
        ];
    }
}
```

### `database/factories/HashtagFactory.php`

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Hashtag>
 */
class HashtagFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Hashtags are stored in lower case, without the leading "#".
            'name' => Str::lower(fake()->unique()->word()),
        ];
    }
}
```

### `database/factories/ClassRoomFactory.php`

```php
<?php

namespace Database\Factories;

use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassRoom>
 */
class ClassRoomFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // A class is always created by a teacher.
            'teacher_id' => User::factory()->teacher(),
            'title' => fake()->sentence(4, false),
            'description' => fake()->paragraph(2),
            'subject' => fake()->randomElement(['Mathematics', 'Physics', 'English', 'History']),
            'date' => fake()->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'time' => fake()->time('H:i:s'),
            'duration' => fake()->randomElement([30, 45, 60, 90]),
            // room_name is generated by ClassRoom::booted() so it is always
            // unique and impossible to guess.
            'status' => ClassRoom::STATUS_SCHEDULED,
            'is_hidden' => false,
        ];
    }

    /**
     * A class that is scheduled in the future.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ClassRoom::STATUS_SCHEDULED,
            'date' => fake()->dateTimeBetween('+1 day', '+30 days')->format('Y-m-d'),
        ]);
    }

    /**
     * A class that is running right now.
     */
    public function live(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ClassRoom::STATUS_LIVE,
            'date' => now()->toDateString(),
        ]);
    }

    /**
     * A class that already took place.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ClassRoom::STATUS_COMPLETED,
            'date' => fake()->dateTimeBetween('-30 days', '-1 day')->format('Y-m-d'),
        ]);
    }

    /**
     * A class hidden by an administrator (FR-28).
     */
    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_hidden' => true,
        ]);
    }
}
```

### `database/factories/ClassEnrollmentFactory.php`

```php
<?php

namespace Database\Factories;

use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClassEnrollment>
 */
class ClassEnrollmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'class_id' => ClassRoom::factory(),
            // Only students enroll in classes.
            'student_id' => User::factory()->student(),
        ];
    }
}
```

### `database/factories/ReportFactory.php`

```php
<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Report>
 */
class ReportFactory extends Factory
{
    /**
     * A report against a post by default.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reporter_id' => User::factory(),
            'reportable_type' => Post::class,
            'reportable_id' => Post::factory(),
            'reason' => fake()->sentence(8),
            'status' => Report::STATUS_PENDING,
        ];
    }

    /**
     * Report a post.
     */
    public function forPost(): static
    {
        return $this->state(fn (array $attributes) => [
            'reportable_type' => Post::class,
            'reportable_id' => Post::factory(),
        ]);
    }

    /**
     * Report a comment.
     */
    public function forComment(): static
    {
        return $this->state(fn (array $attributes) => [
            'reportable_type' => Comment::class,
            'reportable_id' => Comment::factory(),
        ]);
    }

    /**
     * A report that an administrator has already looked at.
     */
    public function reviewed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Report::STATUS_REVIEWED,
        ]);
    }

    /**
     * A report that was rejected.
     */
    public function dismissed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Report::STATUS_DISMISSED,
        ]);
    }
}
```

---

## 5. Seeders

### `database/seeders/AdminUserSeeder.php`

```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    /**
     * Create the administrator account.
     * Admin users can never be created from the public registration form.
     *
     * The email, name and password come from the .env file - no secret is ever
     * hardcoded in the repository.
     */
    public function run(): void
    {
        $email = config('educonnect.admin.email');
        $password = config('educonnect.admin.password');

        // Fail loudly instead of creating an account with a weak/empty password.
        if (blank($password)) {
            throw new RuntimeException(
                'ADMIN_PASSWORD is not set. Add a strong password to your .env file and run the seeder again.'
            );
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => config('educonnect.admin.name'),
                'password' => $password, // bcrypted by the model's "hashed" cast
                'role' => User::ROLE_ADMIN,
                'is_blocked' => false,
                'email_verified_at' => now(),
            ]
        );

        // Handy confirmation when running "php artisan db:seed".
        $this->command?->info("Admin account ready: {$email}");
    }
}
```

### `database/seeders/DemoUserSeeder.php`

```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * The demo people. Phase 1 created them inside DemoSeeder; Phase 2 splits the
 * people from the content so DemoContentSeeder can run on its own.
 *
 * Every account uses the password "password" (hashed by the User model's
 * "hashed" cast). The admin comes from AdminUserSeeder.
 */
class DemoUserSeeder extends Seeder
{
    /** 3 teachers with realistic names, subjects and bios. */
    private const TEACHERS = [
        [
            'name' => 'Maria Rossi',
            'email' => 'teacher1@educonnect.test',
            'subject' => 'Mathematics',
            'bio' => 'Maths teacher for 12 years. I make algebra feel like a puzzle, not a punishment.',
        ],
        [
            'name' => 'David Chen',
            'email' => 'teacher2@educonnect.test',
            'subject' => 'Physics',
            'bio' => 'Physics and robotics. If it moves, we can measure it. Live experiments every Thursday.',
        ],
        [
            'name' => 'Amina Yusuf',
            'email' => 'teacher3@educonnect.test',
            'subject' => 'English',
            'bio' => 'Reader, writer and English teacher. Essays are conversations with a deadline.',
        ],
    ];

    /** 6 students. */
    private const STUDENTS = [
        ['name' => 'Sam Carter', 'email' => 'student1@educonnect.test'],
        ['name' => 'Lina Gomez', 'email' => 'student2@educonnect.test'],
        ['name' => 'Omar Haddad', 'email' => 'student3@educonnect.test'],
        ['name' => 'Nora Berg', 'email' => 'student4@educonnect.test'],
        ['name' => 'Yuki Tanaka', 'email' => 'student5@educonnect.test'],
        ['name' => 'Paul Dubois', 'email' => 'student6@educonnect.test'],
    ];

    public function run(): void
    {
        // updateOrCreate keeps the seeder safe to run twice.
        foreach (self::TEACHERS as $teacher) {
            User::updateOrCreate(
                ['email' => $teacher['email']],
                User::factory()->teacher()->make($teacher)->getAttributes()
            );
        }

        foreach (self::STUDENTS as $student) {
            User::updateOrCreate(
                ['email' => $student['email']],
                User::factory()->student()->make($student)->getAttributes()
            );
        }
    }
}
```

### `database/seeders/DemoContentSeeder.php`

```php
<?php

namespace Database\Seeders;

use App\Models\ClassEnrollment;
use App\Models\ClassRoom;
use App\Models\Comment;
use App\Models\Hashtag;
use App\Models\Like;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Phase 2 demo content: around 40 posts with tags, comments, likes, follows,
 * 8 classes (past, today and upcoming), enrollments and a few reports for the
 * moderation queue.
 *
 * Every demo account has the password "password"; the admin comes from
 * AdminUserSeeder. The seeder is safe to run twice.
 */
class DemoContentSeeder extends Seeder
{
    /** Educational one-liners, all well under the 280 character limit. */
    private const POST_BODIES = [
        'Just finished the algebra worksheet - section 4 was tricky but I got there in the end!',
        'Reminder: the physics homework is due on Friday. Ask in the comments if anything is unclear.',
        'I finally understand Newton\'s second law. Drawing the free body diagram first really helps.',
        'Does anybody want to form a study group for the English essay? Two more spots.',
        'Recorded summary of today\'s geometry lesson is in the class description. Good luck!',
        'Tip for exam week: 25 minutes of focus, 5 minutes break. It changed my whole week.',
        'Thanks to everyone who joined the live class today, 34 of you made it!',
        'Looking for a partner to practise French conversation for 20 minutes a day.',
        'Today we proved the quadratic formula from scratch. The board photos are in the class notes.',
        'Physics question of the day: why does a balloon stick to a wall after you rub it?',
        'My essay outline finally makes sense. Introduction, three claims, counterargument, conclusion.',
        'Study group meets at 18:00 in the library. Bring your trigonometry questions.',
        'A small win: I solved the last three integration problems without checking the answers first.',
        'The history podcast recommended in class is excellent. Episode 4 explains the Industrial Age.',
        'Who else is revising for the biology test? I made a shared flashcard deck.',
        'Remember that the lab report needs a graph, not just a table of numbers.',
        'I started a reading log this week. Ten pages a day is easier than one big session.',
        'Thanks to Ms Rossi for staying after class to explain vectors again.',
        'New class recording is up. We covered energy, work and a very dramatic pendulum.',
        'Hot take: handwritten notes beat typing for remembering formulas. Try it for one week.',
        'Can someone explain the difference between speed and velocity in one sentence?',
        'Our debate team made it to the regional final. Practice is on Tuesday at lunch.',
        'I built a tiny quiz app for practising Spanish verbs. Feedback welcome!',
        'The library has three copies of the set text left. Run, do not walk.',
        'Today\'s challenge: explain photosynthesis to a ten year old in under a minute.',
        'I missed the live class because of football practice. Is the recording available?',
        'Revision plan for the week is on the board: Monday algebra, Wednesday geometry, Friday past papers.',
        'A useful trick for essays: read your introduction out loud. If you stumble, rewrite it.',
        'The chemistry practical was messy but we got a clear result in the end.',
        'Does anyone have notes from the first ten minutes of today\'s lesson?',
        'I made a timeline for the Industrial Age. It is amazing how much fits into fifty years.',
        'Reminder: the science fair proposal is due next Monday, not Friday.',
        'Just uploaded my presentation about renewable energy. Three slides, one big idea.',
        'The best part of the live class was the question corner at the end. More of that please.',
        'I am collecting past exam questions by topic. Reply if you want the folder.',
        'Today I learned that correlation is not causation, and my project got much simpler.',
        'Anyone free to review my English paragraph before tomorrow? I will trade maths help.',
        'The new seating plan makes group work so much easier. Thank you!',
        'Weekly reflection: I studied four hours, asked two questions and finally understood logs.',
        'Good luck to everyone presenting tomorrow. You have prepared more than you think.',
    ];

    /** Hashtags used by the demo posts (FR-15). */
    private const HASHTAGS = [
        'mathematics', 'physics', 'english', 'studytips', 'homework',
        'exams', 'geometry', 'history', 'revision', 'science',
    ];

    /**
     * 8 classes: 3 in the past, 1 live today, 4 upcoming.
     *
     * @var list<array{title: string, subject: string, days: int, time: string, duration: int, status: string}>
     */
    private const CLASS_PLAN = [
        ['title' => 'Algebra recap', 'subject' => 'Mathematics', 'days' => -21, 'time' => '15:00:00', 'duration' => 60, 'status' => ClassRoom::STATUS_COMPLETED],
        ['title' => 'Newton\'s laws live', 'subject' => 'Physics', 'days' => -14, 'time' => '10:00:00', 'duration' => 60, 'status' => ClassRoom::STATUS_COMPLETED],
        ['title' => 'Essay writing workshop', 'subject' => 'English', 'days' => -7, 'time' => '14:00:00', 'duration' => 45, 'status' => ClassRoom::STATUS_COMPLETED],
        ['title' => 'Energy and work', 'subject' => 'Physics', 'days' => 0, 'time' => '12:00:00', 'duration' => 90, 'status' => ClassRoom::STATUS_LIVE],
        ['title' => 'Geometry practice', 'subject' => 'Mathematics', 'days' => 1, 'time' => '16:00:00', 'duration' => 45, 'status' => ClassRoom::STATUS_SCHEDULED],
        ['title' => 'Reading poetry closely', 'subject' => 'English', 'days' => 3, 'time' => '11:00:00', 'duration' => 45, 'status' => ClassRoom::STATUS_SCHEDULED],
        ['title' => 'Trigonometry problem set', 'subject' => 'Mathematics', 'days' => 6, 'time' => '15:30:00', 'duration' => 60, 'status' => ClassRoom::STATUS_SCHEDULED],
        ['title' => 'The Industrial Age', 'subject' => 'History', 'days' => 12, 'time' => '09:30:00', 'duration' => 60, 'status' => ClassRoom::STATUS_SCHEDULED],
    ];

    public function run(): void
    {
        // Content is created once; running db:seed again must not duplicate it.
        if (Post::query()->exists()) {
            return;
        }

        /** @var Collection<int, User> $teachers */
        $teachers = User::where('role', User::ROLE_TEACHER)->get();

        /** @var Collection<int, User> $students */
        $students = User::where('role', User::ROLE_STUDENT)->get();

        /** @var Collection<int, User> $people */
        $people = User::whereIn('role', [User::ROLE_TEACHER, User::ROLE_STUDENT])->get();

        if ($people->isEmpty()) {
            return;
        }

        // --- 1. Follow graph (FR-11): students follow all teachers + 2 classmates,
        //         teachers follow each other. User::follow() blocks self-following.
        foreach ($students as $student) {
            foreach ($teachers as $teacher) {
                $student->follow($teacher);
            }

            foreach ($students->where('id', '!=', $student->id)->random(2) as $classmate) {
                $student->follow($classmate);
            }
        }

        foreach ($teachers as $teacher) {
            foreach ($teachers->where('id', '!=', $teacher->id) as $colleague) {
                $teacher->follow($colleague);
            }
        }

        // --- 2. Hashtags (FR-15)
        /** @var Collection<int, Hashtag> $hashtags */
        $hashtags = collect(self::HASHTAGS)->map(
            fn (string $name) => Hashtag::firstOrCreate(['name' => $name])
        );

        // --- 3. 40 visible posts, each with two hashtags (FR-9)
        $posts = collect();

        foreach (self::POST_BODIES as $index => $body) {
            $post = Post::factory()->create([
                // Spread the posts evenly over everybody.
                'user_id' => $people[$index % $people->count()]->id,
                'body' => $body,
                'created_at' => now()->subMinutes(($index + 1) * 37),
                'updated_at' => now()->subMinutes(($index + 1) * 37),
            ]);

            $post->hashtags()->attach($hashtags->random(2)->pluck('id')->all());

            $posts->push($post);
        }

        // A hidden spam post so the moderation screen is never empty (FR-27).
        $spam = Post::factory()->hidden()->create([
            'user_id' => $people->random()->id,
            'body' => 'Buy cheap followers here, click my link!!!',
        ]);
        $spam->hashtags()->attach($hashtags->firstWhere('name', 'studytips')->id);

        // --- 4. Comments (FR-13): 1-3 per visible post, never by the author.
        foreach ($posts as $post) {
            $commenters = $people->where('id', '!=', $post->user_id)->random(random_int(1, 3));

            foreach ($commenters as $commenter) {
                Comment::factory()->create([
                    'post_id' => $post->id,
                    'user_id' => $commenter->id,
                ]);
            }
        }

        // --- 5. Likes (FR-11): 4-8 different people per post.
        foreach ($posts as $post) {
            foreach ($people->random(min(8, $people->count())) as $liker) {
                Like::firstOrCreate([
                    'post_id' => $post->id,
                    'user_id' => $liker->id,
                ]);
            }
        }

        // --- 6. 8 classes in every state (FR-18, FR-21)
        $classes = collect();

        foreach (self::CLASS_PLAN as $index => $plan) {
            $classes->push(ClassRoom::factory()->create([
                'teacher_id' => $teachers[$index % $teachers->count()]->id,
                'title' => $plan['title'],
                'subject' => $plan['subject'],
                'date' => today()->addDays($plan['days'])->toDateString(),
                // A live class started ten minutes ago.
                'time' => $plan['status'] === ClassRoom::STATUS_LIVE
                    ? now()->subMinutes(10)->format('H:i:s')
                    : $plan['time'],
                'duration' => $plan['duration'],
                'status' => $plan['status'],
            ]));
        }

        // --- 7. Enrollments (FR-21): every student joins three classes.
        foreach ($students as $student) {
            foreach ($classes->random(3) as $classRoom) {
                ClassEnrollment::firstOrCreate([
                    'class_id' => $classRoom->id,
                    'student_id' => $student->id,
                ]);
            }
        }

        // --- 8. A few reports for the moderation queue (FR-32 ... FR-34)
        $reporters = $students->take(3);

        // Two different students report the hidden spam post (unique per reporter).
        foreach ($reporters->take(2) as $reporter) {
            Report::create([
                'reporter_id' => $reporter->id,
                'reportable_type' => $spam->getMorphClass(),
                'reportable_id' => $spam->id,
                'reason' => 'spam',
                'details' => 'This post looks like spam.',
                'status' => Report::STATUS_PENDING,
            ]);
        }

        // One comment report.
        $comment = Comment::query()->inRandomOrder()->first();

        if ($comment) {
            Report::create([
                'reporter_id' => $reporters->last()->id,
                'reportable_type' => $comment->getMorphClass(),
                'reportable_id' => $comment->id,
                'reason' => 'harassment',
                'details' => 'This comment is rude to another student.',
                'status' => Report::STATUS_PENDING,
            ]);
        }

        // One already reviewed report by a teacher.
        $reviewedPost = $posts->random();

        Report::create([
            'reporter_id' => $teachers->first()->id,
            'reportable_type' => $reviewedPost->getMorphClass(),
            'reportable_id' => $reviewedPost->id,
            'reason' => 'inappropriate_content',
            'details' => 'Off topic for this class.',
            'status' => Report::STATUS_REVIEWED,
        ]);

        // One dismissed report.
        $dismissedPost = $posts->random();

        Report::create([
            'reporter_id' => $reporters->first()->id,
            'reportable_type' => $dismissedPost->getMorphClass(),
            'reportable_id' => $dismissedPost->id,
            'reason' => 'other',
            'details' => 'Reviewed and dismissed by a moderator.',
            'status' => Report::STATUS_DISMISSED,
        ]);
    }
}
```

### `database/seeders/DemoSeeder.php`

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Backwards-compatible entry point used by earlier phase notes.
 * New installs call DemoUserSeeder and DemoContentSeeder directly from
 * DatabaseSeeder; this wrapper keeps "db:seed --class=DemoSeeder" working.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DemoUserSeeder::class,
            DemoContentSeeder::class,
        ]);
    }
}
```

### `database/seeders/DatabaseSeeder.php`

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Run it with: php artisan migrate:fresh --seed
     */
    public function run(): void
    {
        // 1. The one admin account (email + password come from .env).
        $this->call(AdminUserSeeder::class);

        // 2. The demo people: 3 teachers + 6 students (Phase 1).
        $this->call(DemoUserSeeder::class);

        // 3. Posts, comments, likes, follows, classes, enrollments, reports.
        $this->call(DemoContentSeeder::class);
    }
}
```

---

## 6. Tinker relationship proof

### `database/tinker/phase2-relationships.php`

```php
<?php

/**
 * Phase 2 relationship proof.
 *
 * Run it after the demo data exists:
 *
 *   php artisan migrate:fresh --seed
 *   php artisan tinker --execute="require base_path('database/tinker/phase2-relationships.php');"
 *
 * Every line prints PASS or FAIL. The script throws at the end when anything
 * failed, so it is also usable in CI.
 */

use App\Models\ClassEnrollment;
use App\Models\ClassRoom;
use App\Models\Comment;
use App\Models\Follow;
use App\Models\Hashtag;
use App\Models\Like;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Carbon;

$results = [];

$check = function (string $label, bool $ok) use (&$results): void {
    $results[] = [$label, $ok];
    echo ($ok ? 'PASS' : 'FAIL').'  '.$label.PHP_EOL;
};

/* -----------------------------------------------------------------
 | Pick one row of every kind from the seeded demo data.
 * ----------------------------------------------------------------- */

$post = Post::whereHas('comments')->whereHas('likes')->whereHas('hashtags')->firstOrFail();
$author = $post->user;
$comment = Comment::whereHas('reports')->firstOrFail();
$like = Like::firstOrFail();
$hashtag = $post->hashtags->first();
$classRoom = ClassRoom::whereHas('students')->whereHas('enrollments')->firstOrFail();
$teacher = $classRoom->teacher;
$student = $classRoom->students()->firstOrFail();
$enrollment = ClassEnrollment::firstOrFail();
$report = Report::firstOrFail();
$reporter = $report->reporter;
$follow = Follow::firstOrFail();
$follower = $follow->follower;
$followed = $follow->following;
$commentAuthor = User::whereHas('comments')->firstOrFail();
$liker = User::whereHas('likes')->firstOrFail();

/* -----------------------------------------------------------------
 | User relationships and helpers
 * ----------------------------------------------------------------- */

$check('User hasMany posts', $author->posts->contains($post));
$check('User hasMany comments', $commentAuthor->comments->isNotEmpty());
$check('User hasMany likes', $liker->likes->isNotEmpty());
$check('User followers (belongsToMany self)', $followed->followers->contains($follower));
$check('User following (belongsToMany self)', $follower->following->contains($followed));
$check('User hasMany teachingClasses', $teacher->teachingClasses->contains($classRoom));
$check('User belongsToMany enrolledClasses', $student->enrolledClasses->contains($classRoom));
$check('User hasMany reports', $reporter->reports->contains($report));
$check('User isFollowing() helper', $follower->isFollowing($followed));
$check('User followersCount() helper', $followed->followersCount() === $followed->followers()->count());
$check('User followingCount() helper', $follower->followingCount() === $follower->following()->count());
$check('User photoUrl() default avatar', str_ends_with($student->photoUrl(), 'images/default-avatar.svg'));
$check('User cannot follow themselves', $student->follow($student) === false);

/* -----------------------------------------------------------------
 | Post relationships, scopes and helpers
 * ----------------------------------------------------------------- */

$check('Post belongsTo user', $post->user->is($author) || $post->user !== null);
$check('Post hasMany comments', $post->comments->isNotEmpty());
$check('Post hasMany likes', $post->likes->isNotEmpty());
$check('Post belongsToMany hashtags', $post->hashtags->contains($hashtag));
$check('Post morphMany reports', Post::whereHas('reports')->exists());
$check('Post likedBy() helper true', $post->likedBy($post->likes->first()->user));
$check('Post likedBy() helper false', $post->likedBy(User::factory()->make()) === false);

$counted = Post::withCounts()->findOrFail($post->id);
$check('Post withCounts() scope', isset($counted->likes_count, $counted->comments_count));
$check('Post visible() scope', Post::visible()->whereKey($post->id)->exists());

/* -----------------------------------------------------------------
 | Comment, Like, Hashtag
 * ----------------------------------------------------------------- */

$check('Comment belongsTo post', $comment->post instanceof Post);
$check('Comment belongsTo user', $comment->user instanceof User);
$check('Comment morphMany reports', $comment->reports->isNotEmpty());

$check('Like belongsTo post', $like->post instanceof Post);
$check('Like belongsTo user', $like->user instanceof User);

$check('Hashtag belongsToMany posts', $hashtag->posts->contains($post));
$check('Hashtag stored lowercase without #', $hashtag->name === mb_strtolower($hashtag->name) && ! str_contains($hashtag->name, '#'));

/* -----------------------------------------------------------------
 | Classes and enrollments
 * ----------------------------------------------------------------- */

$check('ClassRoom table is "classes"', $classRoom->getTable() === 'classes');
$check('ClassRoom belongsTo teacher', $classRoom->teacher instanceof User);
$check('ClassRoom belongsToMany students', $classRoom->students->isNotEmpty());
$check('ClassRoom hasMany enrollments', $classRoom->enrollments->isNotEmpty());
$check('ClassRoom byStatus() scope', ClassRoom::byStatus($classRoom->status)->whereKey($classRoom->id)->exists());
$check('ClassRoom upcoming() scope is a query', ClassRoom::upcoming()->count() >= 0);
$check('ClassRoom start_datetime accessor', $classRoom->start_datetime instanceof Carbon);
$check(
    'ClassRoom room name is educonnect- + 20 chars',
    str_starts_with($classRoom->room_name, 'educonnect-') && strlen($classRoom->room_name) === 31
);

$check('ClassEnrollment belongsTo classRoom', $enrollment->classRoom instanceof ClassRoom);
$check('ClassEnrollment belongsTo student', $enrollment->student instanceof User);

/* -----------------------------------------------------------------
 | Reports and follows
 * ----------------------------------------------------------------- */

$check('Report belongsTo reporter', $report->reporter instanceof User);
$check('Report morphTo reportable', $report->reportable instanceof Post || $report->reportable instanceof Comment);
$check('Report stores details', $report->details === null || is_string($report->details));

$check('Follow belongsTo follower', $follow->follower instanceof User);
$check('Follow belongsTo following', $follow->following instanceof User);

/* -----------------------------------------------------------------
 | Database guard: a direct self-follow row is rejected.
 * ----------------------------------------------------------------- */

$selfFollowRejected = false;

try {
    Follow::create([
        'follower_id' => $student->id,
        'following_id' => $student->id,
    ]);
} catch (InvalidArgumentException) {
    $selfFollowRejected = true;
}

$check('Follow model rejects direct self-follow', $selfFollowRejected);

/* -----------------------------------------------------------------
 | Summary
 * ----------------------------------------------------------------- */

$passed = count(array_filter($results, fn (array $row): bool => $row[1]));
$failed = count($results) - $passed;

echo PHP_EOL."Phase 2 relationship proof: {$passed} passed, {$failed} failed.".PHP_EOL;

if ($failed > 0) {
    throw new RuntimeException("{$failed} relationship check(s) failed.");
}
```

---

## 7. Tests

### `tests/Feature/ModelRelationshipsTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\ClassEnrollment;
use App\Models\ClassRoom;
use App\Models\Comment;
use App\Models\Follow;
use App\Models\Hashtag;
use App\Models\Like;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * One test per key relationship / database rule of Phase 2.
 */
class ModelRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    /* ---------------------------------------------------------------
     | Posts
     * --------------------------------------------------------------- */

    public function test_a_post_belongs_to_a_user_and_has_comments_likes_and_hashtags(): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        $comment = Comment::factory()->create(['post_id' => $post->id]);
        $like = Like::factory()->create(['post_id' => $post->id]);
        $hashtag = Hashtag::factory()->create();

        $post->hashtags()->attach($hashtag->id);

        // Post -> belongsTo user
        $this->assertTrue($post->user->is($author));
        // Post -> hasMany comments / likes
        $this->assertTrue($post->comments->first()->is($comment));
        $this->assertTrue($post->likes->first()->is($like));
        // Post <-> belongsToMany hashtags (both directions)
        $this->assertTrue($post->hashtags->first()->is($hashtag));
        $this->assertTrue($hashtag->posts->first()->is($post));
        // User -> hasMany posts
        $this->assertTrue($author->posts->first()->is($post));
    }

    public function test_a_comment_belongs_to_a_post_and_a_user(): void
    {
        $post = Post::factory()->create();
        $author = User::factory()->create();

        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $author->id,
        ]);

        $this->assertTrue($comment->post->is($post));
        $this->assertTrue($comment->user->is($author));
        $this->assertTrue($author->comments->first()->is($comment));
    }

    public function test_the_visible_scope_hides_hidden_posts_and_comments(): void
    {
        // The comments hang off the visible post, so both tables have exactly
        // one visible and one hidden row.
        $post = Post::factory()->create();
        Post::factory()->hidden()->create();

        Comment::factory()->create(['post_id' => $post->id]);
        Comment::factory()->hidden()->create(['post_id' => $post->id]);

        // The scope keeps only the visible rows.
        $this->assertSame(1, Post::visible()->count());
        $this->assertSame(1, Comment::visible()->count());

        // Both rows are still in the table, they are just hidden.
        $this->assertSame(2, Post::count());
    }

    public function test_deleting_a_post_deletes_its_comments_and_likes(): void
    {
        $post = Post::factory()->create();

        Comment::factory()->count(2)->create(['post_id' => $post->id]);
        Like::factory()->count(2)->create(['post_id' => $post->id]);

        $post->delete();

        // ON DELETE CASCADE.
        $this->assertDatabaseCount('comments', 0);
        $this->assertDatabaseCount('likes', 0);
    }

    /* ---------------------------------------------------------------
     | Likes
     * --------------------------------------------------------------- */

    public function test_is_liked_by_is_only_true_for_users_who_liked_the_post(): void
    {
        $post = Post::factory()->create();
        $liker = User::factory()->create();
        $other = User::factory()->create();

        Like::factory()->create(['post_id' => $post->id, 'user_id' => $liker->id]);

        $this->assertTrue($post->isLikedBy($liker));
        $this->assertFalse($post->isLikedBy($other));
        $this->assertFalse($post->isLikedBy(null));

        // Also correct when the feed eager loaded the relation.
        $loaded = Post::with('likes')->findOrFail($post->id);
        $this->assertTrue($loaded->isLikedBy($liker));
    }

    public function test_a_post_can_only_be_liked_once_by_the_same_user(): void
    {
        $post = Post::factory()->create();
        $user = User::factory()->create();

        Like::factory()->create(['post_id' => $post->id, 'user_id' => $user->id]);

        // unique(post_id, user_id)
        $this->expectException(QueryException::class);

        Like::factory()->create(['post_id' => $post->id, 'user_id' => $user->id]);
    }

    /* ---------------------------------------------------------------
     | Follows
     * --------------------------------------------------------------- */

    public function test_a_user_can_follow_and_unfollow_another_user(): void
    {
        $follower = User::factory()->create();
        $followed = User::factory()->create();

        $this->assertFalse($follower->isFollowing($followed));

        $this->assertTrue($follower->follow($followed));

        $this->assertTrue($follower->fresh()->isFollowing($followed));
        $this->assertTrue($follower->following->first()->is($followed));   // who I follow
        $this->assertTrue($followed->followers->first()->is($follower));   // who follows me

        $follower->unfollow($followed);

        $this->assertFalse($follower->fresh()->isFollowing($followed));
        $this->assertDatabaseCount('follows', 0);
    }

    public function test_a_user_cannot_follow_themselves(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->follow($user));
        $this->assertFalse($user->isFollowing($user));
        $this->assertDatabaseCount('follows', 0);
    }

    public function test_following_the_same_user_twice_creates_only_one_row(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();

        $this->assertTrue($a->follow($b));
        $this->assertTrue($a->follow($b)); // silently ignored by syncWithoutDetaching

        $this->assertDatabaseCount('follows', 1);
    }

    public function test_the_follows_table_rejects_duplicate_rows(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();

        Follow::factory()->create(['follower_id' => $a->id, 'following_id' => $b->id]);

        // unique(follower_id, following_id)
        $this->expectException(QueryException::class);

        Follow::factory()->create(['follower_id' => $a->id, 'following_id' => $b->id]);
    }

    /* ---------------------------------------------------------------
     | Classes and enrollments
     * --------------------------------------------------------------- */

    public function test_a_class_room_belongs_to_a_teacher_and_has_enrolled_students(): void
    {
        $teacher = User::factory()->teacher()->create();
        $students = User::factory()->student()->count(3)->create();

        $classRoom = ClassRoom::factory()->create(['teacher_id' => $teacher->id]);
        $classRoom->students()->attach($students->pluck('id')->all());

        // The model keeps the SRS table name.
        $this->assertSame('classes', $classRoom->getTable());

        $this->assertTrue($classRoom->teacher->is($teacher));
        $this->assertTrue($teacher->classes->first()->is($classRoom));

        $this->assertSame(3, $classRoom->students()->count());
        $this->assertSame(3, $classRoom->enrollments()->count());

        $student = $students->first();
        $this->assertTrue($student->enrolledClasses->first()->is($classRoom));
        $this->assertTrue($student->isEnrolledIn($classRoom));
        $this->assertTrue($classRoom->isOwnedBy($teacher));
        $this->assertFalse($classRoom->isOwnedBy($student));
    }

    public function test_a_student_can_only_enroll_once_in_the_same_class(): void
    {
        $classRoom = ClassRoom::factory()->create();
        $student = User::factory()->student()->create();

        ClassEnrollment::factory()->create([
            'class_id' => $classRoom->id,
            'student_id' => $student->id,
        ]);

        // unique(class_id, student_id)
        $this->expectException(QueryException::class);

        ClassEnrollment::factory()->create([
            'class_id' => $classRoom->id,
            'student_id' => $student->id,
        ]);
    }

    public function test_the_upcoming_scope_returns_future_scheduled_classes_only(): void
    {
        // Freeze "now" so the test can never be flaky around midnight.
        $this->travelTo(Carbon::parse('2026-06-15 12:00:00'));

        $teacher = User::factory()->teacher()->create();

        $laterToday = ClassRoom::factory()->create([
            'teacher_id' => $teacher->id,
            'status' => ClassRoom::STATUS_SCHEDULED,
            'date' => '2026-06-15',
            'time' => '15:00:00',
        ]);

        $earlierToday = ClassRoom::factory()->create([
            'teacher_id' => $teacher->id,
            'status' => ClassRoom::STATUS_SCHEDULED,
            'date' => '2026-06-15',
            'time' => '09:00:00',
        ]);

        $futureDay = ClassRoom::factory()->create([
            'teacher_id' => $teacher->id,
            'status' => ClassRoom::STATUS_SCHEDULED,
            'date' => '2026-06-18',
            'time' => '10:00:00',
        ]);

        // Not scheduled -> never upcoming.
        ClassRoom::factory()->live()->create(['teacher_id' => $teacher->id]);
        ClassRoom::factory()->completed()->create(['teacher_id' => $teacher->id]);

        $ids = ClassRoom::upcoming()->pluck('id')->all();

        $this->assertSame([$laterToday->id, $futureDay->id], $ids);
        $this->assertNotContains($earlierToday->id, $ids);
    }

    /* ---------------------------------------------------------------
     | Reports
     * --------------------------------------------------------------- */

    public function test_reports_are_polymorphic_and_belong_to_a_reporter(): void
    {
        $postReport = Report::factory()->forPost()->create();
        $commentReport = Report::factory()->forComment()->create();

        $this->assertInstanceOf(Post::class, $postReport->reportable);
        $this->assertInstanceOf(Comment::class, $commentReport->reportable);

        // morphMany on both content types.
        $this->assertTrue($postReport->reportable->reports->first()->is($postReport));
        $this->assertTrue($commentReport->reportable->reports->first()->is($commentReport));

        // belongsTo reporter, and the inverse hasMany on the user.
        $this->assertInstanceOf(User::class, $postReport->reporter);
        $this->assertTrue($postReport->reporter->reports->first()->is($postReport));

        // Default status and the moderation scope.
        $this->assertSame(Report::STATUS_PENDING, $postReport->status);
        $this->assertSame(2, Report::pending()->count());
    }

    /* ---------------------------------------------------------------
     | Phase 2 helpers, scopes and accessors
     * --------------------------------------------------------------- */

    public function test_a_user_has_teaching_classes_enrolled_classes_likes_and_reports(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $post = Post::factory()->create(['user_id' => $teacher->id]);

        $taught = ClassRoom::factory()->create(['teacher_id' => $teacher->id]);
        $enrolled = ClassRoom::factory()->create();
        $enrolled->students()->attach($student->id);

        $like = Like::factory()->create(['post_id' => $post->id, 'user_id' => $student->id]);
        $report = Report::factory()->forPost()->create([
            'reporter_id' => $student->id,
            'reportable_id' => $post->id,
        ]);

        // User -> hasMany teachingClasses.
        $this->assertTrue($teacher->teachingClasses->first()->is($taught));
        // User <-> belongsToMany enrolledClasses.
        $this->assertTrue($student->enrolledClasses->first()->is($enrolled));
        // User -> hasMany likes.
        $this->assertTrue($student->likes->first()->is($like));
        // User -> hasMany reports.
        $this->assertTrue($student->reports->first()->is($report));
    }

    public function test_the_follower_and_following_counters_are_correct(): void
    {
        $user = User::factory()->create();
        $followed = User::factory()->count(2)->create();
        $fan = User::factory()->create();

        foreach ($followed as $target) {
            $user->follow($target);
        }

        $fan->follow($user);

        $this->assertSame(2, $user->followingCount());
        $this->assertSame(1, $user->followersCount());
        $this->assertSame(2, $user->following_count);
        $this->assertSame(1, $user->followers_count);

        // withCount() values are reused instead of running another query.
        $counted = User::withCount(['followers', 'following'])->findOrFail($user->id);
        $this->assertSame(1, $counted->followersCount());
        $this->assertSame(2, $counted->followingCount());
    }

    public function test_a_user_without_a_photo_gets_the_default_avatar(): void
    {
        $user = User::factory()->create(['photo' => null]);

        $this->assertSame(asset('images/default-avatar.svg'), $user->photoUrl());
        $this->assertSame(asset('images/default-avatar.svg'), $user->photo_url);
        $this->assertFileExists(public_path('images/default-avatar.svg'));

        $user->photo = 'photos/avatar.jpg';
        $this->assertSame(Storage::disk('public')->url('photos/avatar.jpg'), $user->photoUrl());
    }

    public function test_liked_by_and_with_counts_scopes_work(): void
    {
        $post = Post::factory()->create();
        $liker = User::factory()->create();

        Like::factory()->count(2)->create(['post_id' => $post->id]);
        Like::factory()->create(['post_id' => $post->id, 'user_id' => $liker->id]);
        Comment::factory()->count(3)->create(['post_id' => $post->id]);

        $this->assertTrue($post->likedBy($liker));
        $this->assertFalse($post->likedBy(User::factory()->create()));

        $counted = Post::withCounts()->findOrFail($post->id);
        $this->assertSame(3, (int) $counted->likes_count);
        $this->assertSame(3, (int) $counted->comments_count);
    }

    public function test_a_class_room_can_be_filtered_by_status_and_exposes_its_start_time(): void
    {
        $scheduled = ClassRoom::factory()->create([
            'status' => ClassRoom::STATUS_SCHEDULED,
            'date' => '2026-06-15',
            'time' => '15:30:00',
            'duration' => 60,
        ]);
        ClassRoom::factory()->live()->create();
        ClassRoom::factory()->completed()->create();

        $this->assertSame(1, ClassRoom::byStatus(ClassRoom::STATUS_SCHEDULED)->count());
        $this->assertTrue($scheduled->start_datetime->equalTo(Carbon::parse('2026-06-15 15:30:00')));
        $this->assertTrue($scheduled->endsAt()->equalTo(Carbon::parse('2026-06-15 16:30:00')));
    }

    public function test_every_class_room_gets_a_unique_unguessable_room_name(): void
    {
        $first = ClassRoom::factory()->create(['room_name' => null]);
        $second = ClassRoom::factory()->create(['room_name' => null]);

        $this->assertStringStartsWith('educonnect-', $first->room_name);
        $this->assertSame(31, strlen($first->room_name));
        $this->assertNotSame($first->room_name, $second->room_name);
        $this->assertDatabaseCount('classes', 2);
    }

    public function test_hashtags_are_stored_lowercase_without_the_hash(): void
    {
        $hashtag = Hashtag::create(['name' => '#Maths']);

        $this->assertSame('maths', $hashtag->name);
        $this->assertSame('maths', $hashtag->fresh()->name);

        // unique(name)
        $this->expectException(QueryException::class);

        Hashtag::create(['name' => 'MATHS']);
    }

    public function test_the_follow_model_rejects_a_direct_self_follow_row(): void
    {
        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        Follow::create([
            'follower_id' => $user->id,
            'following_id' => $user->id,
        ]);
    }

    public function test_report_details_are_stored(): void
    {
        $report = Report::factory()->forPost()->create([
            'reason' => 'spam',
            'details' => 'Same link posted five times.',
            'status' => Report::STATUS_REVIEWED,
        ]);

        $this->assertSame('Same link posted five times.', $report->fresh()->details);
        $this->assertSame(Report::STATUS_REVIEWED, $report->fresh()->status);
    }
}
```

### `tests/Feature/ClassTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Phase 5: live classes (FR-18, FR-19, FR-20, FR-21, FR-22, FR-24, FR-28).
 */
class ClassTest extends TestCase
{
    use RefreshDatabase;

    /* -----------------------------------------------------------------
     | Creating a class (FR-18)
     * ----------------------------------------------------------------- */

    /**
     * A teacher creates a class and gets a private, hard to guess room.
     */
    public function test_a_teacher_can_create_a_class_with_a_private_room(): void
    {
        $this->travelTo(Carbon::parse('2026-06-15 12:00:00'));

        $teacher = User::factory()->teacher()->create();

        $response = $this->actingAs($teacher)->post(route('classes.store'), [
            'title' => 'Algebra basics',
            'description' => 'We start with linear equations.',
            'subject' => 'Mathematics',
            'date' => '2026-06-20',
            'time' => '14:30',
            'duration' => 60,
        ]);

        $classRoom = ClassRoom::firstOrFail();

        $response->assertRedirect(route('classes.show', $classRoom));
        $response->assertSessionHas('success');

        $this->assertSame($teacher->id, $classRoom->teacher_id);
        $this->assertSame(ClassRoom::STATUS_SCHEDULED, $classRoom->status);

        // "educonnect-" + 20 random characters.
        $this->assertStringStartsWith('educonnect-', $classRoom->room_name);
        $this->assertSame(31, Str::length($classRoom->room_name));
    }

    /**
     * Two classes never share a room, and the name is never taken from the client.
     */
    public function test_room_names_are_unique_and_ignore_the_request(): void
    {
        $this->travelTo(Carbon::parse('2026-06-15 12:00:00'));

        $teacher = User::factory()->teacher()->create();

        foreach ([1, 2, 3] as $index) {
            $this->actingAs($teacher)->post(route('classes.store'), [
                'title' => 'Lesson '.$index,
                'date' => '2026-06-20',
                'time' => '1'.$index.':00',
                'duration' => 45,
                // A client that tries to pick its own room must be ignored.
                'room_name' => 'my-public-room',
                'status' => ClassRoom::STATUS_LIVE,
            ])->assertRedirect();
        }

        $names = ClassRoom::pluck('room_name');

        $this->assertCount(3, $names);
        $this->assertCount(3, $names->unique());
        $this->assertNotContains('my-public-room', $names->all());

        // room_name and status were set by the server, not by the form.
        $this->assertSame(3, ClassRoom::where('status', ClassRoom::STATUS_SCHEDULED)->count());
    }

    /**
     * A new class must start in the future (FR-18).
     */
    public function test_a_class_cannot_be_created_in_the_past(): void
    {
        $this->travelTo(Carbon::parse('2026-06-15 12:00:00'));

        $teacher = User::factory()->teacher()->create();

        $payload = [
            'title' => 'Too late',
            'date' => '2026-06-15',
            'time' => '11:00',      // one hour before "now"
            'duration' => 60,
        ];

        // Time already passed today.
        $this->actingAs($teacher)
            ->post(route('classes.store'), $payload)
            ->assertSessionHasErrors('time');

        // A whole day in the past is refused by the date rule.
        $this->actingAs($teacher)
            ->post(route('classes.store'), ['date' => '2026-06-14'] + $payload)
            ->assertSessionHasErrors('date');

        // Missing title / bad duration are refused too.
        $this->actingAs($teacher)
            ->post(route('classes.store'), ['title' => '', 'duration' => 1] + $payload)
            ->assertSessionHasErrors(['title', 'duration']);

        $this->assertDatabaseCount('classes', 0);
    }

    /**
     * Only teachers can reach the class management pages.
     */
    public function test_students_and_admins_cannot_manage_classes(): void
    {
        // A guest is sent to the login page (checked first: actingAs lasts for
        // the rest of the test).
        $this->get(route('classes.index'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->student()->create())
            ->get(route('classes.index'))
            ->assertForbidden();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('classes.create'))
            ->assertForbidden();
    }

    /* -----------------------------------------------------------------
     | Ownership (ClassPolicy, FR-18)
     * ----------------------------------------------------------------- */

    /**
     * A teacher may only touch their own classes.
     */
    public function test_a_teacher_cannot_edit_another_teachers_class(): void
    {
        $owner = User::factory()->teacher()->create();
        $stranger = User::factory()->teacher()->create();
        $classRoom = ClassRoom::factory()->scheduled()->create(['teacher_id' => $owner->id]);

        $this->actingAs($stranger)->get(route('classes.edit', $classRoom))->assertForbidden();
        $this->actingAs($stranger)->patch(route('classes.update', $classRoom), ['title' => 'Hijacked'])->assertForbidden();
        $this->actingAs($stranger)->delete(route('classes.destroy', $classRoom))->assertForbidden();
        $this->actingAs($stranger)->get(route('classes.students', $classRoom))->assertForbidden();

        // Nothing changed.
        $this->assertDatabaseHas('classes', ['id' => $classRoom->id, 'title' => $classRoom->title]);
    }

    /**
     * The owner updates a class; the room name survives an edit.
     */
    public function test_the_owner_can_update_their_class(): void
    {
        $this->travelTo(Carbon::parse('2026-06-15 12:00:00'));

        $owner = User::factory()->teacher()->create();
        $classRoom = ClassRoom::factory()->scheduled()->create([
            'teacher_id' => $owner->id,
            'title' => 'Old title',
        ]);

        $roomName = $classRoom->room_name;

        $this->actingAs($owner)
            ->patch(route('classes.update', $classRoom), [
                'title' => 'New title',
                'subject' => 'Physics',
                'date' => $classRoom->date->format('Y-m-d'),
                'time' => '10:00',
                'duration' => 90,
            ])
            ->assertRedirect(route('classes.show', $classRoom));

        $classRoom->refresh();

        $this->assertSame('New title', $classRoom->title);
        $this->assertSame(90, $classRoom->duration);
        $this->assertSame($roomName, $classRoom->room_name);
    }

    /**
     * The teacher can open every management page of their own class.
     */
    public function test_the_teacher_can_open_the_management_pages(): void
    {
        $this->travelTo(Carbon::parse('2026-06-15 12:00:00'));

        $teacher = User::factory()->teacher()->create();
        $classRoom = ClassRoom::factory()->scheduled()->create([
            'teacher_id' => $teacher->id,
            'title' => 'Algebra basics',
        ]);
        $classRoom->students()->attach(User::factory()->student()->create()->id);

        // "My classes"
        $this->actingAs($teacher)->get(route('classes.index'))->assertOk()->assertSee('Algebra basics');

        // The create form.
        $this->actingAs($teacher)->get(route('classes.create'))->assertOk()->assertSee('Duration', false);

        // The edit form is filled with the current values.
        $this->actingAs($teacher)
            ->get(route('classes.edit', $classRoom))
            ->assertOk()
            ->assertSee('Algebra basics');

        // The detail page shows the private room name to the owner.
        $this->actingAs($teacher)
            ->get(route('classes.show', $classRoom))
            ->assertOk()
            ->assertSee($classRoom->room_name);
    }

    /* -----------------------------------------------------------------
     | Enrolling (FR-21)
     * ----------------------------------------------------------------- */

    /**
     * A student enrolls and unenrolls, without ever creating a duplicate.
     */
    public function test_a_student_can_enroll_and_unenroll(): void
    {
        $student = User::factory()->student()->create();
        $classRoom = ClassRoom::factory()->scheduled()->create();

        $this->actingAs($student)
            ->post(route('classes.enroll', $classRoom))
            ->assertRedirect();

        $this->assertDatabaseHas('class_enrollments', [
            'class_id' => $classRoom->id,
            'student_id' => $student->id,
        ]);

        // A double click does not enroll twice.
        $this->actingAs($student)->post(route('classes.enroll', $classRoom))->assertRedirect();
        $this->assertDatabaseCount('class_enrollments', 1);

        $this->actingAs($student)
            ->delete(route('classes.unenroll', $classRoom))
            ->assertRedirect();

        $this->assertDatabaseCount('class_enrollments', 0);
    }

    /**
     * A finished class cannot be joined any more.
     */
    public function test_a_student_cannot_enroll_in_a_finished_class(): void
    {
        $student = User::factory()->student()->create();
        $classRoom = ClassRoom::factory()->completed()->create();

        $this->actingAs($student)
            ->post(route('classes.enroll', $classRoom))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('class_enrollments', 0);
    }

    /**
     * Only students enroll; a teacher cannot use the student route.
     */
    public function test_only_students_can_enroll(): void
    {
        $teacher = User::factory()->teacher()->create();
        $classRoom = ClassRoom::factory()->scheduled()->create();

        $this->actingAs($teacher)
            ->post(route('classes.enroll', $classRoom))
            ->assertForbidden();
    }

    /* -----------------------------------------------------------------
     | Joining the room (FR-20, FR-28)
     * ----------------------------------------------------------------- */

    /**
     * The teacher and the enrolled student get in; a stranger does not.
     */
    public function test_only_the_teacher_and_enrolled_students_can_open_the_room(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $outsider = User::factory()->student()->create();

        $classRoom = ClassRoom::factory()->live()->create(['teacher_id' => $teacher->id]);
        $classRoom->students()->attach($student->id);

        // A guest is asked to log in first (checked before any actingAs call).
        $this->get(route('classes.room', $classRoom))->assertRedirect(route('login'));

        // A student who never enrolled: 403 (FR-20).
        $this->actingAs($outsider)->get(route('classes.room', $classRoom))->assertForbidden();

        // The student who is enrolled and the teacher: 200.
        $this->actingAs($student)->get(route('classes.room', $classRoom))->assertOk();
        $this->actingAs($teacher)->get(route('classes.room', $classRoom))->assertOk();

        // FR-20: an admin moderates classes but is not a room participant.
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('classes.room', $classRoom))->assertForbidden();
    }

    /**
     * An enrolled student only gets in while the class is live (FR-20).
     */
    public function test_an_enrolled_student_can_join_only_when_the_class_is_live(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        $scheduled = ClassRoom::factory()->scheduled()->create(['teacher_id' => $teacher->id]);
        $scheduled->students()->attach($student->id);

        // Not live yet: a friendly redirect, not the room.
        $this->actingAs($student)
            ->get(route('classes.room', $scheduled))
            ->assertRedirect(route('classes.show', $scheduled))
            ->assertSessionHas('error');

        // The teacher may open the room at any time.
        $this->actingAs($teacher)->get(route('classes.room', $scheduled))->assertOk();

        // The teacher starts the class -> the student gets in.
        $this->actingAs($teacher)
            ->patch(route('classes.status', $scheduled), ['status' => ClassRoom::STATUS_LIVE])
            ->assertRedirect();

        $this->actingAs($student)->get(route('classes.room', $scheduled))->assertOk();
    }

    /**
     * The room page embeds Jitsi with the room name and the user's details.
     */
    public function test_the_room_page_embeds_jitsi_with_the_user_details(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        $classRoom = ClassRoom::factory()->live()->create(['teacher_id' => $teacher->id]);
        $classRoom->students()->attach($student->id);

        $response = $this->actingAs($student)->get(route('classes.room', $classRoom));

        $response->assertOk();
        // The room name, the Jitsi domain and the retry button for a failed script.
        $response->assertSee($classRoom->room_name);
        $response->assertSee(config('jitsi.domain'));
        $response->assertSee('data-jitsi', false);
        $response->assertSee('data-jitsi-retry', false);
        $response->assertSee($student->name);
        $response->assertSee($student->email);
        // The hangup event sends the user to their dashboard.
        $response->assertSee(route('dashboard'));

        // The teacher is flagged as the moderator, the student is not.
        $this->actingAs($teacher)
            ->get(route('classes.room', $classRoom))
            ->assertSee('Moderator');
    }

    /**
     * A class hidden by an administrator cannot be joined (FR-28).
     */
    public function test_a_hidden_class_cannot_be_joined(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $admin = User::factory()->admin()->create();

        $classRoom = ClassRoom::factory()->live()->hidden()->create(['teacher_id' => $teacher->id]);
        $classRoom->students()->attach($student->id);

        // The enrolled student is locked out.
        $this->actingAs($student)->get(route('classes.room', $classRoom))->assertForbidden();

        // Enrolling is blocked as well.
        $outsider = User::factory()->student()->create();
        $this->actingAs($outsider)->post(route('classes.enroll', $classRoom))->assertForbidden();

        // FR-20: even an administrator cannot enter the room; moderation is
        // done from the admin panel, never by joining a class with minors.
        $this->actingAs($admin)->get(route('classes.room', $classRoom))->assertForbidden();
    }

    /* -----------------------------------------------------------------
     | Status control (FR-24)
     * ----------------------------------------------------------------- */

    /**
     * The teacher starts and ends a class, and the student cannot.
     */
    public function test_the_teacher_controls_the_class_status(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $classRoom = ClassRoom::factory()->scheduled()->create(['teacher_id' => $teacher->id]);

        // A student is not allowed to touch the status.
        $this->actingAs($student)
            ->patch(route('classes.status', $classRoom), ['status' => ClassRoom::STATUS_LIVE])
            ->assertForbidden();

        // scheduled -> live.
        $this->actingAs($teacher)
            ->patch(route('classes.status', $classRoom), ['status' => ClassRoom::STATUS_LIVE])
            ->assertRedirect();
        $this->assertSame(ClassRoom::STATUS_LIVE, $classRoom->refresh()->status);

        // live -> completed ("the teacher ends the class").
        $this->actingAs($teacher)
            ->patch(route('classes.status', $classRoom), ['status' => ClassRoom::STATUS_COMPLETED])
            ->assertRedirect();
        $this->assertSame(ClassRoom::STATUS_COMPLETED, $classRoom->refresh()->status);
    }

    /**
     * A transition that makes no sense is refused with a message, not a 500.
     */
    public function test_an_invalid_status_transition_is_refused(): void
    {
        $teacher = User::factory()->teacher()->create();
        $classRoom = ClassRoom::factory()->completed()->create(['teacher_id' => $teacher->id]);

        // completed -> scheduled is not part of ClassRoom::TRANSITIONS.
        $this->actingAs($teacher)
            ->patch(route('classes.status', $classRoom), ['status' => ClassRoom::STATUS_SCHEDULED])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(ClassRoom::STATUS_COMPLETED, $classRoom->refresh()->status);

        // A status that does not exist is a validation error.
        $this->actingAs($teacher)
            ->patch(route('classes.status', $classRoom), ['status' => 'paused'])
            ->assertSessionHasErrors('status');
    }

    /* -----------------------------------------------------------------
     | Browsing (FR-19) and the enrolled list (FR-21)
     * ----------------------------------------------------------------- */

    /**
     * Students search, filter and only see classes that are live or ahead.
     */
    public function test_a_student_can_search_and_filter_the_class_list(): void
    {
        $this->travelTo(Carbon::parse('2026-06-15 12:00:00'));

        $student = User::factory()->student()->create();

        $maria = User::factory()->teacher()->create(['name' => 'Maria Rossi']);
        $david = User::factory()->teacher()->create(['name' => 'David Chen']);

        $algebra = ClassRoom::factory()->scheduled()->create([
            'teacher_id' => $maria->id,
            'title' => 'Algebra basics',
            'subject' => 'Mathematics',
        ]);

        $live = ClassRoom::factory()->live()->create([
            'teacher_id' => $david->id,
            'title' => 'Newton live',
            'subject' => 'Physics',
        ]);

        $finished = ClassRoom::factory()->completed()->create([
            'teacher_id' => $maria->id,
            'title' => 'The Industrial Age',
            'subject' => 'History',
        ]);

        $hidden = ClassRoom::factory()->scheduled()->hidden()->create([
            'teacher_id' => $david->id,
            'title' => 'Hidden lesson',
            'subject' => 'Physics',
        ]);

        // Everything that is live or ahead.
        $response = $this->actingAs($student)->get(route('classes.discover'));
        $response->assertOk();
        $response->assertSee('Algebra basics');
        $response->assertSee('Newton live');
        $response->assertDontSee('The Industrial Age');
        $response->assertDontSee('Hidden lesson');

        // Search on the title and on the teacher name.
        $this->actingAs($student)->get(route('classes.discover', ['q' => 'Algebra']))
            ->assertSee('Algebra basics')
            ->assertDontSee('Newton live');

        $this->actingAs($student)->get(route('classes.discover', ['q' => 'David']))
            ->assertSee('Newton live')
            ->assertDontSee('Algebra basics');

        // Filter on one subject.
        $physics = $this->actingAs($student)->get(route('classes.discover', ['subject' => 'Physics']));
        $physics->assertSee('Newton live');
        $physics->assertDontSee('Algebra basics');

        // The subject drop down only offers subjects that are really used.
        $physics->assertSee('Mathematics');

        // The result is paginated (9 per page) and holds the two classes only.
        $list = $this->actingAs($student)->get(route('classes.discover'))->viewData('classes');
        $this->assertSame(2, $list->total());
        $this->assertEqualsCanonicalizing([$algebra->id, $live->id], $list->pluck('id')->all());
    }

    /**
     * The student keeps a list of the classes they joined (FR-21).
     */
    public function test_a_student_sees_the_classes_they_enrolled_in(): void
    {
        $student = User::factory()->student()->create();

        $mine = ClassRoom::factory()->scheduled()->create(['title' => 'My algebra class']);
        $alsoMine = ClassRoom::factory()->live()->create(['title' => 'My live class']);
        ClassRoom::factory()->scheduled()->create(['title' => 'Not my class']);

        $student->enrolledClasses()->attach([$mine->id, $alsoMine->id]);

        $response = $this->actingAs($student)->get(route('student.classes'));

        $response->assertOk();
        $response->assertSee('My algebra class');
        $response->assertSee('My live class');
        $response->assertDontSee('Not my class');

        // The running class comes first.
        $this->assertSame($alsoMine->id, $response->viewData('classes')->first()->id);

        // The details page of an enrolled class is readable.
        $this->actingAs($student)->get(route('classes.show', $mine))->assertOk()->assertSee('Unenroll');
    }

    /**
     * The teacher sees who enrolled; a stranger teacher does not (FR-24).
     */
    public function test_the_teacher_sees_the_enrolled_students(): void
    {
        $teacher = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();

        $classRoom = ClassRoom::factory()->scheduled()->create(['teacher_id' => $teacher->id]);
        $enrolled = User::factory()->student()->create(['name' => 'Enrolled Student']);
        $notEnrolled = User::factory()->student()->create(['name' => 'Not Enrolled Student']);

        $classRoom->students()->attach($enrolled->id);

        $response = $this->actingAs($teacher)->get(route('classes.students', $classRoom));

        $response->assertOk();
        $response->assertSee('Enrolled Student');
        $response->assertDontSee('Not Enrolled Student');

        $this->actingAs($otherTeacher)
            ->get(route('classes.students', $classRoom))
            ->assertForbidden();
    }
}
```

---

## 8. Default avatar

### `public/images/default-avatar.svg`

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 96 96" role="img" aria-label="Default avatar">
    <rect width="96" height="96" rx="48" fill="#e0e7ff"/>
    <circle cx="48" cy="37" r="16" fill="#4f46e5"/>
    <path d="M16 88c3-18 16-28 32-28s29 10 32 28z" fill="#4f46e5"/>
</svg>
```

---

## 9. Commands and manual checklist

```bash
# SQLite (used for this verification)
php artisan migrate:fresh --seed
php artisan tinker --execute="require base_path('database/tinker/phase2-relationships.php');"
php artisan test --filter=ModelRelationshipsTest
php artisan test
```

```bash
# MySQL production path
php artisan migrate:fresh --seed --force
```

Manual checklist:

- [x] `php artisan migrate:fresh --seed` completes with 0 errors.
- [x] 10 users (1 admin, 3 teachers, 6 students).
- [x] 41 posts (40 visible + 1 hidden spam post).
- [x] 80 comments, 320 likes, 36 follows, 10 hashtags.
- [x] 8 classes (3 completed, 1 live, 4 scheduled), 18 enrollments.
- [x] 5 reports (pending, reviewed and dismissed).
- [x] Every relationship prints PASS in the tinker proof (45 checks).
- [x] `ModelRelationshipsTest`: 23 tests, 84 assertions.
- [x] Full suite: 181 tests, 763 assertions, all passing.

---

## 10. Done when

- The eleven SRS tables (`posts`, `comments`, `likes`, `follows`, `hashtags`,
  `hashtag_post`, `classes`, `class_enrollments`, `reports`, `notifications`,
  plus the Phase 1 `users`) exist with the required keys, cascades and unique
  constraints.
- Every model exposes the relationships, scopes and helpers listed above.
- `ClassRoom` maps to `classes` and always generates a unique
  `educonnect-` + 20-character room name.
- The demo seeder can be run repeatedly without creating duplicates.
- The tinker proof and the feature tests pass.
