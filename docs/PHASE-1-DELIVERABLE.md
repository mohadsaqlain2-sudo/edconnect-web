# EduConnect — Phase 1 deliverable (project setup, auth, roles, seeders)

This document contains the full source of every file that makes up Phase 1 in the
delivered repository. Paths are relative to the project root.

> The delivered repository runs on Laravel 12.70.2 (PHP 8.2+), not Laravel 11.
> The fresh-install commands in the chat answer target Laravel 11 as requested;
> the application code below is framework-version agnostic and is what is
> verified by the test suite (172 tests, 732 assertions).

## Setup files


## `composer.json`

```json
{
    "$schema": "https://getcomposer.org/schema.json",
    "name": "laravel/laravel",
    "type": "project",
    "description": "The skeleton application for the Laravel framework.",
    "keywords": ["laravel", "framework"],
    "license": "MIT",
    "require": {
        "php": "^8.2",
        "laravel/framework": "^12.0",
        "laravel/tinker": "^2.10.1"
    },
    "require-dev": {
        "fakerphp/faker": "^1.23",
        "laravel/breeze": "^2.4",
        "laravel/pail": "^1.2.2",
        "laravel/pint": "^1.13",
        "laravel/sail": "^1.41",
        "mockery/mockery": "^1.6",
        "nunomaduro/collision": "^8.6",
        "phpunit/phpunit": "^11.5.3"
    },
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Database\\Factories\\": "database/factories/",
            "Database\\Seeders\\": "database/seeders/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Tests\\": "tests/"
        }
    },
    "scripts": {
        "post-autoload-dump": [
            "Illuminate\\Foundation\\ComposerScripts::postAutoloadDump",
            "@php artisan package:discover --ansi"
        ],
        "post-update-cmd": [
            "@php artisan vendor:publish --tag=laravel-assets --ansi --force"
        ],
        "post-root-package-install": [
            "@php -r \"file_exists('.env') || copy('.env.example', '.env');\""
        ],
        "post-create-project-cmd": [
            "@php artisan key:generate --ansi",
            "@php -r \"file_exists('database/database.sqlite') || touch('database/database.sqlite');\"",
            "@php artisan migrate --graceful --ansi"
        ],
        "dev": [
            "Composer\\Config::disableProcessTimeout",
            "npx concurrently -c \"#93c5fd,#c4b5fd,#fb7185,#fdba74\" \"php artisan serve\" \"php artisan queue:listen --tries=1\" \"php artisan pail --timeout=0\" \"npm run dev\" --names=server,queue,logs,vite"
        ]
    },
    "extra": {
        "branch-alias": {
            "dev-master": "12.x-dev"
        },
        "laravel": {
            "dont-discover": []
        }
    },
    "config": {
        "optimize-autoloader": true,
        "preferred-install": "dist",
        "sort-packages": true,
        "allow-plugins": {
            "pestphp/pest-plugin": true,
            "php-http/discovery": true
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}

```

## `package.json`

```json
{
    "private": true,
    "type": "module",
    "scripts": {
        "build": "vite build",
        "dev": "vite"
    },
    "devDependencies": {
        "axios": "^1.7.4",
        "concurrently": "^9.0.1",
        "laravel-vite-plugin": "^1.2.0",
        "sass": "^1.77.0",
        "vite": "^6.0.11"
    },
    "dependencies": {
        "@popperjs/core": "^2.11.8",
        "alpinejs": "^3.4.2",
        "bootstrap": "^5.3.8",
        "bootstrap-icons": "^1.13.2",
        "chart.js": "^4.6.1"
    }
}

```

## `vite.config.js`

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            // Bootstrap 5 is compiled from SCSS; both entries are loaded by @vite().
            input: ['resources/sass/app.scss', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    css: {
        preprocessorOptions: {
            scss: {
                // Let Sass find `bootstrap` inside node_modules.
                loadPaths: ['node_modules'],
                quietDeps: true,
                // Bootstrap 5.3 still uses @import internally; hide those notices.
                silenceDeprecations: ['import', 'global-builtin', 'color-functions'],
            },
        },
    },
});

```

## `.env.example`

```bash
APP_NAME=EduConnect
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8010

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file
# APP_MAINTENANCE_STORE=database

PHP_CLI_SERVER_WORKERS=4

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

# --- Database: MySQL 8 ---
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=educonnect
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
# Notifications are Laravel queued notifications.
# Local/demo default: "sync" writes them immediately, no worker needed.
# Production: "database" plus a running queue worker (docs/DEPLOYMENT.md).
QUEUE_CONNECTION=sync

CACHE_STORE=database

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# --- Mail ---
# "log" writes the emails to storage/logs/laravel.log (fine for local work).
MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="no-reply@educonnect.test"
MAIL_FROM_NAME="${APP_NAME}"

# Mailtrap - uncomment to send real test emails:
# MAIL_MAILER=smtp
# MAIL_HOST=sandbox.smtp.mailtrap.io
# MAIL_PORT=2525
# MAIL_USERNAME=
# MAIL_PASSWORD=

# --- Jitsi Meet (FR-18) ---
# Public server by default; set your own domain to self host the live classes.
JITSI_DOMAIN=meet.jit.si

# --- EduConnect: the admin account created by AdminUserSeeder ---
# Set a long random password here. Never commit the real one.
ADMIN_NAME="EduConnect Admin"
ADMIN_EMAIL=admin@educonnect.test
ADMIN_PASSWORD=

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="${APP_NAME}"

```

## `bootstrap/app.php`

```php
<?php

use App\Http\Middleware\EnsureUserIsNotBlocked;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Short names so routes can use ->middleware('role:admin') and
        // ->middleware('not.blocked').
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'not.blocked' => EnsureUserIsNotBlocked::class,
        ]);

        // Run the block check on EVERY web request: a user who is blocked while
        // logged in is kicked out immediately, on any page (FR-6).
        $middleware->appendToGroup('web', EnsureUserIsNotBlocked::class);

        // Security headers on every HTML response (SRS 5.2).
        $middleware->appendToGroup('web', SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

```

## `config/educonnect.php`

```php
<?php

/*
|--------------------------------------------------------------------------
| EduConnect settings
|--------------------------------------------------------------------------
| Small application specific settings kept in one place so that no secret
| is ever hardcoded inside a class. Values come from the .env file.
*/

return [

    // The administrator account that AdminUserSeeder creates.
    'admin' => [
        'name' => env('ADMIN_NAME', 'EduConnect Admin'),
        'email' => env('ADMIN_EMAIL', 'admin@educonnect.test'),
        // Never hardcode this. It must be set in .env, otherwise seeding fails loudly.
        'password' => env('ADMIN_PASSWORD'),
    ],

    // Upload rules for profile photos (used by ProfileUpdateRequest and the controller).
    'uploads' => [
        'profile_photo_dir' => 'profiles',                    // -> storage/app/public/profiles
        'profile_photo_max_kb' => 2048,                       // 2 MB
        'profile_photo_mimes' => ['jpg', 'jpeg', 'png', 'webp'],
    ],

];

```

## Database

## `database/migrations/0001_01_01_000000_create_users_table.php`

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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};

```

## `database/migrations/2026_10_29_000001_add_profile_fields_to_users_table.php`

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

## `database/factories/UserFactory.php`

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

## `database/seeders/DatabaseSeeder.php`

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Run it with: php artisan db:seed
     */
    public function run(): void
    {
        // The one admin account (email + password come from .env).
        $this->call(AdminUserSeeder::class);

        // Demo people: 3 teachers and 6 students.
        $this->call(DemoSeeder::class);
    }
}

```

## `database/seeders/AdminUserSeeder.php`

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

## `database/seeders/DemoSeeder.php`

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
 * Demo content so a fresh install already has people, posts and classes to look at.
 * Every demo account is created through the factory and has the password
 * "password" (hashed by the User model).
 */
class DemoSeeder extends Seeder
{
    /**
     * 3 teachers and 6 students.
     */
    private const TEACHERS = [
        ['name' => 'Maria Rossi', 'email' => 'teacher1@educonnect.test', 'subject' => 'Mathematics'],
        ['name' => 'David Chen', 'email' => 'teacher2@educonnect.test', 'subject' => 'Physics'],
        ['name' => 'Amina Yusuf', 'email' => 'teacher3@educonnect.test', 'subject' => 'English'],
    ];

    private const STUDENTS = [
        ['name' => 'Sam Carter', 'email' => 'student1@educonnect.test'],
        ['name' => 'Lina Gomez', 'email' => 'student2@educonnect.test'],
        ['name' => 'Omar Haddad', 'email' => 'student3@educonnect.test'],
        ['name' => 'Nora Berg', 'email' => 'student4@educonnect.test'],
        ['name' => 'Yuki Tanaka', 'email' => 'student5@educonnect.test'],
        ['name' => 'Paul Dubois', 'email' => 'student6@educonnect.test'],
    ];

    /**
     * Hashtags used by the demo posts (FR-15).
     */
    private const HASHTAGS = ['mathematics', 'physics', 'english', 'studytips', 'homework', 'exams'];

    /**
     * Sample post bodies for the feed (FR-9). Keep them under 280 characters.
     */
    private const POST_BODIES = [
        'Just finished the algebra worksheet - section 4 was tricky but I got there in the end!',
        'Reminder: the physics homework is due on Friday. Ask in the comments if anything is unclear.',
        'I finally understand Newton\'s second law. Drawing the free body diagram first really helps.',
        'Does anybody want to form a study group for the English essay? Two more spots.',
        'Recorded summary of today\'s geometry lesson is in the class description. Good luck!',
        'Tip for exam week: 25 minutes of focus, 5 minutes break. It changed my whole week.',
        'Thanks to everyone who joined the live class today, 34 of you made it!',
        'Looking for a partner to practise French conversation for 20 minutes a day.',
    ];

    /**
     * The classes that get scheduled (FR-18), one per status.
     */
    private const CLASS_PLAN = [
        ['title' => 'Algebra basics', 'subject' => 'Mathematics', 'status' => 'scheduled', 'room' => 'math-algebra-basics'],
        ['title' => 'Geometry practice', 'subject' => 'Mathematics', 'status' => 'scheduled', 'room' => 'math-geometry-practice'],
        ['title' => 'Essay writing workshop', 'subject' => 'English', 'status' => 'scheduled', 'room' => 'english-essay-writing'],
        ['title' => 'Newton\'s laws live', 'subject' => 'Physics', 'status' => 'live', 'room' => 'physics-newtons-laws'],
        ['title' => 'The Industrial Age', 'subject' => 'History', 'status' => 'completed', 'room' => 'history-industrial-age'],
    ];

    public function run(): void
    {
        // --- 1. People -----------------------------------------------------------------
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

        // --- 2. Social and class content -----------------------------------------------
        // This part is created only once, so a second "db:seed" does not duplicate it.
        if (Post::query()->exists()) {
            return;
        }

        /** @var Collection<int, User> $teachers */
        $teachers = User::where('role', User::ROLE_TEACHER)->get();

        /** @var Collection<int, User> $students */
        $students = User::where('role', User::ROLE_STUDENT)->get();

        /** @var Collection<int, User> $people Teachers and students together. */
        $people = User::whereIn('role', [User::ROLE_TEACHER, User::ROLE_STUDENT])->get();

        // --- 3. Follows (FR-17): every student follows all 3 teachers and 2 classmates.
        foreach ($students as $student) {
            foreach ($teachers as $teacher) {
                $student->follow($teacher);
            }

            foreach ($students->where('id', '!=', $student->id)->random(2) as $classmate) {
                $student->follow($classmate);
            }
        }

        // --- 4. Hashtags (FR-15)
        $hashtags = collect(self::HASHTAGS)->map(
            fn (string $name) => Hashtag::firstOrCreate(['name' => $name])
        );

        // --- 5. Posts, each with two hashtags (FR-9)
        $posts = collect();

        foreach (self::POST_BODIES as $body) {
            $post = Post::factory()->create([
                'user_id' => $people->random()->id,
                'body' => $body,
            ]);

            $post->hashtags()->attach($hashtags->random(2)->pluck('id')->all());

            $posts->push($post);
        }

        // One hidden post so the moderation screen already has something in it (FR-27).
        $hiddenPost = Post::factory()->hidden()->create([
            'user_id' => $people->random()->id,
            'body' => 'Buy cheap followers here, click my link!!!',
        ]);
        $posts->push($hiddenPost);

        // --- 6. Comments (FR-13): two per post.
        foreach ($posts as $post) {
            Comment::factory()->count(2)->create([
                'post_id' => $post->id,
                'user_id' => $people->random()->id,
            ]);
        }

        // --- 7. Likes (FR-11): three different people like every post.
        foreach ($posts as $post) {
            foreach ($people->random(3) as $liker) {
                Like::firstOrCreate([
                    'post_id' => $post->id,
                    'user_id' => $liker->id,
                ]);
            }
        }

        // --- 8. Classes in all three statuses (FR-18, FR-24)
        $classes = collect();

        foreach (self::CLASS_PLAN as $index => $plan) {
            $factory = match ($plan['status']) {
                ClassRoom::STATUS_LIVE => ClassRoom::factory()->live(),
                ClassRoom::STATUS_COMPLETED => ClassRoom::factory()->completed(),
                default => ClassRoom::factory()->scheduled(),
            };

            $classes->push($factory->create([
                // Spread the classes over the available teachers.
                'teacher_id' => $teachers[$index % $teachers->count()]->id,
                'title' => $plan['title'],
                'subject' => $plan['subject'],
                'room_name' => $plan['room'],
            ]));
        }

        // --- 9. Enrollments (FR-21): every student joins three classes.
        foreach ($students as $student) {
            foreach ($classes->random(3) as $classRoom) {
                ClassEnrollment::firstOrCreate([
                    'class_id' => $classRoom->id,
                    'student_id' => $student->id,
                ]);
            }
        }

        // --- 10. Reports for the moderation queue (FR-32 ... FR-34)
        // Two different students report the same spam post. The reporters must
        // differ because (reporter, content) is unique since the moderation phase.
        foreach ($students->random(2) as $reporter) {
            Report::factory()->create([
                'reporter_id' => $reporter->id,
                'reportable_type' => Post::class,
                'reportable_id' => $hiddenPost->id,
                'reason' => 'spam',
                'details' => 'This post looks like spam.',
            ]);
        }

        // Report one of the comments created above (no factory-made rows: the
        // demo data must keep exactly 3 teachers and 6 students).
        Report::factory()->create([
            'reporter_id' => $students->random()->id,
            'reportable_type' => Comment::class,
            'reportable_id' => Comment::query()->inRandomOrder()->value('id'),
            'reason' => 'harassment',
            'details' => 'This comment is rude to another student.',
        ]);

        Report::factory()->reviewed()->create([
            'reporter_id' => $teachers->random()->id,
            'reportable_type' => Post::class,
            'reportable_id' => $posts->random()->id,
            'reason' => 'inappropriate_content',
            'details' => 'Off topic for this class.',
        ]);
    }
}

```

## Model, validation, controllers, middleware

## `app/Models/User.php`

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
     * Public URL of the uploaded profile photo.
     * Returns null when the user has not uploaded one yet.
     */
    public function photoUrl(): ?string
    {
        return $this->photo ? Storage::disk('public')->url($this->photo) : null;
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
    public function classes(): HasMany
    {
        return $this->hasMany(ClassRoom::class, 'teacher_id');
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

## `app/Http/Requests/Auth/RegisterRequest.php`

```php
<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Everyone is allowed to register.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for creating a new account.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Password::defaults()],

            // Only teacher or student. A tampered "admin" value is rejected here,
            // so nobody can promote themselves through the public form.
            'role' => ['required', Rule::in(User::REGISTERABLE_ROLES)],

            // The subject is optional at registration (teachers can add or change
            // it later from their profile, FR-4).
            'subject' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * Friendlier messages for the most common mistakes.
     */
    public function messages(): array
    {
        return [
            'role.in' => 'Please choose either Teacher or Student.',
        ];
    }

    /**
     * Extra safety net: even if someone crafts a request by hand, an "admin"
     * role is thrown away before the controller ever sees it.
     */
    protected function passedValidation(): void
    {
        if (! in_array($this->string('role')->toString(), User::REGISTERABLE_ROLES, true)) {
            abort(403, 'This role cannot be registered.');
        }
    }
}

```

## `app/Http/Requests/Auth/LoginRequest.php`

```php
<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        // FR-6: a blocked account may not log in, even with the correct password.
        if (Auth::user()->is_blocked) {
            // Never leave a session behind.
            Auth::guard('web')->logout();

            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'Your account has been blocked by an administrator. Please contact support.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}

```

## `app/Http/Controllers/Auth/RegisteredUserController.php`

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration form.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Create the account and log the new user in.
     * The role can only be "teacher" or "student": the Form Request already
     * rejected anything else, so an admin account can never be registered here.
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        // $request->safe() only contains the fields listed in the Form Request.
        $validated = $request->safe()->only(['name', 'email', 'password', 'role', 'subject']);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'], // bcrypted by the model's "hashed" cast
            'role' => $validated['role'],
            // The subject only applies to teachers.
            'subject' => $validated['role'] === User::ROLE_TEACHER ? ($validated['subject'] ?? null) : null,
        ]);

        event(new Registered($user));

        Auth::login($user);

        // FR-2: every role lands on its own dashboard.
        return redirect(route($user->dashboardRoute(), absolute: false));
    }
}

```

## `app/Http/Controllers/Auth/AuthenticatedSessionController.php`

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     * LoginRequest blocks accounts with is_blocked = true (FR-6).
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // FR-2: redirect by role (admin / teacher / student).
        return redirect()->intended(route($request->user()->dashboardRoute(), absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}

```

## `app/Http/Controllers/Auth/PasswordResetLinkController.php`

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status == Password::RESET_LINK_SENT
                    ? back()->with('status', __($status))
                    : back()->withInput($request->only('email'))
                        ->withErrors(['email' => __($status)]);
    }
}

```

## `app/Http/Controllers/Auth/NewPasswordController.php`

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        return $status == Password::PASSWORD_RESET
                    ? redirect()->route('login')->with('status', __($status))
                    : back()->withInput($request->only('email'))
                        ->withErrors(['email' => __($status)]);
    }
}

```

## `app/Http/Controllers/DashboardController.php`

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Sends a user to the dashboard that belongs to their role (FR-2).
 * The dashboards themselves live in AdminDashboardController,
 * TeacherDashboardController and StudentDashboardController.
 */
class DashboardController extends Controller
{
    /**
     * /dashboard -> forward the user to the dashboard of their own role.
     */
    public function index(Request $request): RedirectResponse
    {
        return redirect()->route($request->user()->dashboardRoute());
    }
}

```

## `app/Http/Controllers/AdminDashboardController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Admin dashboard (FR-2, FR-26, FR-33 chart and moderation).
 * Shows the platform totals and a 12 month "new users" chart.
 */
class AdminDashboardController extends Controller
{
    /** How many months the chart covers. */
    private const CHART_MONTHS = 12;

    public function __invoke(): View
    {
        // One query per card: cheap and easy to read.
        $stats = [
            'users' => User::count(),
            'teachers' => User::where('role', User::ROLE_TEACHER)->count(),
            'students' => User::where('role', User::ROLE_STUDENT)->count(),
            'posts' => Post::count(),
            'classes' => ClassRoom::count(),
        ];

        return view('dashboards.admin', [
            'stats' => $stats,
            'chart' => $this->newUsersPerMonth(),
            // Moderation shortcuts (FR-33): pending counter + newest five.
            'pendingReports' => Report::query()->pending()->count(),
            'recentReports' => Report::query()
                ->pending()
                ->with(['reporter:id,name', 'reportable'])
                ->latest()
                ->take(5)
                ->get(),
        ]);
    }

    /**
     * New registrations per month for the last 12 months.
     * Months without registrations are filled with 0 so the chart keeps a
     * continuous time axis.
     *
     * @return array{labels: list<string>, values: list<int>, total: int}
     */
    private function newUsersPerMonth(): array
    {
        // First month of the window: 11 months back, so today's month is included.
        $start = now()->startOfMonth()->subMonths(self::CHART_MONTHS - 1);

        // Grouping by month needs a date function, and MySQL and SQLite (used by
        // the test suite) have different names for it. Pick the right one.
        $monthExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', created_at)"
            : "DATE_FORMAT(created_at, '%Y-%m')";

        $counts = User::query()
            ->where('created_at', '>=', $start)
            ->selectRaw("{$monthExpression} as month, COUNT(*) as total")
            ->groupByRaw($monthExpression)
            ->pluck('total', 'month');

        $labels = [];
        $values = [];

        for ($i = 0; $i < self::CHART_MONTHS; $i++) {
            $month = $start->copy()->addMonths($i);

            $labels[] = $month->format('M Y');
            // Missing months default to 0.
            $values[] = (int) ($counts[$month->format('Y-m')] ?? 0);
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'total' => array_sum($values),
        ];
    }
}

```

## `app/Http/Controllers/TeacherDashboardController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\ClassEnrollment;
use App\Models\Comment;
use App\Models\Follow;
use App\Models\Like;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Teacher dashboard (FR-2, FR-29).
 * Cards with the teacher's own numbers, a paginated list of their classes and
 * a short activity stream (likes, comments and new followers).
 */
class TeacherDashboardController extends Controller
{
    /** How many activity entries the stream shows. */
    private const ACTIVITY_LIMIT = 10;

    /** Classes per page in the table. */
    private const CLASSES_PER_PAGE = 8;

    public function __invoke(Request $request): View
    {
        $teacher = $request->user();

        $mine = $teacher->classes();

        $stats = [
            'classes' => (clone $mine)->count(),
            'upcoming' => (clone $mine)->upcoming()->count(),
            // Distinct students across all of my classes (nobody counted twice).
            'students' => ClassEnrollment::query()
                ->whereIn('class_id', (clone $mine)->select('id'))
                ->distinct()
                ->count('student_id'),
            'followers' => $teacher->followers()->count(),
        ];

        // My classes, newest first, with the number of enrolled students.
        // withCount() keeps the table free of N+1 queries.
        $classes = $mine
            ->withCount('students')
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->paginate(self::CLASSES_PER_PAGE)
            ->withQueryString();

        return view('dashboards.teacher', [
            'stats' => $stats,
            'classes' => $classes,
            'activity' => $this->recentActivity($teacher),
        ]);
    }

    /**
     * The latest likes, comments and new followers that concern this teacher.
     * Each source is limited to ACTIVITY_LIMIT rows and eager loads its author,
     * then the three lists are merged and cut down to the newest entries.
     *
     * @return Collection<int, array{icon: string, color: string, text: string, excerpt: string|null, at: \Carbon\CarbonInterface|null}>
     */
    private function recentActivity(User $teacher): Collection
    {
        // Likes on my posts.
        $likes = Like::query()
            ->with(['user:id,name', 'post:id,user_id,body'])
            ->whereHas('post', fn ($query) => $query->where('user_id', $teacher->id))
            ->latest()
            ->limit(self::ACTIVITY_LIMIT)
            ->get()
            ->map(fn (Like $like) => [
                'icon' => 'bi-hand-thumbs-up',
                'color' => 'primary',
                'text' => __(':name liked your post', ['name' => $like->user?->name ?? __('Someone')]),
                'excerpt' => $like->post?->body,
                'at' => $like->created_at,
            ]);

        // Comments on my posts.
        $comments = Comment::query()
            ->with(['user:id,name', 'post:id,user_id,body'])
            ->whereHas('post', fn ($query) => $query->where('user_id', $teacher->id))
            ->latest()
            ->limit(self::ACTIVITY_LIMIT)
            ->get()
            ->map(fn (Comment $comment) => [
                'icon' => 'bi-chat',
                'color' => 'success',
                'text' => __(':name commented on your post', ['name' => $comment->user?->name ?? __('Someone')]),
                'excerpt' => $comment->body,
                'at' => $comment->created_at,
            ]);

        // People who started following me.
        $followers = Follow::query()
            ->with('follower:id,name')
            ->where('following_id', $teacher->id)
            ->latest()
            ->limit(self::ACTIVITY_LIMIT)
            ->get()
            ->map(fn (Follow $follow) => [
                'icon' => 'bi-person-plus',
                'color' => 'warning',
                'text' => __(':name started following you', ['name' => $follow->follower?->name ?? __('Someone')]),
                'excerpt' => null,
                'at' => $follow->created_at,
            ]);

        return $likes
            ->concat($comments)
            ->concat($followers)
            ->sortByDesc('at')
            ->take(self::ACTIVITY_LIMIT)
            ->values();
    }
}

```

## `app/Http/Controllers/StudentDashboardController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Follow;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Student dashboard (FR-2, FR-30).
 * Cards with the student's numbers, the upcoming enrolled classes with a Join
 * button and a list of teachers the student does not follow yet.
 */
class StudentDashboardController extends Controller
{
    /** How many upcoming classes the list shows. */
    private const UPCOMING_LIMIT = 6;

    /** How many teacher suggestions the list shows. */
    private const RECOMMENDATION_LIMIT = 3;

    public function __invoke(Request $request): View
    {
        $student = $request->user();

        $stats = [
            'classes' => $student->enrolledClasses()->count(),
            'upcoming' => $student->enrolledClasses()->upcoming()->count(),
            // Only teachers count, so the card matches what the page offers.
            'following' => $student->following()->where('role', User::ROLE_TEACHER)->count(),
        ];

        // Upcoming classes I am enrolled in, soonest first.
        // Eager loading the teacher and counting the students avoids N+1.
        $upcomingClasses = $student->enrolledClasses()
            ->upcoming()
            ->with('teacher:id,name,subject')
            ->withCount('students')
            ->limit(self::UPCOMING_LIMIT)
            ->get();

        return view('dashboards.student', [
            'stats' => $stats,
            'upcomingClasses' => $upcomingClasses,
            'recommendedTeachers' => $this->recommendedTeachers($student),
        ]);
    }

    /**
     * Teachers that this student does not follow yet, most followed first.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    private function recommendedTeachers(User $student): \Illuminate\Database\Eloquent\Collection
    {
        // Sub-query of everybody the student already follows.
        $alreadyFollowed = Follow::query()
            ->select('following_id')
            ->where('follower_id', $student->id);

        return User::query()
            ->where('role', User::ROLE_TEACHER)
            ->whereNotIn('users.id', $alreadyFollowed)
            ->withCount('followers')
            ->orderByDesc('followers_count')
            ->orderBy('name')
            ->limit(self::RECOMMENDATION_LIMIT)
            ->get();
    }
}

```

## `app/Http/Middleware/RoleMiddleware.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Only let the request through when the logged-in user has one of the given roles.
     *
     * Usage in routes/web.php:
     *   ->middleware('role:admin')
     *   ->middleware('role:teacher,admin')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Not logged in yet -> send them to the login page.
        if (! $user) {
            return redirect()->route('login');
        }

        // Wrong role -> 403 Forbidden.
        if (! in_array($user->role, $roles, true)) {
            abort(403, 'You are not allowed to open this page.');
        }

        return $next($request);
    }
}

```

## `app/Http/Middleware/EnsureUserIsNotBlocked.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kicks out a user who is blocked while they are already logged in (FR-6).
 * Registered as "not.blocked" and also appended to the whole "web" group.
 */
class EnsureUserIsNotBlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Guests are not affected: this middleware only polices logged-in users.
        if ($user && $user->is_blocked) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('error', 'Your account has been blocked by an administrator. Please contact support.');
        }

        return $next($request);
    }
}

```

## `app/Notifications/ResetPasswordNotification.php`

```php
<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Branded password reset email (FR-3).
 *
 * It extends the framework notification so the token, expiry and reset URL
 * logic stay exactly the same - only the look of the email changes.
 */
class ResetPasswordNotification extends BaseResetPassword
{
    /**
     * Build the reset email with our own Markdown template.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reset your EduConnect password')
            ->markdown('emails.reset-password', [
                'url' => $this->resetUrl($notifiable),
                'name' => $notifiable->name,
                'expires' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
            ]);
    }
}

```

## Routes

## `routes/web.php`

```php
<?php

use App\Http\Controllers\AdminClassController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminPostController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\ClassBrowseController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\ClassStatusController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\HashtagController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\LiveClassController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\TeacherDashboardController;
use App\Http\Controllers\UserProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public pages
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Admin area (FR-2, FR-5)
|--------------------------------------------------------------------------
| A student or a teacher can never open an admin page: the "role" middleware
| answers with 403. There is no admin registration either, the admin account
| comes from AdminUserSeeder.
*/
Route::middleware(['auth', 'not.blocked', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');

        // User management (FR-27).
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}/block', [AdminUserController::class, 'block'])->name('users.block');
        Route::patch('/users/{user}/unblock', [AdminUserController::class, 'unblock'])->name('users.unblock');
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

        // Post management (FR-28).
        Route::get('/posts', [AdminPostController::class, 'index'])->name('posts.index');
        Route::patch('/posts/{post}/hide', [AdminPostController::class, 'toggleHidden'])->name('posts.hide');
        Route::delete('/posts/{post}', [AdminPostController::class, 'destroy'])->name('posts.destroy');

        // Class management (FR-28).
        Route::get('/classes', [AdminClassController::class, 'index'])->name('classes.index');
        Route::patch('/classes/{classRoom}/hide', [AdminClassController::class, 'toggleHidden'])->name('classes.hide');
        Route::delete('/classes/{classRoom}', [AdminClassController::class, 'destroy'])->name('classes.destroy');

        // Moderation queue (FR-33, FR-34).
        Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/{report}', [AdminReportController::class, 'show'])->name('reports.show');
        Route::patch('/reports/{report}/hide', [AdminReportController::class, 'hide'])->name('reports.hide');
        Route::patch('/reports/{report}/unhide', [AdminReportController::class, 'unhide'])->name('reports.unhide');
        Route::patch('/reports/{report}/reviewed', [AdminReportController::class, 'markReviewed'])->name('reports.reviewed');
        Route::patch('/reports/{report}/dismiss', [AdminReportController::class, 'dismiss'])->name('reports.dismiss');
        Route::delete('/reports/{report}/content', [AdminReportController::class, 'destroyContent'])->name('reports.content.destroy');
        Route::post('/reports/{report}/block-user', [AdminReportController::class, 'blockUser'])->name('reports.block-user');
    });

/*
|--------------------------------------------------------------------------
| Teacher area (FR-2)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'not.blocked', 'role:teacher'])
    ->prefix('teacher')
    ->name('teacher.')
    ->group(function () {
        Route::get('/dashboard', TeacherDashboardController::class)->name('dashboard');
    });

/*
|--------------------------------------------------------------------------
| Student area (FR-2, FR-17, FR-30)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'not.blocked', 'role:student'])
    ->prefix('student')
    ->name('student.')
    ->group(function () {
        Route::get('/dashboard', StudentDashboardController::class)->name('dashboard');
    });

// Follow / unfollow another member from a profile or the search page
// (FR-11). Students and teachers can follow each other; admin accounts
// are not followable and admins do not use the follow routes.
// Throttled so the button cannot be used to hammer the database.
Route::middleware(['auth', 'not.blocked', 'role:student,teacher'])
    ->group(function () {
        Route::post('/u/{user}/follow', [FollowController::class, 'store'])
            ->middleware('throttle:30,1')
            ->name('follows.store');

        Route::delete('/u/{user}/follow', [FollowController::class, 'destroy'])
            ->middleware('throttle:30,1')
            ->name('follows.destroy');
    });

/*
|--------------------------------------------------------------------------
| Social feed (FR-7 ... FR-14)
|--------------------------------------------------------------------------
| Every logged in role uses the same feed: admins, teachers and students.
*/
Route::middleware(['auth', 'not.blocked'])->group(function () {
    Route::get('/feed', [PostController::class, 'index'])->name('feed.index');

    // Posts. /posts/create must be declared before /posts/{post}.
    Route::get('/posts/create', [PostController::class, 'create'])->name('posts.create');
    // At most 10 new posts per minute per user (FR-9).
    Route::post('/posts', [PostController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('posts.store');
    Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');
    Route::get('/posts/{post}/edit', [PostController::class, 'edit'])->name('posts.edit');
    Route::patch('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');

    // Likes - small JSON endpoints for the fetch() calls in the feed (FR-11).
    Route::post('/posts/{post}/like', [LikeController::class, 'store'])
        ->middleware('throttle:60,1')
        ->name('posts.like');
    Route::delete('/posts/{post}/like', [LikeController::class, 'destroy'])
        ->middleware('throttle:60,1')
        ->name('posts.unlike');

    // Comments (FR-13).
    Route::post('/posts/{post}/comments', [CommentController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('comments.store');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])
        ->middleware('throttle:30,1')
        ->name('comments.destroy');

    // Reporting a post or comment (FR-32). Throttled so the queue cannot be
    // flooded by one account.
    Route::post('/posts/{post}/reports', [ReportController::class, 'storePost'])
        ->middleware('throttle:10,1')
        ->name('reports.posts.store');
    Route::post('/comments/{comment}/reports', [ReportController::class, 'storeComment'])
        ->middleware('throttle:10,1')
        ->name('reports.comments.store');

    // Hashtag pages (FR-15). The binding uses the (lower case) name column.
    Route::get('/hashtag/{hashtag:name}', [HashtagController::class, 'show'])->name('hashtags.show');

    // Public profiles and the follower lists (FR-5, FR-11).
    Route::get('/u/{user}', [UserProfileController::class, 'show'])->name('users.show');
    Route::get('/u/{user}/followers', [UserProfileController::class, 'followers'])->name('users.followers');
    Route::get('/u/{user}/following', [UserProfileController::class, 'following'])->name('users.following');

    // "My followers" in the sidebar jumps to my own list.
    Route::get('/followers', fn () => redirect()->route('users.followers', auth()->id()))
        ->name('followers.index');
});

// /dashboard simply forwards everybody to the dashboard of their own role.
Route::middleware(['auth', 'not.blocked'])
    ->get('/dashboard', [DashboardController::class, 'index'])
    ->name('dashboard');

/*
|--------------------------------------------------------------------------
| Live classes (FR-18 ... FR-24)
|--------------------------------------------------------------------------
| A teacher manages their own classes, a student browses them, enrolls and
| joins. /classes/create must be declared before /classes/{classRoom}, and
| /classes/discover before the wildcard as well, otherwise "discover" would be
| taken for a class id.
*/

// Teacher only: create, edit, delete and manage a class (ClassPolicy checks
// that the class really belongs to the teacher).
Route::middleware(['auth', 'not.blocked', 'role:teacher'])->group(function () {
    Route::get('/classes', [ClassController::class, 'index'])->name('classes.index');
    Route::get('/classes/create', [ClassController::class, 'create'])->name('classes.create');
    Route::post('/classes', [ClassController::class, 'store'])->name('classes.store');
    Route::get('/classes/{classRoom}/edit', [ClassController::class, 'edit'])->name('classes.edit');
    Route::patch('/classes/{classRoom}', [ClassController::class, 'update'])->name('classes.update');
    Route::delete('/classes/{classRoom}', [ClassController::class, 'destroy'])->name('classes.destroy');

    // Who is enrolled (FR-24) and the scheduled / live / completed switch.
    Route::get('/classes/{classRoom}/students', [ClassController::class, 'students'])->name('classes.students');
    Route::patch('/classes/{classRoom}/status', [ClassStatusController::class, 'update'])->name('classes.status');
});

// Every logged in role: the class detail page and the Jitsi room (FR-20).
Route::middleware(['auth', 'not.blocked'])->group(function () {
    // Find classes: search + subject filter + pagination (FR-19).
    Route::get('/classes/discover', [ClassBrowseController::class, 'index'])->name('classes.discover');
    Route::get('/classes/{classRoom}', [ClassBrowseController::class, 'show'])->name('classes.show');

    // FR-18 / FR-20 / FR-22: the embedded Jitsi Meet room.
    Route::get('/classes/{classRoom}/room', LiveClassController::class)->name('classes.room');
});

// Student only: enroll / unenroll and "my enrolled classes" (FR-21).
Route::middleware(['auth', 'not.blocked', 'role:student'])->group(function () {
    Route::post('/classes/{classRoom}/enroll', [EnrollmentController::class, 'store'])->name('classes.enroll');
    Route::delete('/classes/{classRoom}/enroll', [EnrollmentController::class, 'destroy'])->name('classes.unenroll');

    Route::get('/student/classes', [ClassBrowseController::class, 'enrolled'])->name('student.classes');
});

/*
|--------------------------------------------------------------------------
| Notifications (FR-23 ... FR-25)
|--------------------------------------------------------------------------
| The bell in the navbar polls /notifications/poll every 45 seconds and gets
| the fresh unread counter plus the newest five entries as JSON.
*/
Route::middleware(['auth', 'not.blocked'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');

    // Lightweight JSON endpoint for the polling script (FR-24).
    Route::get('/notifications/poll', [NotificationController::class, 'poll'])
        ->middleware('throttle:60,1')
        ->name('notifications.poll');

    // Mark everything as read (FR-25). Declared before the {notification} routes.
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

    // One notification: mark it read, or mark it read and open its page (FR-25).
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::get('/notifications/{notification}/open', [NotificationController::class, 'open'])->name('notifications.open');
});

/*
|--------------------------------------------------------------------------
| Search (FR-14)
|--------------------------------------------------------------------------
| One box in the navbar, two tabs on the page: people and hashtags.
*/
Route::middleware(['auth', 'not.blocked'])->group(function () {
    Route::get('/search', [SearchController::class, 'index'])->name('search.index');
});

/*
|--------------------------------------------------------------------------
| Profile (FR-4)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'not.blocked'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

```

## `routes/auth.php`

```php
<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    // Rate limited: max 5 new accounts per minute from one IP.
    Route::post('register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:5,1');

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    // The login attempt itself is rate limited inside LoginRequest (5 tries).
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    // Rate limited so nobody can spam reset emails (FR-3).
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});

```

## Layouts and Blade components

## `resources/views/layouts/guest.blade.php`

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name', 'EduConnect') }}</title>

        {{-- Bootstrap 5 + Bootstrap Icons compiled by Vite. --}}
        @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    </head>
    <body class="edu-auth-body">
        <main class="min-vh-100">
            <div class="row g-0 min-vh-100">
                {{-- Branded panel: desktop and tablet only. --}}
                <section class="col-lg-6 d-none d-lg-flex edu-auth-brand p-5 flex-column justify-content-between"
                         aria-label="{{ __('About EduConnect') }}">
                    <a href="{{ url('/') }}" class="d-inline-flex align-items-center gap-2 text-white text-decoration-none">
                        <span class="fs-2" aria-hidden="true">
                            <x-application-logo class="text-white" />
                        </span>
                        <span class="h4 fw-bold mb-0">{{ config('app.name', 'EduConnect') }}</span>
                    </a>

                    <div class="my-5">
                        <h1 class="display-6 fw-bold mb-3">{{ __('Learn together, wherever you are.') }}</h1>
                        <p class="lead text-white-50 mb-4">
                            {{ __('Live classes, a friendly social feed and the tools teachers need to keep every student close.') }}
                        </p>

                        <ul class="list-unstyled d-grid gap-3 mb-0">
                            <li class="d-flex gap-3">
                                <span class="edu-auth-icon" aria-hidden="true"><i class="bi bi-camera-video"></i></span>
                                <span>
                                    <strong class="d-block">{{ __('Live video classes') }}</strong>
                                    <span class="small text-white-50">{{ __('Join a class from any browser, no extra app required.') }}</span>
                                </span>
                            </li>
                            <li class="d-flex gap-3">
                                <span class="edu-auth-icon" aria-hidden="true"><i class="bi bi-people"></i></span>
                                <span>
                                    <strong class="d-block">{{ __('A real school community') }}</strong>
                                    <span class="small text-white-50">{{ __('Follow teachers, share posts and learn together.') }}</span>
                                </span>
                            </li>
                            <li class="d-flex gap-3">
                                <span class="edu-auth-icon" aria-hidden="true"><i class="bi bi-shield-check"></i></span>
                                <span>
                                    <strong class="d-block">{{ __('Safe by design') }}</strong>
                                    <span class="small text-white-50">{{ __('Reporting and moderation tools protect every learner.') }}</span>
                                </span>
                            </li>
                        </ul>
                    </div>

                    <p class="small text-white-50 mb-0">
                        &copy; {{ date('Y') }} {{ config('app.name', 'EduConnect') }}
                    </p>
                </section>

                {{-- Form panel: full width on phones, half width on desktop. --}}
                <section class="col-12 col-lg-6 d-flex align-items-center justify-content-center p-4 p-md-5">
                    <div class="w-100 edu-auth-form">
                        {{-- Compact brand for phones and small tablets. --}}
                        <div class="d-lg-none text-center mb-4">
                            <a href="{{ url('/') }}" class="d-inline-flex align-items-center gap-2 text-decoration-none">
                                <span class="fs-2 text-primary" aria-hidden="true">
                                    <x-application-logo />
                                </span>
                                <span class="h4 fw-bold text-dark mb-0">{{ config('app.name', 'EduConnect') }}</span>
                            </a>
                            <p class="text-muted small mb-0 mt-1">{{ __('Learn together, anywhere.') }}</p>
                        </div>

                        <div class="card border-0 shadow-sm edu-auth-card">
                            <div class="card-body p-4 p-md-5">
                                {{-- Flash messages and the validation summary. --}}
                                <x-flash />

                                {{ $slot }}
                            </div>
                        </div>

                        <p class="text-center text-muted small mt-4 mb-0">
                            {{ __('Learn together, anywhere.') }}
                        </p>
                    </div>
                </section>
            </div>
        </main>
    </body>
</html>

```

## `resources/views/layouts/app.blade.php`

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name', 'EduConnect') }}</title>

        @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    </head>
    {{-- body carries the notification polling URL for logged in users (FR-24) --}}
    <body class="d-flex flex-column min-vh-100" @auth data-notification-url="{{ route('notifications.poll') }}" @endauth>
        {{-- Top navigation bar --}}
        @include('layouts.navigation')

        <div class="flex-grow-1 d-lg-flex">
            {{-- Role based sidebar: a column on desktop, an offcanvas drawer on phones. --}}
            @auth
                @include('layouts.sidebar')
            @endauth

            <main class="flex-grow-1 py-4">
                <div class="container">
                    {{-- Success / error / validation messages --}}
                    <x-flash />

                    {{-- Optional page header --}}
                    @isset($header)
                        <div class="mb-4">{{ $header }}</div>
                    @endisset
                </div>

                {{ $slot }}
            </main>
        </div>

        <footer class="py-4 mt-auto border-top bg-white">
            <div class="container text-center text-muted small">
                &copy; {{ date('Y') }} {{ config('app.name', 'EduConnect') }} &middot; Learn together, anywhere.
            </div>
        </footer>
    </body>
</html>

```

## `resources/views/layouts/navigation.blade.php`

```blade
{{-- Top navigation bar: menu toggle (mobile), brand, search, notifications, user menu. --}}
@php
    // Unread notifications drive the bell counter (FR-24); the newest five fill
    // the dropdown. The polling script refreshes both every 45 seconds.
    $unreadNotifications = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;
    $latestNotifications = auth()->check()
        ? auth()->user()->notifications()->latest()->take(5)->get()
        : collect();
@endphp

<nav class="navbar navbar-dark bg-dark sticky-top">
    <div class="container-fluid px-3 px-lg-4">
        @auth
            {{-- Opens the sidebar drawer on phones and tablets only;
                 from lg upwards the sidebar is always visible. --}}
            <button class="btn btn-outline-light btn-sm me-2 d-lg-none" type="button"
                    data-bs-toggle="offcanvas" data-bs-target="#appSidebar"
                    aria-controls="appSidebar" aria-label="{{ __('Open the menu') }}">
                <i class="bi bi-list fs-5"></i>
            </button>
        @endauth

        <a class="navbar-brand fw-bold d-flex align-items-center gap-2"
           href="{{ auth()->check() ? route('dashboard') : url('/') }}">
            <x-application-logo class="text-white" />
            <span class="d-none d-sm-inline">{{ config('app.name', 'EduConnect') }}</span>
        </a>

        <div class="d-flex align-items-center ms-auto gap-2">
            @auth
                {{-- One search box for people and hashtags (FR-14).
                     On phones the box would not fit, so there is an icon instead. --}}
                <form method="GET" action="{{ route('search.index') }}" role="search" class="edu-search d-none d-md-block">
                    <div class="input-group input-group-sm">
                        <input type="search" name="q" class="form-control border-0"
                               value="{{ request()->routeIs('search.*') ? request('q') : '' }}"
                               placeholder="{{ __('Search people or #tags') }}"
                               aria-label="{{ __('Search') }}">
                        <button class="btn btn-light" type="submit" aria-label="{{ __('Search') }}">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>
            @endauth

            <ul class="navbar-nav flex-row align-items-center gap-1">
            @auth
                {{-- Phone shortcut to the search page --}}
                <li class="nav-item d-md-none">
                    <a class="btn btn-outline-light btn-sm" href="{{ route('search.index') }}"
                       aria-label="{{ __('Search') }}">
                        <i class="bi bi-search"></i>
                    </a>
                </li>

                {{-- Notification bell: unread counter + newest five (FR-24) --}}
                <li class="nav-item dropdown">
                    <button class="btn btn-outline-light btn-sm position-relative" type="button"
                            data-bs-toggle="dropdown" aria-expanded="false"
                            aria-label="{{ __('Notifications') }}" data-notification-toggle>
                        <i class="bi bi-bell"></i>
                        {{-- The badge is refreshed by resources/js/notifications.js. --}}
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill text-bg-danger {{ $unreadNotifications > 0 ? '' : 'd-none' }}"
                              data-notification-badge>
                            {{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end notification-menu" data-notification-list>
                        @include('notifications._dropdown', ['notifications' => $latestNotifications])
                    </ul>
                </li>

                {{-- User dropdown: profile + logout --}}
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        @if (auth()->user()->photoUrl())
                            {{-- Escaped attribute value; the photo lives on the public disk. --}}
                            <img src="{{ auth()->user()->photoUrl() }}" alt="{{ auth()->user()->name }}"
                                 class="avatar me-2">
                        @else
                            <i class="bi bi-person-circle fs-5 me-1"></i>
                        @endif
                        <span class="d-none d-md-inline">{{ auth()->user()->name }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <span class="dropdown-item-text small text-muted">
                                {{ __('Signed in as') }}
                                <span class="text-capitalize fw-semibold">{{ auth()->user()->role }}</span>
                            </span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="{{ route('users.show', auth()->id()) }}">
                                <i class="bi bi-person me-1"></i> {{ __('My public profile') }}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('feed.index') }}">
                                <i class="bi bi-chat-square-text me-1"></i> {{ __('Feed') }}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('notifications.index') }}">
                                <i class="bi bi-bell me-1"></i> {{ __('Notifications') }}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                <i class="bi bi-gear me-1"></i> {{ __('Profile settings') }}
                            </a>
                        </li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right me-1"></i> {{ __('Log out') }}
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            @else
                {{-- Guests see login / register --}}
                <li class="nav-item">
                    <a class="btn btn-outline-light btn-sm" href="{{ route('login') }}">{{ __('Log in') }}</a>
                </li>
                <li class="nav-item">
                    <a class="btn btn-light btn-sm" href="{{ route('register') }}">{{ __('Register') }}</a>
                </li>
            @endauth
            </ul>
        </div>
    </div>
</nav>

```

## `resources/views/layouts/sidebar.blade.php`

```blade
{{-- Role based sidebar.
     From lg upwards it is a fixed column; on phones it is an offcanvas drawer
     opened with the hamburger button in the navbar. Items whose page does not
     exist yet are rendered as disabled "soon" entries, so the menu never
     points at a missing route. --}}
@php
    $role = auth()->user()->role;

    $menu = match ($role) {
        \App\Models\User::ROLE_ADMIN => [
            ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'route' => 'admin.dashboard'],
            ['label' => 'Users', 'icon' => 'bi-people', 'route' => 'admin.users.index'],
            ['label' => 'Posts', 'icon' => 'bi-chat-square-text', 'route' => 'admin.posts.index'],
            ['label' => 'Classes', 'icon' => 'bi-easel', 'route' => 'admin.classes.index'],
            ['label' => 'Reports', 'icon' => 'bi-flag', 'route' => 'admin.reports.index'],
        ],
        \App\Models\User::ROLE_TEACHER => [
            ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'route' => 'teacher.dashboard'],
            ['label' => 'My classes', 'icon' => 'bi-easel', 'route' => 'classes.index'],
            ['label' => 'New class', 'icon' => 'bi-plus-square', 'route' => 'classes.create'],
            ['label' => 'Feed', 'icon' => 'bi-chat-square-text', 'route' => 'feed.index'],
            ['label' => 'My followers', 'icon' => 'bi-people', 'route' => 'followers.index'],
        ],
        default => [
            ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'route' => 'student.dashboard'],
            ['label' => 'My classes', 'icon' => 'bi-journal-bookmark', 'route' => 'student.classes'],
            ['label' => 'Find classes', 'icon' => 'bi-search', 'route' => 'classes.discover'],
            ['label' => 'Feed', 'icon' => 'bi-chat-square-text', 'route' => 'feed.index'],
            ['label' => 'Teachers', 'icon' => 'bi-person-video3', 'route' => 'search.index'],
        ],
    };
@endphp

<aside class="offcanvas-lg offcanvas-start edu-sidebar flex-shrink-0" tabindex="-1" id="appSidebar"
       aria-labelledby="appSidebarLabel">
    {{-- Header is only needed in the mobile drawer --}}
    <div class="offcanvas-header border-bottom d-lg-none">
        <h2 class="offcanvas-title h6 mb-0" id="appSidebarLabel">
            <i class="bi bi-mortarboard-fill text-edu me-1"></i> {{ __('Menu') }}
        </h2>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ __('Close') }}"></button>
    </div>

    <div class="offcanvas-body d-flex flex-column p-3">
        {{-- Main menu for the current role --}}
        <ul class="nav nav-pills flex-column gap-1">
            @foreach ($menu as $item)
                <li class="nav-item">
                    @if (Route::has($item['route']))
                        <a class="nav-link d-flex align-items-center {{ request()->routeIs($item['route']) ? 'active' : '' }}"
                           href="{{ route($item['route']) }}"
                           @if (request()->routeIs($item['route'])) aria-current="page" @endif>
                            <i class="bi {{ $item['icon'] }} me-2"></i> {{ __($item['label']) }}
                        </a>
                    @else
                        <span class="nav-link disabled d-flex align-items-center justify-content-between">
                            <span><i class="bi {{ $item['icon'] }} me-2"></i> {{ __($item['label']) }}</span>
                            <span class="badge text-bg-secondary">{{ __('soon') }}</span>
                        </span>
                    @endif
                </li>
            @endforeach
        </ul>

        {{-- Account links, pushed to the bottom of the column --}}
        <ul class="nav flex-column gap-1 mt-auto pt-3 border-top">
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center {{ request()->routeIs('profile.*') ? 'active' : '' }}"
                   href="{{ route('profile.edit') }}">
                    <i class="bi bi-person-gear me-2"></i> {{ __('Profile settings') }}
                </a>
            </li>
            <li class="nav-item">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="nav-link text-danger w-100 text-start">
                        <i class="bi bi-box-arrow-right me-2"></i> {{ __('Log out') }}
                    </button>
                </form>
            </li>
        </ul>
    </div>
</aside>

```

## `resources/views/components/password-input.blade.php`

```blade
{{--
    Password field with an accessible show/hide toggle (FR-1, FR-2, FR-3).
    The toggle is wired by resources/js/auth.js, so it also works without Alpine.
    Usage: <x-password-input name="password" :label="__('Password')" />
--}}
@props([
    'name',
    'label',
    'autocomplete' => 'current-password',
    'required' => true,
    'autofocus' => false,
])

<div class="mb-3">
    <x-input-label :for="$name" :value="$label" />

    <div class="input-group">
        <input id="{{ $name }}"
               name="{{ $name }}"
               type="password"
               @if ($required) required @endif
               @if ($autofocus) autofocus @endif
               autocomplete="{{ $autocomplete }}"
               @class(['form-control', 'is-invalid' => $errors->has($name)])
               {{ $attributes }}>

        <button type="button"
                class="btn btn-outline-secondary password-toggle"
                data-password-toggle="{{ $name }}"
                data-show-label="{{ __('Show password') }}"
                data-hide-label="{{ __('Hide password') }}"
                aria-label="{{ __('Show password') }}"
                aria-pressed="false">
            <i class="bi bi-eye" aria-hidden="true"></i>
        </button>
    </div>

    <x-input-error :messages="$errors->get($name)" class="mt-1" />
</div>

```

## `resources/views/components/input-label.blade.php`

```blade
@props(['value'])

<label {{ $attributes->merge(['class' => 'form-label fw-semibold small']) }}>
    {{ $value ?? $slot }}
</label>

```

## `resources/views/components/text-input.blade.php`

```blade
@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'form-control']) }}>

```

## `resources/views/components/input-error.blade.php`

```blade
@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'text-danger small list-unstyled mb-0']) }}>
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif

```

## `resources/views/components/primary-button.blade.php`

```blade
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn btn-primary']) }}>
    {{ $slot }}
</button>

```

## `resources/views/components/flash.blade.php`

```blade
{{-- Flash + validation summary messages. Used by every layout via <x-flash /> --}}

@if (session('success'))
    {{-- Green banner after a successful action --}}
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('error'))
    {{-- Red banner for a refused action --}}
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('status'))
    {{-- Neutral informational banner (used by Breeze for "verify your email") --}}
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="bi bi-info-circle me-1"></i> {{ session('status') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@php($bag = $errors ?? null)
@if ($bag && $bag->any())
    {{-- Summary of every validation error of the last request.
         $errors is only shared by the "web" middleware group, so it is missing
         on error pages such as 404 - hence the null-safe check above. --}}
    <div class="alert alert-danger" role="alert">
        <div class="fw-semibold mb-1">
            <i class="bi bi-exclamation-triangle me-1"></i> {{ __('Please fix the following:') }}
        </div>
        <ul class="mb-0 small">
            @foreach ($bag->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

```

## `resources/views/components/application-logo.blade.php`

```blade
{{-- EduConnect logo: a graduation cap icon --}}
<span {{ $attributes->merge(['class' => 'text-primary']) }}>
    <i class="bi bi-mortarboard-fill"></i>
</span>

```

## `resources/views/components/auth-session-status.blade.php`

```blade
@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'alert alert-success py-2 small']) }}>
        {{ $status }}
    </div>
@endif

```

## Auth views

## `resources/views/auth/login.blade.php`

```blade
<x-guest-layout>
    <h1 class="h4 fw-bold mb-1">{{ __('Welcome back') }}</h1>
    <p class="text-muted small mb-4">{{ __('Log in to reach your classes and feed.') }}</p>

    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf

        <!-- Email -->
        <div class="mb-3">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')"
                          :class="$errors->has('email') ? 'is-invalid' : ''"
                          required autofocus autocomplete="username"
                          placeholder="you@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <!-- Password with show/hide toggle -->
        <x-password-input name="password" :label="__('Password')" autocomplete="current-password" />

        <!-- Remember me -->
        <div class="form-check mb-4">
            <input id="remember_me" type="checkbox" class="form-check-input" name="remember"
                   @checked(old('remember'))>
            <label for="remember_me" class="form-check-label small">{{ __('Remember me') }}</label>
        </div>

        <x-primary-button class="w-100 justify-content-center">
            <i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i>
            {{ __('Log in') }}
        </x-primary-button>

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-4">
            @if (Route::has('password.request'))
                <a class="small text-decoration-none" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            @if (Route::has('register'))
                <a class="small text-decoration-none" href="{{ route('register') }}">
                    {{ __('Create an account') }}
                </a>
            @endif
        </div>
    </form>
</x-guest-layout>

```

## `resources/views/auth/register.blade.php`

```blade
<x-guest-layout>
    @php
        $selectedRole = in_array(old('role'), ['teacher', 'student'], true) ? old('role') : 'student';
    @endphp

    <h1 class="h4 fw-bold mb-1">{{ __('Create your account') }}</h1>
    <p class="text-muted small mb-4">{{ __('Join as a teacher or as a student.') }}</p>

    <form method="POST" action="{{ route('register') }}" novalidate>
        @csrf

        <!-- Name -->
        <div class="mb-3">
            <x-input-label for="name" :value="__('Full name')" />
            <x-text-input id="name" type="text" name="name" :value="old('name')"
                          :class="$errors->has('name') ? 'is-invalid' : ''"
                          required autofocus autocomplete="name"
                          placeholder="Maria Rossi" />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>

        <!-- Email -->
        <div class="mb-3">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')"
                          :class="$errors->has('email') ? 'is-invalid' : ''"
                          required autocomplete="username"
                          placeholder="you@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <!-- Role: only Teacher or Student can ever be posted. -->
        <div class="mb-3">
            <x-input-label for="role" :value="__('I am a')" />
            <select id="role" name="role" data-role-select
                    class="form-select {{ $errors->has('role') ? 'is-invalid' : '' }}" required>
                <option value="student" @selected($selectedRole === 'student')>{{ __('Student') }}</option>
                <option value="teacher" @selected($selectedRole === 'teacher')>{{ __('Teacher') }}</option>
            </select>
            <div class="form-text">{{ __('Admin accounts are created by the platform.') }}</div>
            <x-input-error :messages="$errors->get('role')" class="mt-1" />
        </div>

        <!-- Optional subject, revealed for teachers by auth.js. -->
        <div class="mb-3" data-subject-field @if ($selectedRole !== 'teacher') hidden @endif>
            <x-input-label for="subject" :value="__('Subject you teach (optional)')" />
            <x-text-input id="subject" type="text" name="subject" :value="old('subject')"
                          placeholder="{{ __('e.g. Mathematics') }}"
                          maxlength="100"
                          :class="$errors->has('subject') ? 'is-invalid' : ''" />
            <x-input-error :messages="$errors->get('subject')" class="mt-1" />
        </div>

        <!-- Password with show/hide toggle -->
        <x-password-input name="password" :label="__('Password')" autocomplete="new-password" />

        <!-- Confirm password -->
        <x-password-input name="password_confirmation" :label="__('Confirm password')" autocomplete="new-password" />

        <p class="form-text mt-0 mb-3">{{ __('Use at least 8 characters.') }}</p>

        <x-primary-button class="w-100 justify-content-center">
            <i class="bi bi-person-plus me-1" aria-hidden="true"></i>
            {{ __('Register') }}
        </x-primary-button>

        <p class="text-center small mt-4 mb-0">
            {{ __('Already registered?') }}
            <a class="text-decoration-none" href="{{ route('login') }}">{{ __('Log in') }}</a>
        </p>
    </form>
</x-guest-layout>

```

## `resources/views/auth/forgot-password.blade.php`

```blade
<x-guest-layout>
    <h1 class="h4 fw-bold mb-1">{{ __('Forgot your password?') }}</h1>
    <p class="text-muted small mb-4">
        {{ __('No problem. Tell us your email address and we will send you a link to choose a new password.') }}
    </p>

    <form method="POST" action="{{ route('password.email') }}" novalidate>
        @csrf

        <div class="mb-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')"
                          :class="$errors->has('email') ? 'is-invalid' : ''"
                          required autofocus autocomplete="username"
                          placeholder="you@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <x-primary-button class="w-100 justify-content-center">
            <i class="bi bi-envelope me-1" aria-hidden="true"></i>
            {{ __('Email password reset link') }}
        </x-primary-button>

        <p class="text-center small mt-4 mb-0">
            <a class="text-decoration-none" href="{{ route('login') }}">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
                {{ __('Back to login') }}
            </a>
        </p>
    </form>
</x-guest-layout>

```

## `resources/views/auth/reset-password.blade.php`

```blade
<x-guest-layout>
    <h1 class="h4 fw-bold mb-1">{{ __('Choose a new password') }}</h1>
    <p class="text-muted small mb-4">{{ __('Pick something long and hard to guess.') }}</p>

    <form method="POST" action="{{ route('password.store') }}" novalidate>
        @csrf

        <!-- Reset token (comes from the emailed link) -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="mb-3">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email', $request->email)"
                          :class="$errors->has('email') ? 'is-invalid' : ''"
                          required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <x-password-input name="password" :label="__('New password')" autocomplete="new-password" />

        <x-password-input name="password_confirmation" :label="__('Confirm new password')" autocomplete="new-password" />

        <p class="form-text mt-0 mb-4">{{ __('Use at least 8 characters.') }}</p>

        <x-primary-button class="w-100 justify-content-center">
            <i class="bi bi-key me-1" aria-hidden="true"></i>
            {{ __('Reset password') }}
        </x-primary-button>

        <p class="text-center small mt-4 mb-0">
            <a class="text-decoration-none" href="{{ route('login') }}">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
                {{ __('Back to login') }}
            </a>
        </p>
    </form>
</x-guest-layout>

```

## `resources/views/auth/confirm-password.blade.php`

```blade
<x-guest-layout>
    <h1 class="h4 fw-bold mb-1">{{ __('Confirm your password') }}</h1>
    <p class="text-muted small mb-4">
        {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
    </p>

    <form method="POST" action="{{ route('password.confirm') }}" novalidate>
        @csrf

        <x-password-input name="password" :label="__('Password')" autocomplete="current-password" autofocus />

        <x-primary-button class="w-100 justify-content-center">
            <i class="bi bi-shield-check me-1" aria-hidden="true"></i>
            {{ __('Confirm') }}
        </x-primary-button>
    </form>
</x-guest-layout>

```

## `resources/views/auth/verify-email.blade.php`

```blade
<x-guest-layout>
    <div class="text-center mb-3">
        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary p-3 fs-4"
              aria-hidden="true">
            <i class="bi bi-envelope-check"></i>
        </span>
    </div>

    <h1 class="h4 fw-bold mb-1 text-center">{{ __('Verify your email') }}</h1>
    <p class="text-muted small mb-4 text-center">
        {{ __('Thanks for signing up! Please click the link we just emailed you to verify your address.') }}
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success py-2 small" role="status">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <x-primary-button class="w-100 justify-content-center">
            <i class="bi bi-send me-1" aria-hidden="true"></i>
            {{ __('Resend verification email') }}
        </x-primary-button>
    </form>

    <p class="text-center small mt-4 mb-0">
        {{ __('Wrong account?') }}
    </p>

    <form method="POST" action="{{ route('logout') }}" class="text-center">
        @csrf
        <button type="submit" class="btn btn-link btn-sm p-0 text-decoration-none">
            {{ __('Log out') }}
        </button>
    </form>
</x-guest-layout>

```

## Dashboard views

## `resources/views/dashboards/admin.blade.php`

```blade
<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h1 class="h5 fw-semibold mb-0">
                <i class="bi bi-shield-lock me-1"></i> {{ __('Admin dashboard') }}
            </h1>
            <span class="badge text-bg-danger text-capitalize">{{ auth()->user()->role }}</span>
        </div>
    </x-slot>

    <div class="container">
        {{-- Platform totals (FR-7) --}}
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-xl">
                <x-stat-card :label="__('Total users')" :value="$stats['users']" icon="bi-people" color="primary" />
            </div>
            <div class="col-12 col-sm-6 col-xl">
                <x-stat-card :label="__('Teachers')" :value="$stats['teachers']" icon="bi-person-video3" color="success" />
            </div>
            <div class="col-12 col-sm-6 col-xl">
                <x-stat-card :label="__('Students')" :value="$stats['students']" icon="bi-mortarboard" color="info" />
            </div>
            <div class="col-12 col-sm-6 col-xl">
                <x-stat-card :label="__('Posts')" :value="$stats['posts']" icon="bi-chat-square-text" color="warning" />
            </div>
            <div class="col-12 col-sm-6 col-xl">
                <x-stat-card :label="__('Classes')" :value="$stats['classes']" icon="bi-easel" color="danger" />
            </div>
            <div class="col-12 col-sm-6 col-xl">
                {{-- Moderation shortcut (FR-33). --}}
                <a href="{{ route('admin.reports.index', ['status' => 'pending']) }}"
                   class="text-decoration-none">
                    <x-stat-card :label="__('Pending reports')" :value="$pendingReports" icon="bi-flag" color="danger" />
                </a>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-lg-8">
                {{-- New registrations per month (FR-26) --}}
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h2 class="h6 fw-bold mb-0">{{ __('New users per month') }}</h2>
                            <div class="small text-muted">{{ __('The last 12 months') }}</div>
                        </div>
                        <span class="badge bg-light text-dark border">
                            {{ trans_choice(':count new user|:count new users', $chart['total'], ['count' => $chart['total']]) }}
                        </span>
                    </div>
                    <div class="card-body">
                        @php
                            // Chart.js configuration. app.js renders every canvas that
                            // carries a data-chart attribute, so no inline JS is needed.
                            $chartConfig = [
                                'type' => 'bar',
                                'data' => [
                                    'labels' => $chart['labels'],
                                    'datasets' => [[
                                        'label' => __('New users'),
                                        'data' => $chart['values'],
                                        'backgroundColor' => 'rgba(79, 70, 229, 0.65)',
                                        'borderColor' => '#4f46e5',
                                        'borderWidth' => 1,
                                        'borderRadius' => 6,
                                    ]],
                                ],
                                'options' => [
                                    'responsive' => true,
                                    'maintainAspectRatio' => false,
                                    'plugins' => ['legend' => ['display' => false]],
                                    'scales' => [
                                        'y' => [
                                            'beginAtZero' => true,
                                            // New users are whole people.
                                            'ticks' => ['precision' => 0],
                                        ],
                                    ],
                                ],
                            ];
                        @endphp

                        <div class="edu-chart">
                            {{-- Blade escapes the quotes as &quot;, the browser turns them
                                 back into real quotes before JSON.parse runs. --}}
                            <canvas data-chart="{{ json_encode($chartConfig) }}"
                                    aria-label="{{ __('New users per month') }}" role="img"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                {{-- Newest pending reports with a quick link (FR-33). --}}
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h2 class="h6 fw-bold mb-0">{{ __('Pending reports') }}</h2>
                        <a href="{{ route('admin.reports.index', ['status' => 'pending']) }}"
                           class="small text-decoration-none">{{ __('See all') }}</a>
                    </div>
                    <div class="list-group list-group-flush">
                        @forelse ($recentReports as $report)
                            <a href="{{ route('admin.reports.show', $report) }}"
                               class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div class="small">
                                        <span class="badge bg-light text-dark border">{{ $report->content_type }}</span>
                                        <div class="mt-1">{{ $report->content_preview }}</div>
                                        <div class="text-muted">
                                            {{ $report->reason_label }} &middot; {{ $report->created_at?->diffForHumans() }}
                                        </div>
                                    </div>
                                    <i class="bi bi-chevron-right text-muted"></i>
                                </div>
                            </a>
                        @empty
                            <div class="list-group-item text-center text-muted py-4 small">
                                <i class="bi bi-shield-check me-1"></i>
                                {{ __('Nothing to moderate right now.') }}
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

```

## `resources/views/dashboards/teacher.blade.php`

```blade
<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h1 class="h5 fw-semibold mb-0">
                <i class="bi bi-easel me-1"></i> {{ __('Teacher dashboard') }}
            </h1>
            <span class="badge text-bg-primary text-capitalize">{{ auth()->user()->role }}</span>
        </div>
    </x-slot>

    <div class="container">
        {{-- My numbers (FR-22) --}}
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <x-stat-card :label="__('My classes')" :value="$stats['classes']" icon="bi-easel" color="primary" />
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <x-stat-card :label="__('Upcoming classes')" :value="$stats['upcoming']" icon="bi-calendar-check" color="success" />
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <x-stat-card :label="__('Enrolled students')" :value="$stats['students']" icon="bi-people" color="info" />
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <x-stat-card :label="__('Followers')" :value="$stats['followers']" icon="bi-person-hearts" color="warning" />
            </div>
        </div>

        <div class="row g-3">
            {{-- My classes, paginated (FR-18, FR-24) --}}
            <div class="col-12 col-xl-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h2 class="h6 fw-bold mb-0">{{ __('My classes') }}</h2>
                        <span class="small text-muted">{{ $classes->total() }}</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">{{ __('Title') }}</th>
                                    <th scope="col">{{ __('Date') }}</th>
                                    <th scope="col">{{ __('Status') }}</th>
                                    <th scope="col" class="text-end">{{ __('Enrolled') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($classes as $class)
                                    @php
                                        // Badge colour for the class status.
                                        $statusColor = match ($class->status) {
                                            \App\Models\ClassRoom::STATUS_LIVE => 'danger',
                                            \App\Models\ClassRoom::STATUS_COMPLETED => 'secondary',
                                            default => 'primary',
                                        };
                                    @endphp
                                    <tr>
                                        <td>
                                            {{-- Every row links to the class detail page (FR-18) --}}
                                            <a href="{{ route('classes.show', $class) }}"
                                               class="fw-semibold text-decoration-none text-dark">{{ $class->title }}</a>
                                            @if ($class->subject)
                                                <div class="small text-muted">{{ $class->subject }}</div>
                                            @endif
                                        </td>
                                        <td class="text-nowrap">
                                            {{ $class->date?->format('d M Y') }}
                                            <div class="small text-muted">
                                                {{ \Illuminate\Support\Str::substr((string) $class->time, 0, 5) }}
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge text-bg-{{ $statusColor }} text-capitalize">
                                                {{ $class->status }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            {{-- students_count comes from withCount() --}}
                                            <a href="{{ route('classes.students', $class) }}"
                                               class="badge bg-light text-dark border text-decoration-none"
                                               title="{{ __('See the enrolled students') }}">
                                                {{ $class->students_count }}
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            <i class="bi bi-inbox me-1"></i> {{ __('You have not created a class yet.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="card-body pt-0">
                        {{-- Bootstrap 5 pagination --}}
                        <x-pagination-links :paginator="$classes" />
                    </div>
                </div>
            </div>

            {{-- Latest likes, comments and new followers (FR-25) --}}
            <div class="col-12 col-xl-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h2 class="h6 fw-bold mb-0">{{ __('Recent activity') }}</h2>
                    </div>
                    <div class="list-group list-group-flush">
                        @forelse ($activity as $item)
                            <div class="list-group-item d-flex gap-3">
                                <i class="bi {{ $item['icon'] }} text-{{ $item['color'] }} fs-5"></i>
                                <div class="small">
                                    {{-- Text is escaped; the excerpt is optional --}}
                                    <div>{{ $item['text'] }}</div>
                                    @if ($item['excerpt'])
                                        <div class="text-muted">{{ \Illuminate\Support\Str::limit($item['excerpt'], 60) }}</div>
                                    @endif
                                    <div class="text-muted">{{ $item['at']?->diffForHumans() }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="list-group-item text-center text-muted py-4 small">
                                <i class="bi bi-activity me-1"></i> {{ __('Nothing has happened yet.') }}
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

```

## `resources/views/dashboards/student.blade.php`

```blade
<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h1 class="h5 fw-semibold mb-0">
                <i class="bi bi-mortarboard me-1"></i> {{ __('Student dashboard') }}
            </h1>
            <span class="badge text-bg-success text-capitalize">{{ auth()->user()->role }}</span>
        </div>
    </x-slot>

    <div class="container">
        {{-- My numbers (FR-21) --}}
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-4">
                <x-stat-card :label="__('Enrolled classes')" :value="$stats['classes']" icon="bi-journal-bookmark" color="primary" />
            </div>
            <div class="col-12 col-md-4">
                <x-stat-card :label="__('Upcoming classes')" :value="$stats['upcoming']" icon="bi-calendar-check" color="success" />
            </div>
            <div class="col-12 col-md-4">
                <x-stat-card :label="__('Teachers followed')" :value="$stats['following']" icon="bi-person-hearts" color="info" />
            </div>
        </div>

        <div class="row g-3">
            {{-- Classes I am enrolled in and that are still ahead (FR-20) --}}
            <div class="col-12 col-xl-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h2 class="h6 fw-bold mb-0">{{ __('My upcoming classes') }}</h2>
                        <span class="small text-muted">{{ $upcomingClasses->count() }}</span>
                    </div>

                    <div class="card-body">
                        <div class="row g-3">
                            @forelse ($upcomingClasses as $class)
                                <div class="col-12 col-md-6">
                                    <x-class-card :class-room="$class" :url="route('classes.show', $class)">
                                        {{-- A student may only enter a running class (FR-20);
                                             otherwise the page explains when the Join button appears. --}}
                                        @if ($class->isLive())
                                            <a href="{{ route('classes.room', $class) }}" class="btn btn-sm btn-danger">
                                                <i class="bi bi-camera-video me-1"></i> {{ __('Join live') }}
                                            </a>
                                        @else
                                            <a href="{{ route('classes.show', $class) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye me-1"></i> {{ __('Details') }}
                                            </a>
                                            <span class="badge text-bg-light border align-self-center">
                                                {{ __('Join opens when the teacher starts it') }}
                                            </span>
                                        @endif
                                    </x-class-card>
                                </div>
                            @empty
                                <div class="col-12 text-center text-muted py-4">
                                    <i class="bi bi-calendar-x me-1"></i>
                                    {{ __('You have no upcoming class. Browse the classes to join one.') }}
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- Teachers I do not follow yet (FR-11, FR-30) --}}
            <div class="col-12 col-xl-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h2 class="h6 fw-bold mb-0">{{ __('Recommended teachers') }}</h2>
                    </div>

                    <div class="card-body">
                        <div class="d-flex flex-column gap-3">
                            @forelse ($recommendedTeachers as $teacher)
                                <x-user-card :user="$teacher">
                                    {{-- Follow button: posts to the follow route --}}
                                    <form method="POST" action="{{ route('follows.store', $teacher) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            <i class="bi bi-person-plus me-1"></i> {{ __('Follow') }}
                                        </button>
                                    </form>
                                </x-user-card>
                            @empty
                                <div class="text-center text-muted py-4 small">
                                    <i class="bi bi-check2-circle me-1"></i>
                                    {{ __('You already follow every teacher.') }}
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

```

## Email template

## `resources/views/emails/reset-password.blade.php`

```blade
{{--
    Branded password reset email (FR-3).
    Kept intentionally simple: one heading, one button, plain text fallback.
--}}
<x-mail::message>
# Reset your password

Hello {{ $name }},

We received a request to reset the password for your EduConnect account.

<x-mail::button :url="$url" color="primary">
Choose a new password
</x-mail::button>

This link expires in {{ $expires }} minutes. If you did not ask for a reset, you can ignore this email — your password will not change.

Thanks,<br>
{{ config('app.name', 'EduConnect') }}
</x-mail::message>

```

## Front-end assets

## `resources/js/auth.js`

```js
/**
 * Authentication page helpers (FR-1, FR-2, FR-3).
 *
 * Two small progressive enhancements, written in plain JavaScript:
 *   1. show / hide a password field,
 *   2. show the optional "subject" field only for teachers.
 *
 * The forms still work if this file fails to load; every field has a proper
 * label and a normal server-side validation fallback.
 */

function setupPasswordToggles() {
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const input = document.getElementById(button.dataset.passwordToggle);

        if (! input) {
            return;
        }

        button.addEventListener('click', () => {
            const isHidden = input.type === 'password';

            input.type = isHidden ? 'text' : 'password';
            button.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
            button.setAttribute(
                'aria-label',
                isHidden ? button.dataset.hideLabel : button.dataset.showLabel,
            );

            const icon = button.querySelector('i');

            if (icon) {
                icon.classList.toggle('bi-eye', ! isHidden);
                icon.classList.toggle('bi-eye-slash', isHidden);
            }

            // Keep the cursor in the field after changing its type.
            input.focus();
        });
    });
}

function setupRoleFields() {
    const roleSelect = document.querySelector('[data-role-select]');
    const subjectField = document.querySelector('[data-subject-field]');

    if (! roleSelect || ! subjectField) {
        return;
    }

    const syncSubjectField = () => {
        subjectField.hidden = roleSelect.value !== 'teacher';
    };

    roleSelect.addEventListener('change', syncSubjectField);
    syncSubjectField();
}

function bootAuthHelpers() {
    setupPasswordToggles();
    setupRoleFields();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootAuthHelpers);
} else {
    bootAuthHelpers();
}

```

## `resources/js/app.js`

```js
import './bootstrap'; // axios setup (window.axios)

// Bootstrap 5 JavaScript (dropdowns, modals, collapses...) + Popper
import * as bootstrap from 'bootstrap';

import Alpine from 'alpinejs';

// Authentication helpers: password visibility + teacher subject field (FR-1 ... FR-3).
import './auth';

// Feed interactions: likes, comments, follows and "Load more" (FR-11, FR-13, FR-17).
import './feed';

// Live class room: embeds Jitsi Meet through the External API (FR-18, FR-22).
import './jitsi';

// Notification bell: unread badge + newest five, refreshed by polling (FR-24).
import './notifications';

// Chart.js 4 with every controller/scale registered ("/auto").
// Used by the admin dashboard and later by the admin analytics page (FR-31).
import Chart from 'chart.js/auto';

// Make them available to plain <script> tags inside Blade views
window.bootstrap = bootstrap;
window.Alpine = Alpine;
window.Chart = Chart;

Alpine.start();

/**
 * Turn every <canvas data-chart='{...}'> into a real chart.
 * Blade views only have to print the Chart.js configuration as JSON,
 * which keeps the JavaScript out of the markup (no inline scripts).
 */
function renderCharts() {
    document.querySelectorAll('canvas[data-chart]').forEach((canvas) => {
        try {
            // The config travels as JSON in the data-chart attribute.
            new Chart(canvas, JSON.parse(canvas.dataset.chart));
        } catch (error) {
            console.error('Could not build the chart on', canvas, error);
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', renderCharts);
} else {
    renderCharts();
}

```

## `resources/sass/app.scss`

```scss
// -----------------------------------------------------------------------------
// EduConnect stylesheet
// Bootstrap 5 and Bootstrap Icons are pulled straight from node_modules and
// compiled by Vite (no Tailwind anywhere in this project).
// -----------------------------------------------------------------------------

@import 'bootstrap/scss/bootstrap';
@import 'bootstrap-icons/font/bootstrap-icons.css';

// --- EduConnect theme colours -------------------------------------------------
:root {
    --edu-primary: #4f46e5;
    --edu-primary-dark: #3730a3;
    --edu-bg: #f6f7fb;
}

body {
    background-color: var(--edu-bg);
}

// Use our brand colour for every primary button
.btn-primary {
    --bs-btn-bg: var(--edu-primary);
    --bs-btn-border-color: var(--edu-primary);
    --bs-btn-hover-bg: var(--edu-primary-dark);
    --bs-btn-hover-border-color: var(--edu-primary-dark);
    --bs-btn-active-bg: var(--edu-primary-dark);
    --bs-btn-active-border-color: var(--edu-primary-dark);
}

.text-edu {
    color: var(--edu-primary) !important;
}

// Soft, clean cards
.card {
    border-radius: 0.75rem;
}

// Small round avatar used in the navbar and on the profile page
.avatar {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 50%;
}

.avatar-lg {
    width: 96px;
    height: 96px;
    object-fit: cover;
    border-radius: 50%;
}

// Used by the post feed later on
.post-body {
    white-space: pre-wrap;
    word-break: break-word;
}

// --- Layout shell: sidebar + content -----------------------------------------
// The sidebar is a normal column from lg upwards and an offcanvas drawer on
// phones (Bootstrap 5.3 "offcanvas-lg" does the switch for us).
.edu-sidebar {
    width: 260px;
    background-color: #fff;
    border-right: 1px solid var(--bs-border-color);
}

.edu-sidebar .nav-link {
    border-radius: 0.5rem;
    padding: 0.55rem 0.75rem;
}

.edu-sidebar .nav-link.active {
    background-color: rgba(79, 70, 229, 0.1);
    color: var(--edu-primary);
    font-weight: 600;
}

// Charts need a fixed height, otherwise Chart.js keeps growing the canvas.
.edu-chart {
    position: relative;
    height: 320px;
}

// Small square icon chip used by the stat cards
.stat-icon {
    width: 48px;
    height: 48px;
    flex: 0 0 48px;
    border-radius: 0.75rem;
}

// Alpine.js helpers: hide elements until Alpine has taken over
[x-cloak] {
    display: none !important;
}

// --- Live class room (FR-18) --------------------------------------------------
// Jitsi builds its iframe inside .jitsi-frame, so this element needs a real
// height. It fills the screen minus the navbar, the page header and the footer.
.jitsi-frame {
    position: relative;
    width: 100%;
    height: calc(100vh - 340px);
    min-height: 420px;
    border-radius: 0.75rem;
    overflow: hidden;
    background-color: #1c1c1c;

    // Jitsi writes inline width/height on the iframe; make it fill the box.
    iframe {
        width: 100% !important;
        height: 100% !important;
        border: 0;
    }
}

// On a phone the room gets the whole width and as much height as possible.
@media (max-width: 991.98px) {
    .jitsi-frame {
        height: calc(100vh - 260px);
        min-height: 360px;
        border-radius: 0.5rem;
    }
}

// Touch friendly: every control is at least 44px tall on phones
@media (max-width: 575.98px) {
    .btn,
    .form-control,
    .form-select {
        min-height: 44px;
    }
}

// --- Notifications and search (FR-14, FR-24, FR-25) --------------------------
// The search box in the navbar.
.edu-search {
    width: 260px;
}

@media (min-width: 992px) {
    .edu-search {
        width: 320px;
    }
}

// The bell dropdown holds up to five entries, so it is a bit wider than default.
.notification-menu {
    width: min(360px, 92vw);
    max-height: 70vh;
    overflow-y: auto;
}

// Unread entries stand out with a soft brand tint and a left border.
.notification-unread {
    background-color: rgba(79, 70, 229, 0.06);
    border-left: 3px solid var(--edu-primary);
}

// Small timestamp line inside a notification entry.
.edu-time {
    font-size: 0.75rem;
}

// --- Authentication split screen (FR-1, FR-2, FR-3) --------------------------
// A branded indigo panel on the left and the form on the right. On phones the
// brand panel is replaced by a compact logo above the card.
.edu-auth-body {
    background-color: #fff;
}

.edu-auth-brand {
    position: relative;
    overflow: hidden;
    color: #fff;
    background: linear-gradient(145deg, #312e81 0%, #4338ca 45%, #4f46e5 100%);
}

// Soft light circles keep the panel from looking flat.
.edu-auth-brand::before,
.edu-auth-brand::after {
    content: '';
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.08);
    pointer-events: none;
}

.edu-auth-brand::before {
    width: 420px;
    height: 420px;
    top: -160px;
    right: -140px;
}

.edu-auth-brand::after {
    width: 300px;
    height: 300px;
    bottom: -120px;
    left: -100px;
}

.edu-auth-brand > * {
    position: relative;
    z-index: 1;
}

.edu-auth-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    flex: 0 0 44px;
    border-radius: 0.75rem;
    background: rgba(255, 255, 255, 0.14);
    font-size: 1.15rem;
}

.edu-auth-form {
    max-width: 440px;
}

.edu-auth-card {
    border-radius: 1rem;
}

// The password toggle should not take the full input-group height on desktop.
.password-toggle {
    min-width: 48px;
}

@media (max-width: 991.98px) {
    .edu-auth-body {
        background-color: var(--edu-bg);
    }
}

```

## Tests

## `tests/Feature/Auth/RegistrationTest.php`

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A visitor can register as a teacher and lands on the teacher dashboard (FR-1, FR-2).
     */
    public function test_a_teacher_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'New Teacher',
            'email' => 'new.teacher@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'teacher',
            'subject' => 'Mathematics',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/teacher/dashboard');

        $this->assertDatabaseHas('users', [
            'email' => 'new.teacher@example.com',
            'role' => 'teacher',
            'subject' => 'Mathematics',
        ]);
    }

    /**
     * A visitor can register as a student and lands on the student dashboard (FR-1, FR-2).
     */
    public function test_a_student_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'New Student',
            'email' => 'new.student@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'student',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/student/dashboard');

        $this->assertDatabaseHas('users', [
            'email' => 'new.student@example.com',
            'role' => 'student',
            'subject' => null,
        ]);
    }

    /**
     * Nobody can create an admin account through the public form (FR-1).
     */
    public function test_the_admin_role_cannot_be_registered(): void
    {
        $response = $this->post('/register', [
            'name' => 'Sneaky User',
            'email' => 'sneaky@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'admin',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'sneaky@example.com']);
        $this->assertSame(0, User::where('role', User::ROLE_ADMIN)->count());
    }

    /**
     * The subject is optional: a teacher can add it later from the profile (FR-4).
     */
    public function test_a_teacher_can_register_without_a_subject(): void
    {
        $response = $this->post('/register', [
            'name' => 'No Subject Teacher',
            'email' => 'nosubject@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'teacher',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/teacher/dashboard');

        $this->assertDatabaseHas('users', [
            'email' => 'nosubject@example.com',
            'role' => 'teacher',
            'subject' => null,
        ]);
    }
}

```

## `tests/Feature/Auth/AuthenticationTest.php`

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        // FR-2: after login everybody lands on the dashboard of their own role.
        $response->assertRedirect(route('student.dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    /**
     * FR-2: every role is sent to the dashboard it is allowed to open.
     */
    public function test_an_admin_is_redirected_to_the_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_a_teacher_is_redirected_to_the_teacher_dashboard(): void
    {
        $teacher = User::factory()->teacher()->create();

        $response = $this->post('/login', [
            'email' => $teacher->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($teacher);
        $response->assertRedirect(route('teacher.dashboard', absolute: false));
    }

    /**
     * FR-1/FR-2: the split-screen auth pages expose the password toggles.
     */
    public function test_the_auth_pages_use_the_branded_layout_and_password_toggles(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('edu-auth-brand', false)
            ->assertSee('data-password-toggle', false);

        $this->get('/register')
            ->assertOk()
            ->assertSee('data-role-select', false)
            ->assertSee('data-subject-field', false)
            ->assertSee('data-password-toggle', false)
            ->assertDontSee('value="admin"', false);
    }
}

```

## `tests/Feature/Auth/BlockedUserTest.php`

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class BlockedUserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A blocked user may not log in, even with the correct password (FR-6).
     */
    public function test_a_blocked_user_cannot_log_in(): void
    {
        User::factory()->blocked()->create([
            'email' => 'blocked@example.com',
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'blocked@example.com',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');

        $this->assertStringContainsString(
            'blocked',
            (string) session('errors')->first('email')
        );
    }

    /**
     * A user who is blocked while logged in is kicked out on the next request (FR-6).
     */
    public function test_a_logged_in_user_is_logged_out_when_blocked(): void
    {
        $user = User::factory()->create();

        // The user is already authenticated (simulates a still-open session).
        Auth::login($user);

        // The administrator blocks the account.
        $user->forceFill(['is_blocked' => true])->save();

        $response = $this->get('/profile');

        $this->assertGuest();
        $response->assertRedirect('/login');
        $response->assertSessionHas('error');
    }
}

```

## `tests/Feature/Auth/PasswordResetTest.php`

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    /**
     * FR-3: the reset email uses the branded EduConnect template and link.
     */
    public function test_the_reset_email_uses_the_branded_template(): void
    {
        $user = User::factory()->create(['name' => 'Maria Rossi']);

        $mail = (new ResetPasswordNotification('test-token'))->toMail($user);

        $this->assertSame('Reset your EduConnect password', $mail->subject);
        $this->assertSame('emails.reset-password', $mail->markdown);
        $this->assertSame('Maria Rossi', $mail->viewData['name']);
        $this->assertStringContainsString('/reset-password/test-token', $mail->viewData['url']);

        // The Markdown template renders without errors (button text is present).
        $this->assertStringContainsString('Choose a new password', $mail->render());
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) {
            $response = $this->get('/reset-password/'.$notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });
    }
}

```

## `tests/Feature/RoleAccessTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A student can never open the admin dashboard (FR-5).
     */
    public function test_a_student_cannot_open_the_admin_dashboard(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->get('/admin/dashboard')
            ->assertForbidden();
    }

    /**
     * Each role reaches its own dashboard (FR-2).
     */
    public function test_each_role_reaches_its_own_dashboard(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/dashboard')
            ->assertOk();

        $this->actingAs(User::factory()->teacher()->create())
            ->get('/teacher/dashboard')
            ->assertOk();

        $this->actingAs(User::factory()->student()->create())
            ->get('/student/dashboard')
            ->assertOk();
    }

    /**
     * /dashboard forwards everybody to the dashboard of their own role (FR-2).
     */
    public function test_the_generic_dashboard_redirects_by_role(): void
    {
        $this->actingAs(User::factory()->teacher()->create())
            ->get('/dashboard')
            ->assertRedirect('/teacher/dashboard');
    }

    /**
     * Guests are sent to the login page.
     */
    public function test_a_guest_cannot_open_the_admin_dashboard(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }
}

```
