# EduConnect — Phase 1 deliverable

Project setup, authentication, roles and seeders. Every path below is relative to
the project root and the code is copied verbatim from the working repository.

**Snapshot of the delivered build**

| Item | Value |
| --- | --- |
| PHP | 8.3.31 (requires 8.2+) |
| Framework | Laravel **12.70.2** |
| Database | MySQL 8 (configured in `.env`; the test suite uses SQLite `:memory:`) |
| Front end | Blade + Bootstrap 5.3.8 + Bootstrap Icons + Popper, compiled with Vite 6 |
| Auth scaffold | Laravel Breeze (Blade stack), Tailwind removed |
| Full test suite | `php artisan test` → **266 passed, 1401 assertions** |
| Phase 1 suite | `RegistrationTest` + `AuthenticationTest` + `BlockedUserTest` + `PasswordResetTest` + `RoleAccessTest` → **22 passed, 74 assertions** |

> **Framework deviation.** The brief asks for Laravel 11. The delivered
> application is built on Laravel 12.70.2 because that is the installed
> framework in this workspace. The Phase 1 code is version agnostic: the
> middleware alias registration in `bootstrap/app.php`, the Breeze controllers
> and the migrations are identical in Laravel 11. The only difference for a
> fresh Laravel 11 install is the framework constraint in `composer.json`
> (`laravel/framework: ^11.0`) and the Breeze version (`^2.1`). This deviation
> is recorded in `README.md` under *Known deviations from the SRS*.

> **Guest landing page.** The master brief says guests may only see login and
> register. The delivered application also serves a public landing page at `/`
> (and `/up` as a health check). `/dashboard` and every role dashboard are
> behind `auth`, so no private data is exposed. Remove the `/` route in
> `routes/web.php` if you need the stricter interpretation.

## 1. Files in this phase

| Area | Files |
| --- | --- |
| Setup | `composer.json`, `package.json`, `vite.config.js`, `.env.example`, `bootstrap/app.php`, `config/educonnect.php` |
| Database | `0001_01_01_000000_create_users_table.php`, `2026_10_29_000001_add_profile_fields_to_users_table.php`, `UserFactory`, `DatabaseSeeder`, `AdminUserSeeder`, `DemoUserSeeder` |
| Model and validation | `app/Models/User.php`, `RegisterRequest`, `LoginRequest`, `ForgotPasswordRequest`, `ResetPasswordRequest` |
| Controllers | `RegisteredUserController`, `AuthenticatedSessionController`, `PasswordResetLinkController`, `NewPasswordController`, `DashboardController` |
| Middleware and routes | `RoleMiddleware`, `EnsureUserIsNotBlocked`, `routes/auth.php`, `routes/web.php`, `bootstrap/app.php` |
| Views | `layouts/guest`, `layouts/app`, auth components, `auth/*` views, `dashboards/*` (Phase 1 placeholders, expanded in Phase 3), `emails/reset-password` |
| Assets | `resources/sass/app.scss`, `resources/js/app.js`, `resources/js/auth.js` |
| Tests | `tests/Feature/Auth/RegistrationTest.php`, `AuthenticationTest.php`, `BlockedUserTest.php`, `PasswordResetTest.php`, `tests/Feature/RoleAccessTest.php` |

## 2. Exact commands

The commands below create the project from scratch (Laravel 11 as requested).
The delivered repository is already installed, so day-to-day you only need the
last block.

```bash
# 2.1 Create the project
composer create-project laravel/laravel:^11.0 educonnect
cd educonnect

# 2.2 Breeze (Blade stack) — this also pulls in Tailwind, which is removed next
composer require laravel/breeze --dev
php artisan breeze:install blade

# 2.3 Remove Tailwind completely
npm remove tailwindcss @tailwindcss/forms @tailwindcss/vite autoprefixer postcss
rm -f tailwind.config.js postcss.config.js resources/css/app.css
# delete the @tailwind / @vite('resources/css/app.css') lines from the layout
```

```bash
# 2.4 Bootstrap 5 + Icons + Popper through npm
npm install bootstrap@^5.3.3 bootstrap-icons@^1.11.3 @popperjs/core@^2.11.8 sass --save-dev

# 2.5 Configure MySQL + mail
cp .env.example .env
php artisan key:generate
# edit .env: DB_DATABASE=educonnect, DB_USERNAME/DB_PASSWORD, APP_URL,
# MAIL_MAILER=smtp + Mailtrap host/port/user/password
php artisan migrate

# 2.6 Build assets and the storage symlink
npm run build          # or: npm run dev
php artisan storage:link
```

```bash
# 2.7 Database + demo data
php artisan migrate:fresh --seed

# 2.8 Phase 1 tests
php artisan test tests/Feature/Auth/RegistrationTest.php \
                 tests/Feature/Auth/AuthenticationTest.php \
                 tests/Feature/Auth/BlockedUserTest.php \
                 tests/Feature/Auth/PasswordResetTest.php \
                 tests/Feature/RoleAccessTest.php

# 2.9 Run it
php artisan serve
```

`npm run dev` for hot reload while developing, `npm run build` before a demo or a
deploy. `php artisan storage:link` is needed once per machine (the repository
already contains `public/storage`).

## 3. Admin account

`AdminUserSeeder` reads the credentials from `.env`, so no secret is hardcoded:

```dotenv
ADMIN_NAME="EduConnect Admin"
ADMIN_EMAIL=admin@educonnect.test
ADMIN_PASSWORD="EduConnect#Admin2026!"
```

If `ADMIN_PASSWORD` is empty the seeder throws a `RuntimeException` instead of
creating a weak account. Demo teachers and students all use the password
`password`.

## 4. What the code does

- **FR-1 registration** — only `teacher` and `student` are accepted by
  `RegisterRequest` (`Rule::in(User::REGISTERABLE_ROLES)`); `passedValidation()`
  forces the role server side. A posted `role=admin` fails validation. Teacher
  gets an optional `subject`.
- **FR-2 login/logout** — Breeze session controller with remember-me, a
  5-attempt throttle in `LoginRequest` and a redirect through
  `User::dashboardRoute()`.
- **FR-3 password reset** — request link, branded mailable
  (`resources/views/emails/reset-password.blade.php`), signed token reset.
- **FR-6 RBAC** — `role:admin`, `role:teacher,student` middleware alias, plus
  `not.blocked` which logs out a user who is blocked mid-session.


# 5. Setup files

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

```javascript
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

```text
APP_NAME=EduConnect
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8010

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

# Used by the <meta name="description"> tag and social previews.
APP_DESCRIPTION="EduConnect is an online classroom with live video classes, a friendly social feed and simple class management for teachers and students."

APP_MAINTENANCE_DRIVER=file
# APP_MAINTENANCE_STORE=database

PHP_CLI_SERVER_WORKERS=4

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug
# Production: LOG_CHANNEL=daily, LOG_LEVEL=warning, LOG_DAILY_DAYS=14

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
# Production (HTTPS): SESSION_SECURE_COOKIE=true, SESSION_ENCRYPT=true
# The cookie is already HTTPS-only automatically when APP_URL starts with https://
SESSION_SECURE_COOKIE=false
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

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
# "log" is fine for local work; production uses SMTP (see docs/DEPLOYMENT.md).
# Password reset emails are sent synchronously by default. With
# QUEUE_CONNECTION=database the notification is queued and a worker is required.
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

    // Upload rules (used by the Form Requests and ImageUploadService).
    // Files are always re-encoded with GD, so these limits also decide the
    // dimensions and quality of what actually lands on disk (SRS 5.1).
    'uploads' => [
        'post_image_dir' => 'posts',                          // -> storage/app/public/posts
        'post_image_max_kb' => 2048,                          // 2 MB
        'post_image_mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        'post_image_max_width' => 1600,
        'post_image_max_height' => 1600,
        'post_image_quality' => 82,

        'profile_photo_dir' => 'profiles',                    // -> storage/app/public/profiles
        'profile_photo_max_kb' => 2048,                       // 2 MB
        'profile_photo_mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        'profile_photo_max_width' => 512,
        'profile_photo_max_height' => 512,
        'profile_photo_quality' => 85,
    ],

    // Default meta description for pages that do not provide their own.
    'meta' => [
        'description' => env(
            'APP_DESCRIPTION',
            'EduConnect is an online classroom with live video classes, a friendly social feed and simple class management for teachers and students.'
        ),
    ],

];

```

# 6. Database

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
 * @extends Factory<User>
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

## `database/seeders/DemoUserSeeder.php`

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

# 7. Model, validation, controllers and middleware

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

## `app/Http/Requests/Auth/ForgotPasswordRequest.php`

```php
<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }
}

```

## `app/Http/Requests/Auth/ResetPasswordRequest.php`

```php
<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
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
use App\Http\Requests\Auth\ForgotPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
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
    public function store(ForgotPasswordRequest $request): RedirectResponse
    {
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
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
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
    public function store(ResetPasswordRequest $request): RedirectResponse
    {
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

# 8. Routes
`bootstrap/app.php` (section 5) registers the aliases `role` →

`RoleMiddleware` and `not.blocked` → `EnsureUserIsNotBlocked`, and appends the
blocked-user check to the whole `web` group. `routes/auth.php` holds the guest
and authenticated auth routes; `routes/web.php` maps the three role prefixes.


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
        ->middleware('throttle:register');

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    // The login attempt itself is rate limited inside LoginRequest (5 tries).
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    // Rate limited so nobody can spam reset emails (FR-3).
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:password-reset')
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:password-reset')
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
use App\Http\Controllers\DashboardChartController;
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

        // Phase 3C: living documentation of the design system. Admin only,
        // because it is a maintenance page rather than a product feature.
        Route::view('/style-guide', 'admin.style-guide')->name('style-guide');

        // Phase 3B: JSON series for the dashboard range selector.
        Route::get('/dashboard/charts', [DashboardChartController::class, 'admin'])
            ->name('dashboard.charts');

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

        // Phase 3B: JSON series for the dashboard range selectors.
        Route::get('/dashboard/charts', [DashboardChartController::class, 'teacher'])
            ->name('dashboard.charts');
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

        // Phase 3B: JSON series for the dashboard range selectors.
        Route::get('/dashboard/charts', [DashboardChartController::class, 'student'])
            ->name('dashboard.charts');
    });

// Follow / unfollow another member from a profile or the search page
// (FR-11). Students and teachers can follow each other; admin accounts
// are not followable and admins do not use the follow routes.
// Throttled so the button cannot be used to hammer the database.
Route::middleware(['auth', 'not.blocked', 'role:student,teacher'])
    ->group(function () {
        Route::post('/u/{user}/follow', [FollowController::class, 'store'])
            ->middleware('throttle:follows')
            ->name('follows.store');

        Route::delete('/u/{user}/follow', [FollowController::class, 'destroy'])
            ->middleware('throttle:follows')
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
        ->middleware('throttle:posts')
        ->name('posts.store');
    Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');
    Route::get('/posts/{post}/edit', [PostController::class, 'edit'])->name('posts.edit');
    Route::patch('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');

    // Likes - small JSON endpoints for the fetch() calls in the feed (FR-11).
    Route::post('/posts/{post}/like', [LikeController::class, 'store'])
        ->middleware('throttle:likes')
        ->name('posts.like');
    Route::delete('/posts/{post}/like', [LikeController::class, 'destroy'])
        ->middleware('throttle:likes')
        ->name('posts.unlike');

    // Comments (FR-13).
    Route::post('/posts/{post}/comments', [CommentController::class, 'store'])
        ->middleware('throttle:comments')
        ->name('comments.store');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])
        ->middleware('throttle:comments')
        ->name('comments.destroy');

    // Reporting a post or comment (FR-32). Throttled so the queue cannot be
    // flooded by one account.
    Route::post('/posts/{post}/reports', [ReportController::class, 'storePost'])
        ->middleware('throttle:reports')
        ->name('reports.posts.store');
    Route::post('/comments/{comment}/reports', [ReportController::class, 'storeComment'])
        ->middleware('throttle:reports')
        ->name('reports.comments.store');

    // Hashtag pages (FR-13). The binding uses the (lower case) name column.
    // /hashtags/{name} is the canonical URL; the old singular address remains
    // available so earlier links keep working.
    Route::get('/hashtags/{hashtag:name}', [HashtagController::class, 'show'])->name('hashtags.show');
    Route::get('/hashtag/{hashtag:name}', [HashtagController::class, 'show'])->name('hashtags.legacy');

    // Public profiles and the follower lists (FR-5, FR-11).
    Route::get('/users/{user}', [UserProfileController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/followers', [UserProfileController::class, 'followers'])->name('users.followers');
    Route::get('/users/{user}/following', [UserProfileController::class, 'following'])->name('users.following');

    // The older /u/... addresses stay valid and redirect to the canonical URL.
    Route::get('/u/{user}', fn ($user) => redirect()->route('users.show', $user));
    Route::get('/u/{user}/followers', fn ($user) => redirect()->route('users.followers', $user));
    Route::get('/u/{user}/following', fn ($user) => redirect()->route('users.following', $user));

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
| The bell in the navbar polls /notifications/poll every 30 seconds and gets
| the fresh unread counter plus the newest eight entries as JSON.
*/
Route::middleware(['auth', 'not.blocked'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');

    // Lightweight JSON endpoint for the polling script (FR-24).
    Route::get('/notifications/poll', [NotificationController::class, 'poll'])
        ->middleware('throttle:notifications-poll')
        ->name('notifications.poll');

    // Mark everything as read (FR-25). Declared before the {notification} routes.
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

    // One notification: mark it read, open its page, or delete it (FR-25).
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::get('/notifications/{notification}/open', [NotificationController::class, 'open'])->name('notifications.open');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
});

/*
|--------------------------------------------------------------------------
| Search (FR-14)
|--------------------------------------------------------------------------
| One box in the navbar, three tabs on the page: people, hashtags and posts.
*/
Route::middleware(['auth', 'not.blocked'])->group(function () {
    Route::get('/search', [SearchController::class, 'index'])->name('search.index');

    // Debounced live suggestions for the navbar dropdown (FR-14).
    Route::get('/search/suggest', [SearchController::class, 'suggest'])
        ->middleware('throttle:search-suggest')
        ->name('search.suggest');
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

# 9. Layouts and components

## `resources/views/layouts/guest.blade.php`

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="color-scheme" content="light dark">

        <title>{{ $attributes->get('title') ? $attributes->get('title').' · ' : '' }}{{ config('app.name', 'EduConnect') }}</title>

        {{-- Apply the saved theme before the first paint (same key as the app layout). --}}
        <script>
            (function () {
                try {
                    var stored = window.localStorage.getItem('educonnect.theme');
                    var theme = stored === 'dark' || stored === 'light'
                        ? stored
                        : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                    document.documentElement.setAttribute('data-bs-theme', theme);
                } catch (error) {
                    document.documentElement.setAttribute('data-bs-theme', 'light');
                }
            })();
        </script>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

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
                                <span class="h4 fw-bold text-body mb-0">{{ config('app.name', 'EduConnect') }}</span>
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
        <meta name="color-scheme" content="light dark">

        @php
            // A page can pass <x-slot name="title">Page title</x-slot> and
            // <x-slot name="description">Meta description</x-slot>.
            $pageTitle = isset($title) ? trim(strip_tags((string) $title)) : '';
            $pageDescription = isset($description) && trim(strip_tags((string) $description)) !== ''
                ? trim(strip_tags((string) $description))
                : config('educonnect.meta.description');
            $fullTitle = ($pageTitle !== '' ? $pageTitle.' · ' : '').config('app.name', 'EduConnect');
        @endphp
        <title>{{ $fullTitle }}</title>
        <meta name="description" content="{{ $pageDescription }}">
        <link rel="canonical" href="{{ url()->current() }}">

        {{-- Social previews use the same title and description. --}}
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ config('app.name', 'EduConnect') }}">
        <meta property="og:title" content="{{ $fullTitle }}">
        <meta property="og:description" content="{{ $pageDescription }}">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="{{ $fullTitle }}">
        <meta name="twitter:description" content="{{ $pageDescription }}">

        {{-- Favicon and browser chrome colours (light + dark). --}}
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <link rel="apple-touch-icon" href="{{ asset('favicon.ico') }}">
        <meta name="theme-color" content="#4f46e5" media="(prefers-color-scheme: light)">
        <meta name="theme-color" content="#0f172a" media="(prefers-color-scheme: dark)">

        {{-- Theme first: before the stylesheet so there is no flash of the
             wrong colour scheme. Explicit choice wins, then the OS setting. --}}
        <script>
            (function () {
                try {
                    var stored = window.localStorage.getItem('educonnect.theme');
                    var theme = stored === 'dark' || stored === 'light'
                        ? stored
                        : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                    document.documentElement.setAttribute('data-bs-theme', theme);
                } catch (error) {
                    document.documentElement.setAttribute('data-bs-theme', 'light');
                }
            })();
        </script>

        {{-- Apply the remembered desktop sidebar state before the first paint,
             so the layout does not jump when JavaScript finishes loading. --}}
        <script>
            try {
                if (window.localStorage.getItem('educonnect.sidebar.collapsed') === '1') {
                    document.documentElement.classList.add('edu-sidebar-collapsed');
                }
            } catch (error) {
                // Private browsing can refuse localStorage; the default open
                // sidebar is a perfectly fine fallback.
            }
        </script>

        {{-- Inter is loaded from Google Fonts (allowed by the phase brief). --}}
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    </head>
    @php
        // One unread counter shared by the top bar and the mobile navigation,
        // so both badges always agree and the feed keeps its query budget.
        $layoutUnreadCount = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;

        // The public landing page uses this layout too, but a signed-in visitor
        // should see the marketing site, not the dashboard sidebar, mobile nav
        // or composer shortcut. Those are dashboard chrome only.
        $isLandingPage = request()->is('/');
    @endphp

    {{-- body carries the notification polling URL for logged in users (FR-24) --}}
    <body class="edu-body d-flex flex-column min-vh-100 {{ auth()->check() && ! $isLandingPage ? 'edu-has-mobile-nav' : '' }}"
          @auth data-notification-url="{{ route('notifications.poll') }}" @endauth>
        <a class="visually-hidden-focusable edu-skip-link" href="#main-content" style="position:fixed;">
            {{ __('Skip to main content') }}
        </a>

        {{-- Sticky, blurred top navigation bar --}}
        @include('layouts.navigation', ['unreadNotifications' => $layoutUnreadCount])

        <div class="edu-shell flex-grow-1" id="appShell">
            {{-- Role based sidebar: a column on desktop, an offcanvas drawer on phones.
                 Never on the public landing page. --}}
            @auth
                @unless ($isLandingPage)
                    @include('layouts.sidebar')
                @endunless
            @endauth

            <div class="edu-main">
                <main class="edu-main-inner" id="main-content">
                    <div class="container-xxl px-3 px-lg-4">
                        {{-- Success / error / validation messages --}}
                        <x-flash />

                        {{-- Page header supplied by the view. Breadcrumbs live in
                             the top bar, so this only renders when a page has one. --}}
                        @isset($header)
                            <div class="edu-page-header mb-4">
                                {{ $header }}
                            </div>
                        @endisset
                    </div>

                    {{-- Page body. The CSS fades this in; page-transitions.js adds
                         the matching fade-out when a link is followed. --}}
                    <div class="edu-page">
                        {{ $slot }}
                    </div>
                </main>

                <footer class="edu-footer mt-auto">
                    <div class="container-xxl px-3 px-lg-4">
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                            <span>
                                &copy; {{ date('Y') }} {{ config('app.name', 'EduConnect') }}
                            </span>
                            <span class="d-inline-flex align-items-center gap-2">
                                <i class="bi bi-mortarboard-fill text-edu" aria-hidden="true"></i>
                                {{ __('Learn together, anywhere.') }}
                            </span>
                        </div>
                    </div>
                </footer>
            </div>
        </div>

        {{-- Page specific JavaScript (kept out of the markup). --}}
        @stack('scripts')

        @auth
            @unless ($isLandingPage)
                {{-- Mobile bottom navigation: five destinations, always one tap away. --}}
                @include('layouts.mobile-nav', ['unreadCount' => $layoutUnreadCount])

                {{-- Floating composer shortcut, feed only, never over the desktop layout. --}}
                @if (request()->routeIs('feed.index'))
                    <a class="edu-fab d-lg-none" href="{{ route('posts.create') }}"
                       aria-label="{{ __('New post') }}" title="{{ __('New post') }}">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
                    </a>
                @endif
            @endunless
        @endauth
    </body>
</html>

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
    'strength' => false,
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
               @if ($strength) data-password-strength-input @endif
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

    @if ($strength)
        {{-- Progressive enhancement: auth.js fills this in. Without JavaScript
             the server-side rule ("at least 8 characters") still applies. --}}
        <div class="edu-strength" data-password-strength data-score="0"
             data-strength-empty="{{ __('Use at least 8 characters, with a number or a symbol.') }}"
             data-strength-weak="{{ __('Weak password — add more characters.') }}"
             data-strength-fair="{{ __('Fair password — mix letters, numbers and a symbol.') }}"
             data-strength-good="{{ __('Good password — almost there.') }}"
             data-strength-strong="{{ __('Strong password. Well done!') }}"
             data-strength-input>
            <div class="edu-strength-track" aria-hidden="true">
                <span class="edu-strength-seg"></span>
                <span class="edu-strength-seg"></span>
                <span class="edu-strength-seg"></span>
                <span class="edu-strength-seg"></span>
            </div>
            <div class="small text-muted" data-password-strength-label aria-live="polite">
                {{ __('Use at least 8 characters, with a number or a symbol.') }}
            </div>
        </div>
    @endif

    <x-input-error :messages="$errors->get($name)" class="mt-1" />
</div>

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

## `resources/views/components/secondary-button.blade.php`

```blade
<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn btn-outline-secondary']) }}>
    {{ $slot }}
</button>

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

## `resources/views/components/application-logo.blade.php`

```blade
{{-- EduConnect logo: a graduation cap icon --}}
<span {{ $attributes->merge(['class' => 'text-primary']) }}>
    <i class="bi bi-mortarboard-fill"></i>
</span>

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

# 10. Auth views

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
<x-guest-layout :title="__('Create your account')">
    @php
        $requestedRole = request()->query('role');
        $selectedRole = in_array(old('role'), ['teacher', 'student'], true)
            ? old('role')
            : (in_array($requestedRole, ['teacher', 'student'], true) ? $requestedRole : 'student');
    @endphp

    <h1 class="h4 fw-bold mb-1">{{ __('Create your account') }}</h1>
    <p class="text-muted small mb-4">{{ __('Join as a teacher or as a student. You can switch later from your profile.') }}</p>

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
        <fieldset class="mb-3" data-role-select>
            <legend class="form-label mb-2">{{ __('I am a') }}</legend>

            <div class="row g-2">
                @foreach ([
                    'student' => [
                        'icon' => 'bi-mortarboard',
                        'title' => __('Student'),
                        'copy' => __('Follow teachers and enroll in live classes.'),
                    ],
                    'teacher' => [
                        'icon' => 'bi-easel',
                        'title' => __('Teacher'),
                        'copy' => __('Publish classes and share posts with your students.'),
                    ],
                ] as $value => $card)
                    <div class="col-12 col-sm-6">
                        <label class="edu-role-card w-100 h-100 {{ $selectedRole === $value ? 'is-selected' : '' }}">
                            <input type="radio" name="role" value="{{ $value }}"
                                   data-role-radio
                                   @checked($selectedRole === $value) required>

                            <span class="edu-role-icon" aria-hidden="true">
                                <i class="bi {{ $card['icon'] }}"></i>
                            </span>

                            <span class="min-w-0">
                                <span class="d-block fw-semibold">{{ $card['title'] }}</span>
                                <span class="d-block small text-muted">{{ $card['copy'] }}</span>
                            </span>
                        </label>
                    </div>
                @endforeach
            </div>

            <div class="form-text">{{ __('Admin accounts are created by the platform.') }}</div>
            <x-input-error :messages="$errors->get('role')" class="mt-1" />
        </fieldset>

        <!-- Optional subject, revealed for teachers by auth.js. -->
        <div class="mb-3" data-subject-field @if ($selectedRole !== 'teacher') hidden @endif>
            <x-input-label for="subject" :value="__('Subject you teach (optional)')" />
            <x-text-input id="subject" type="text" name="subject" :value="old('subject')"
                          placeholder="{{ __('e.g. Mathematics') }}"
                          maxlength="100"
                          :class="$errors->has('subject') ? 'is-invalid' : ''" />
            <x-input-error :messages="$errors->get('subject')" class="mt-1" />
        </div>

        <!-- Password with show/hide toggle and strength meter -->
        <x-password-input name="password" :label="__('Password')" autocomplete="new-password" :strength="true" />

        <!-- Confirm password -->
        <x-password-input name="password_confirmation" :label="__('Confirm password')" autocomplete="new-password" />

        <x-primary-button class="w-100 justify-content-center">
            <i class="bi bi-person-plus me-1" aria-hidden="true"></i>
            {{ __('Create account') }}
        </x-primary-button>

        <p class="text-center small mt-4 mb-0">
            {{ __('Already registered?') }}
            <a class="text-decoration-none fw-semibold" href="{{ route('login') }}">{{ __('Log in') }}</a>
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

# 11. Email template

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

# 12. Dashboards
Phase 1 shipped one placeholder view per role. Those placeholders were replaced
by the full dashboards in Phase 3, so only the router is reproduced here:

- `app/Http/Controllers/AdminDashboardController.php`
- `app/Http/Controllers/TeacherDashboardController.php`
- `app/Http/Controllers/StudentDashboardController.php`
- `resources/views/dashboards/admin.blade.php`
- `resources/views/dashboards/teacher.blade.php`
- `resources/views/dashboards/student.blade.php`

The complete Phase 3 versions are in `docs/PHASE-3-DELIVERABLE.md`. What matters
for Phase 1 is that `/admin/dashboard`, `/teacher/dashboard` and
`/student/dashboard` exist, are behind `auth` + `role:…` and render for the
right role — proven by `RoleAccessTest`.


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

# 13. Front-end assets
`vitest.config`/Vite compiles `resources/sass/app.scss` and `resources/js/app.js`.
The stylesheet grew across Phases 3–8; the Phase 1 pieces are the Bootstrap
import, the design tokens (`--edu-*`) and the `.auth-*` split-screen rules.


## `resources/js/auth.js`

```javascript
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
    const roleControl = document.querySelector('[data-role-select]');
    const subjectField = document.querySelector('[data-subject-field]');

    if (! roleControl || ! subjectField) {
        return;
    }

    const roleSelect = roleControl.matches('select') ? roleControl : null;
    const roleRadios = roleControl.querySelectorAll('[data-role-radio]');

    const currentRole = () => {
        if (roleSelect) {
            return roleSelect.value;
        }

        const checked = roleControl.querySelector('[data-role-radio]:checked');

        return checked ? checked.value : 'student';
    };

    const syncSubjectField = () => {
        subjectField.hidden = currentRole() !== 'teacher';

        // Keep the whole card highlighted, including in browsers without :has().
        roleRadios.forEach((radio) => {
            const card = radio.closest('.edu-role-card');

            if (card) {
                card.classList.toggle('is-selected', radio.checked);
            }
        });
    };

    if (roleSelect) {
        roleSelect.addEventListener('change', syncSubjectField);
    }

    roleRadios.forEach((radio) => radio.addEventListener('change', syncSubjectField));

    syncSubjectField();
}

/**
 * Password strength meter (register page only).
 *
 * Four independent signals keep the advice understandable: length, mixed
 * case, a number and a symbol. The label is announced through aria-live, so
 * a screen reader hears the same feedback as the colour bars.
 */
function setupPasswordStrength() {
    document.querySelectorAll('[data-password-strength-input]').forEach((input) => {
        const meter = input.closest('.mb-3')?.querySelector('[data-password-strength]');

        if (! meter) {
            return;
        }

        const label = meter.querySelector('[data-password-strength-label]');
        const labels = [
            meter.dataset.strengthEmpty || 'Use at least 8 characters, with a number or a symbol.',
            meter.dataset.strengthWeak || 'Weak password — add more characters.',
            meter.dataset.strengthFair || 'Fair password — mix letters, numbers and a symbol.',
            meter.dataset.strengthGood || 'Good password — almost there.',
            meter.dataset.strengthStrong || 'Strong password. Well done!',
        ];

        const update = () => {
            const value = input.value;
            let score = 0;

            if (value.length >= 8) {
                score += 1;
            }
            if (/[a-z]/.test(value) && /[A-Z]/.test(value)) {
                score += 1;
            }
            if (/\d/.test(value)) {
                score += 1;
            }
            if (/[^A-Za-z0-9]/.test(value)) {
                score += 1;
            }

            // A very short password never looks strong, whatever it contains.
            if (value.length > 0 && value.length < 8) {
                score = Math.min(score, 1);
            }

            meter.dataset.score = String(score);

            if (label) {
                label.textContent = value.length === 0 ? labels[0] : labels[score];
            }
        };

        input.addEventListener('input', update);
        update();
    });
}

function bootAuthHelpers() {
    setupPasswordToggles();
    setupRoleFields();
    setupPasswordStrength();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootAuthHelpers);
} else {
    bootAuthHelpers();
}

```

## `resources/js/app.js`

```javascript
import './bootstrap'; // axios setup (window.axios)

// Bootstrap 5 JavaScript (dropdowns, modals, collapses...) + Popper
import * as bootstrap from 'bootstrap';

import Alpine from 'alpinejs';

// Chart.js 4 with every controller/scale registered ("/auto").
import Chart from 'chart.js/auto';

// Layout helpers: remember the desktop sidebar state (FR-31).
import './layout';

// Phase 3B: light/dark mode, themed charts, live countdowns, table tools and
// smooth page transitions.
import './theme';
import './charts';
import './countdown';
import './data-table';
import './page-transitions';
import './toast';

// Profile photo preview and "remove current photo" (FR-4).
import './profile-photo';

// Authentication helpers: password visibility + teacher subject field (FR-1 ... FR-3).
import './auth';

// Feed interactions: likes, comments, follows and "Load more" (FR-11, FR-13, FR-17).
import './feed';

// Live class room: embeds Jitsi Meet through the External API (FR-18, FR-22).
import './jitsi';

// Notification bell: unread badge + newest eight, refreshed by polling (FR-24)
// and "mark as read" without a reload (FR-25).
import './notifications';

// Live search suggestions under the navbar box (FR-14).
import './search';

// Global form polish: busy submit buttons and live character counters (Phase 3C).
import './forms';

// Live preview on the create / edit class form (Phase 3C).
import './classes';

// Make them available to plain <script> tags inside Blade views.
window.bootstrap = bootstrap;
window.Alpine = Alpine;
window.Chart = Chart;

Alpine.start();

```

## `resources/sass/app.scss`

```scss
// -----------------------------------------------------------------------------
// EduConnect stylesheet (Phase 3B premium dashboard design)
// -----------------------------------------------------------------------------
// Bootstrap 5 and Bootstrap Icons are pulled straight from node_modules and
// compiled by Vite (no Tailwind anywhere in this project). The tokens live in
// _variables.scss and are imported before Bootstrap so the framework itself
// picks up the brand palette and dark-mode surfaces.
// -----------------------------------------------------------------------------

@import 'variables';
@import 'bootstrap/scss/bootstrap';
@import 'bootstrap-icons/font/bootstrap-icons.css';

// -----------------------------------------------------------------------------
// Base
// -----------------------------------------------------------------------------

:root {
    --bs-body-bg: var(--edu-page-bg);
    --bs-body-color: var(--edu-text);
    --bs-border-color: var(--edu-border);
    --bs-secondary-color: var(--edu-muted);
    --bs-link-color-rgb: 79, 70, 229;
    --bs-link-hover-color-rgb: 55, 48, 163;
    --bs-focus-ring-color: rgba(79, 70, 229, 0.25);
}

[data-bs-theme='dark'] {
    --bs-body-bg: var(--edu-page-bg);
    --bs-body-color: var(--edu-text);
    --bs-border-color: var(--edu-border);
    --bs-secondary-color: var(--edu-muted);
    --bs-link-color-rgb: 165, 180, 252;
    --bs-link-hover-color-rgb: 199, 210, 254;
    --bs-emphasis-color: #f8fafc;
    --bs-heading-color: #f8fafc;
    --bs-tertiary-bg: #0f172a;
    --bs-secondary-bg: #172033;
}

html {
    scroll-behavior: smooth;
}

body {
    background-color: var(--edu-page-bg);
    color: var(--edu-text);
    font-feature-settings: 'cv02', 'cv03', 'cv04', 'cv11';
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    overflow-x: hidden;
}

// Bootstrap has no utility for this, but the dashboard flex children need it
// so long names and titles truncate instead of widening the grid.
.min-w-0 {
    min-width: 0;
}

::selection {
    background: rgba(79, 70, 229, 0.2);
}

h1, h2, h3, h4, h5, h6,
.h1, .h2, .h3, .h4, .h5, .h6 {
    color: var(--edu-text);
    letter-spacing: -0.015em;
}

h1, .h1 { font-size: clamp(1.6rem, 1.25rem + 1.2vw, 2rem); }
h2, .h2 { font-size: clamp(1.35rem, 1.15rem + 0.7vw, 1.6rem); }
h3, .h3 { font-size: 1.3rem; }
h4, .h4 { font-size: 1.15rem; }
h5, .h5 { font-size: 1rem; }
h6, .h6 { font-size: 0.9375rem; }

small, .small {
    font-size: 0.8125rem;
}

.text-muted {
    color: var(--edu-muted) !important;
}

.text-edu {
    color: var(--edu-primary) !important;
}

.bg-edu-soft {
    background: var(--edu-primary-soft) !important;
}

.edu-num,
.tabular-nums {
    font-variant-numeric: tabular-nums;
    font-feature-settings: 'tnum';
}

.edu-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.11em;
    text-transform: uppercase;
    color: var(--edu-primary);
}

.edu-muted {
    color: var(--edu-muted);
}

hr {
    border-color: var(--edu-border);
    opacity: 1;
}

// Focus rings: visible on every interactive element, never on click-only divs.
a:focus-visible,
button:focus-visible,
.btn:focus-visible,
.form-control:focus-visible,
.form-select:focus-visible,
.nav-link:focus-visible,
.dropdown-item:focus-visible,
[tabindex]:focus-visible {
    outline: 2px solid var(--edu-primary);
    outline-offset: 2px;
    box-shadow: var(--edu-ring);
}

// -----------------------------------------------------------------------------
// Buttons, badges, forms, tables, dropdowns, modals
// -----------------------------------------------------------------------------

.btn {
    --bs-btn-font-weight: 600;
    --bs-btn-padding-x: 0.95rem;
    --bs-btn-padding-y: 0.55rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    border-radius: 0.625rem;
    transition: transform var(--edu-transition), box-shadow var(--edu-transition),
        background-color var(--edu-transition), border-color var(--edu-transition),
        color var(--edu-transition);
}

.btn:active {
    transform: translateY(1px);
}

.btn-primary {
    --bs-btn-bg: var(--edu-primary);
    --bs-btn-border-color: var(--edu-primary);
    --bs-btn-hover-bg: var(--edu-primary-dark);
    --bs-btn-hover-border-color: var(--edu-primary-dark);
    --bs-btn-active-bg: var(--edu-primary-dark);
    --bs-btn-active-border-color: var(--edu-primary-dark);
    --bs-btn-disabled-bg: #a5b4fc;
    --bs-btn-disabled-border-color: #a5b4fc;
    box-shadow: 0 8px 18px -12px rgba(79, 70, 229, 0.8);
}

.btn-primary:hover {
    box-shadow: 0 12px 24px -14px rgba(79, 70, 229, 0.85);
}

.btn-outline-primary {
    --bs-btn-color: var(--edu-primary);
    --bs-btn-border-color: rgba(79, 70, 229, 0.35);
    --bs-btn-hover-bg: var(--edu-primary);
    --bs-btn-hover-border-color: var(--edu-primary);
    --bs-btn-active-bg: var(--edu-primary-dark);
    --bs-btn-active-border-color: var(--edu-primary-dark);
}

.btn-light {
    --bs-btn-bg: #ffffff;
    --bs-btn-border-color: var(--edu-border);
    --bs-btn-hover-bg: var(--edu-surface-soft);
    --bs-btn-hover-border-color: var(--edu-border-strong);
}

[data-bs-theme='dark'] .btn-light {
    --bs-btn-bg: var(--edu-surface-raised);
    --bs-btn-border-color: var(--edu-border);
    --bs-btn-color: var(--edu-text);
    --bs-btn-hover-bg: var(--edu-surface-muted);
    --bs-btn-hover-border-color: var(--edu-border-strong);
    --bs-btn-hover-color: var(--edu-text);
}

.btn-soft-primary {
    background: var(--edu-primary-soft);
    border-color: transparent;
    color: var(--edu-primary);
}

.btn-soft-primary:hover {
    background: rgba(79, 70, 229, 0.18);
    color: var(--edu-primary-dark);
}

.edu-icon-btn {
    width: 42px;
    height: 42px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    border: 1px solid transparent;
    background: transparent;
    color: var(--edu-muted);
    transition: background-color var(--edu-transition), color var(--edu-transition),
        border-color var(--edu-transition);
}

.edu-icon-btn:hover,
.edu-icon-btn:focus-visible {
    background: var(--edu-surface-muted);
    color: var(--edu-text);
}

.badge {
    font-weight: 600;
    letter-spacing: 0.01em;
    padding: 0.38em 0.65em;
    border-radius: 0.45rem;
}

.badge.rounded-pill {
    border-radius: 50rem !important;
}

.edu-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.3rem 0.65rem;
    border-radius: 999px;
    background: var(--edu-surface-muted);
    border: 1px solid var(--edu-border);
    color: var(--edu-muted-strong);
    font-size: 0.75rem;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
}

.form-control,
.form-select {
    border-radius: 0.625rem;
    border-color: var(--edu-border-strong);
    background-color: var(--edu-surface);
    color: var(--edu-text);
    padding-block: 0.55rem;
}

.form-control::placeholder {
    color: var(--edu-muted);
    opacity: 0.85;
}

.form-control:focus,
.form-select:focus {
    border-color: var(--edu-primary);
    box-shadow: var(--edu-ring);
    background-color: var(--edu-surface);
    color: var(--edu-text);
}

.form-label {
    font-weight: 600;
    font-size: 0.8125rem;
    color: var(--edu-text-soft);
    margin-bottom: 0.4rem;
}

.input-group-text {
    background: var(--edu-surface-soft);
    border-color: var(--edu-border-strong);
    color: var(--edu-muted);
    border-radius: 0.625rem;
}

.form-check-input:checked {
    background-color: var(--edu-primary);
    border-color: var(--edu-primary);
}

.form-check-input:focus {
    box-shadow: var(--edu-ring);
}

// --- Cards -------------------------------------------------------------------
.card {
    --bs-card-bg: var(--edu-surface);
    --bs-card-border-color: var(--edu-border);
    --bs-card-cap-bg: transparent;
    --bs-card-border-radius: var(--edu-radius);
    background-color: var(--edu-surface);
    color: var(--edu-text);
    border-radius: var(--edu-radius);
    border: 1px solid var(--edu-border);
    box-shadow: var(--edu-shadow-card);
}

.card-header {
    border-bottom-color: var(--edu-border);
    padding: 1rem 1.15rem;
    background: transparent;
}

.card-footer {
    border-top-color: var(--edu-border);
    background: transparent;
    padding: 0.9rem 1.15rem;
}

.card-body {
    padding: 1.15rem;
}

// The dashboard uses .edu-card for the hover lift. Keeping it opt-in avoids
// forms and static panels jumping while somebody is typing in them.
.edu-card,
.edu-stat-card,
.edu-chart-card {
    transition: transform var(--edu-transition), box-shadow var(--edu-transition),
        border-color var(--edu-transition);
}

.edu-card:hover,
.edu-stat-card:hover,
.edu-chart-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--edu-shadow-hover);
    border-color: var(--edu-border-strong);
}

// --- Tables ------------------------------------------------------------------
.table {
    --bs-table-bg: transparent;
    --bs-table-color: var(--edu-text);
    --bs-table-border-color: var(--edu-border);
    --bs-table-hover-bg: rgba(79, 70, 229, 0.045);
    --bs-table-striped-bg: rgba(148, 163, 184, 0.06);
    margin-bottom: 0;
}

.table > thead {
    background: var(--edu-surface-soft);
}

.table > :not(caption) > * > * {
    padding: 0.85rem 1rem;
    border-bottom-color: var(--edu-border);
    vertical-align: middle;
}

.table > thead th {
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.07em;
    text-transform: uppercase;
    color: var(--edu-muted);
    border-bottom: 1px solid var(--edu-border);
    white-space: nowrap;
}

.table > tbody tr:last-child > * {
    border-bottom-width: 0;
}

.table-hover > tbody > tr:hover > * {
    --bs-table-bg-state: rgba(79, 70, 229, 0.05);
}

[data-bs-theme='dark'] .table-hover > tbody > tr:hover > * {
    --bs-table-bg-state: rgba(129, 140, 248, 0.09);
}

// --- Dropdowns and modals ----------------------------------------------------
.dropdown-menu {
    --bs-dropdown-bg: var(--edu-surface-raised);
    --bs-dropdown-border-color: var(--edu-border);
    --bs-dropdown-link-color: var(--edu-text);
    --bs-dropdown-link-hover-bg: var(--edu-surface-muted);
    --bs-dropdown-link-active-bg: var(--edu-primary);
    --bs-dropdown-link-active-color: #ffffff;
    border-radius: 0.85rem;
    padding: 0.45rem;
    box-shadow: var(--edu-shadow-pop);
}

.dropdown-item {
    border-radius: 0.55rem;
    padding: 0.55rem 0.7rem;
    font-size: 0.875rem;
}

.dropdown-divider {
    border-color: var(--edu-border);
}

.modal-content {
    background: var(--edu-surface-raised);
    color: var(--edu-text);
    border: 1px solid var(--edu-border);
    border-radius: 1rem;
    box-shadow: var(--edu-shadow-pop);
}

.modal-header,
.modal-footer {
    border-color: var(--edu-border);
}

.list-group {
    --bs-list-group-bg: transparent;
    --bs-list-group-color: var(--edu-text);
    --bs-list-group-border-color: var(--edu-border);
    --bs-list-group-action-hover-bg: var(--edu-surface-soft);
    --bs-list-group-action-active-bg: var(--edu-surface-muted);
}

.alert {
    border-radius: 0.85rem;
    border-width: 1px;
    font-size: 0.875rem;
}

.progress {
    background: var(--edu-surface-muted);
    border-radius: 999px;
    height: 0.5rem;
}

.progress-bar {
    border-radius: 999px;
}

.edu-progress .progress {
    min-height: 6px;
}

// -----------------------------------------------------------------------------
// Avatars
// -----------------------------------------------------------------------------

.avatar {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 50%;
    background: var(--edu-surface-muted);
}

.avatar-sm {
    width: 32px;
    height: 32px;
    object-fit: cover;
    border-radius: 50%;
    background: var(--edu-surface-muted);
}

.avatar-lg {
    width: 96px;
    height: 96px;
    object-fit: cover;
    border-radius: 50%;
    background: var(--edu-surface-muted);
}

.avatar-xl {
    width: 128px;
    height: 128px;
    object-fit: cover;
    border-radius: 50%;
    background: var(--edu-surface-muted);
}

// Initials fallback when a user has not uploaded a photo.
.edu-avatar-initials {
    display: inline-grid;
    place-items: center;
    flex-shrink: 0;
    border-radius: 50%;
    background: var(--edu-primary-soft);
    color: var(--edu-primary);
    font-size: 0.9em;
    font-weight: 700;
    letter-spacing: 0.02em;
    line-height: 1;
    user-select: none;
}

.avatar-sm.edu-avatar-initials {
    font-size: 0.68rem;
}

.avatar-lg.edu-avatar-initials {
    font-size: 1.45rem;
}

.avatar-xl.edu-avatar-initials {
    font-size: 1.9rem;
}

.profile-photo-preview {
    width: 96px;
    height: 96px;
    object-fit: cover;
    border-radius: 50%;
    border: 1px solid var(--edu-border);
    background-color: var(--edu-surface-soft);
}

// -----------------------------------------------------------------------------
// App shell: sidebar + navbar
// -----------------------------------------------------------------------------

.edu-body {
    min-height: 100vh;
}

.edu-shell {
    display: flex;
    align-items: stretch;
    min-height: calc(100vh - var(--edu-navbar-height));
}

.edu-main {
    display: flex;
    flex-direction: column;
    flex: 1 1 auto;
    min-width: 0;
}

.edu-main-inner {
    flex: 1 1 auto;
    padding: 0 0 2.5rem;
}

.edu-page {
    animation: edu-page-in 320ms var(--edu-ease) both;
}

@keyframes edu-page-in {
    from {
        opacity: 0;
        transform: translateY(8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.edu-page.is-leaving {
    opacity: 0;
    transform: translateY(4px);
    transition: opacity 130ms ease, transform 130ms ease;
}

// --- Desktop sidebar ---------------------------------------------------------
.edu-sidebar {
    --bs-offcanvas-width: 292px;
    width: var(--edu-sidebar-width);
    flex: 0 0 auto;
    color: var(--edu-sidebar-text);
    background: linear-gradient(160deg, var(--edu-sidebar-from) 0%, var(--edu-sidebar-to) 100%);
    border-right: 1px solid rgba(255, 255, 255, 0.06);
    transition: width 180ms var(--edu-ease);
    z-index: 1030;
}

// On desktop the offcanvas is static, so it needs its own positioning context
// for the decorative ::before layer. On phones it is already fixed.
@media (min-width: 992px) {
    .edu-sidebar {
        position: relative;
    }
}

.edu-sidebar::before {
    content: '';
    position: absolute;
    inset: 0;
    pointer-events: none;
    background:
        radial-gradient(420px 260px at 10% -10%, rgba(124, 58, 237, 0.22), transparent 60%),
        radial-gradient(360px 240px at 110% 20%, rgba(79, 70, 229, 0.16), transparent 60%);
}

.edu-sidebar > * {
    position: relative;
    z-index: 1;
}

.edu-sidebar .offcanvas-body {
    padding: 1rem 0.85rem 1.1rem;
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.18) transparent;
}

.edu-sidebar .offcanvas-body::-webkit-scrollbar {
    width: 6px;
}

.edu-sidebar .offcanvas-body::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.18);
    border-radius: 99px;
}

.edu-sidebar-logo {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    padding: 0.35rem 0.5rem 1rem;
    color: #ffffff;
    text-decoration: none;
}

.edu-sidebar-logo:hover {
    color: #ffffff;
}

.edu-sidebar-logo-mark {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    flex: 0 0 42px;
    border-radius: 13px;
    background: linear-gradient(145deg, var(--edu-primary) 0%, var(--edu-secondary) 100%);
    box-shadow: 0 10px 22px -12px rgba(79, 70, 229, 0.9);
    color: #ffffff;
    font-size: 1.15rem;
}

.edu-sidebar-logo-text {
    line-height: 1.15;
}

.edu-sidebar-logo-text strong {
    display: block;
    font-size: 1.02rem;
    font-weight: 700;
    letter-spacing: -0.02em;
}

.edu-sidebar-logo-text span {
    display: block;
    font-size: 0.68rem;
    color: var(--edu-sidebar-heading);
    letter-spacing: 0.12em;
    text-transform: uppercase;
}

.edu-sidebar-section {
    margin: 0.9rem 0.5rem 0.35rem;
    font-size: 0.66rem;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--edu-sidebar-heading);
}

.edu-sidebar .nav-link {
    position: relative;
    display: flex;
    align-items: center;
    gap: 0.7rem;
    min-height: 44px;
    padding: 0.62rem 0.75rem;
    margin-bottom: 0.15rem;
    border-radius: 0.7rem;
    color: var(--edu-sidebar-text);
    font-size: 0.875rem;
    font-weight: 500;
    transition: background-color var(--edu-transition), color var(--edu-transition);
}

.edu-sidebar .nav-link i {
    font-size: 1.05rem;
    width: 1.25rem;
    text-align: center;
    flex: 0 0 1.25rem;
    color: var(--edu-sidebar-heading);
    transition: color var(--edu-transition);
}

.edu-sidebar .nav-link:hover,
.edu-sidebar .nav-link:focus-visible {
    background: var(--edu-sidebar-hover);
    color: #ffffff;
}

.edu-sidebar .nav-link:hover i,
.edu-sidebar .nav-link:focus-visible i {
    color: #c7d2fe;
}

.edu-sidebar .nav-link.active {
    background: var(--edu-sidebar-active);
    color: #ffffff;
    font-weight: 600;
}

.edu-sidebar .nav-link.active i {
    color: #a5b4fc;
}

.edu-sidebar .nav-link.active::before {
    content: '';
    position: absolute;
    left: -0.85rem;
    top: 50%;
    transform: translateY(-50%);
    width: 3px;
    height: 22px;
    border-radius: 0 3px 3px 0;
    background: linear-gradient(180deg, #a5b4fc, #7c3aed);
    box-shadow: 0 0 16px rgba(165, 180, 252, 0.7);
}

.edu-sidebar .nav-link .badge {
    margin-left: auto;
    font-size: 0.65rem;
}

.edu-sidebar-divider {
    border-top: 1px solid var(--edu-sidebar-border);
    margin: 0.9rem 0.5rem;
}

.edu-sidebar-user {
    margin-top: auto;
    padding: 0.85rem;
    border-radius: 0.9rem;
    background: rgba(255, 255, 255, 0.055);
    border: 1px solid var(--edu-sidebar-border);
}

.edu-sidebar-user-name {
    color: #ffffff;
    font-weight: 600;
    font-size: 0.875rem;
    line-height: 1.2;
}

.edu-sidebar-user-role {
    color: var(--edu-sidebar-heading);
    font-size: 0.72rem;
    text-transform: capitalize;
}

.edu-sidebar-user .avatar,
.edu-sidebar-user .avatar-sm {
    border: 1px solid rgba(255, 255, 255, 0.2);
}

// The offcanvas header on phones must match the dark column.
.edu-sidebar .offcanvas-header {
    border-bottom: 1px solid var(--edu-sidebar-border) !important;
    color: #ffffff;
}

.edu-sidebar .offcanvas-title {
    color: #ffffff;
}

.edu-sidebar .btn-close {
    filter: invert(1) grayscale(1) brightness(2);
}

// Collapsed desktop sidebar: icon-only, still readable through tooltips.
@media (min-width: 992px) {
    html.edu-sidebar-collapsed .edu-sidebar {
        width: var(--edu-sidebar-collapsed-width);
    }

    html.edu-sidebar-collapsed .edu-sidebar .edu-sidebar-label,
    html.edu-sidebar-collapsed .edu-sidebar .edu-sidebar-section,
    html.edu-sidebar-collapsed .edu-sidebar .edu-sidebar-logo-text,
    html.edu-sidebar-collapsed .edu-sidebar .edu-sidebar-user-text,
    html.edu-sidebar-collapsed .edu-sidebar .edu-sidebar-user .badge,
    html.edu-sidebar-collapsed .edu-sidebar .nav-link .badge {
        display: none !important;
    }

    html.edu-sidebar-collapsed .edu-sidebar .nav-link {
        justify-content: center;
        padding-inline: 0.55rem;
    }

    html.edu-sidebar-collapsed .edu-sidebar .nav-link i {
        margin: 0;
    }

    html.edu-sidebar-collapsed .edu-sidebar .nav-link.active::before {
        left: -0.55rem;
    }

    html.edu-sidebar-collapsed .edu-sidebar .edu-sidebar-logo {
        justify-content: center;
        padding-inline: 0;
    }

    html.edu-sidebar-collapsed .edu-sidebar .edu-sidebar-user {
        padding: 0.6rem;
        text-align: center;
    }

    html.edu-sidebar-collapsed .edu-sidebar .edu-sidebar-user .d-flex {
        justify-content: center;
    }

    html.edu-sidebar-collapsed .edu-sidebar .edu-sidebar-footer-link {
        justify-content: center;
    }
}

// --- Top navbar --------------------------------------------------------------
.edu-navbar {
    position: sticky;
    top: 0;
    z-index: 1040;
    min-height: var(--edu-navbar-height);
    padding: 0.55rem 0;
    background: color-mix(in srgb, var(--edu-surface) 82%, transparent);
    border-bottom: 1px solid var(--edu-border);
    backdrop-filter: saturate(180%) blur(16px);
    -webkit-backdrop-filter: saturate(180%) blur(16px);
}

@supports not (backdrop-filter: blur(1px)) {
    .edu-navbar {
        background: var(--edu-surface);
    }
}

.edu-navbar .navbar-brand {
    color: var(--edu-text);
    font-weight: 700;
    letter-spacing: -0.02em;
}

.edu-navbar-title {
    display: block;
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--edu-text);
    line-height: 1.2;
    max-width: 22rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.edu-navbar .breadcrumb {
    --bs-breadcrumb-divider-color: var(--edu-muted);
    --bs-breadcrumb-item-padding-x: 0.35rem;
    margin: 0;
    font-size: 0.72rem;
}

.edu-navbar .breadcrumb a {
    color: var(--edu-muted);
    text-decoration: none;
}

.edu-navbar .breadcrumb a:hover {
    color: var(--edu-primary);
}

.edu-navbar .breadcrumb-item.active {
    color: var(--edu-muted-strong);
}

.edu-search {
    width: 260px;
}

.edu-search .form-control {
    background: var(--edu-surface-soft);
    border-color: transparent;
    font-size: 0.85rem;
}

.edu-search .form-control:focus {
    background: var(--edu-surface);
    border-color: var(--edu-border-strong);
}

@media (min-width: 992px) {
    .edu-search {
        width: 320px;
    }
}

.edu-navbar .nav-link {
    color: var(--edu-text);
}

.edu-navbar .dropdown-toggle::after {
    display: none;
}

// Bootstrap sets `.navbar-nav .dropdown-menu` to `position: static` outside a
// `navbar-expand-*` container, so the user/notification menus would expand the
// bar and push the page down. The top bar is always horizontal here, so keep
// its menus floating above the content instead.
.edu-navbar .navbar-nav .dropdown-menu {
    position: absolute;
}

.edu-navbar-avatar {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.25rem 0.45rem 0.25rem 0.3rem;
    border-radius: 999px;
    border: 1px solid var(--edu-border);
    background: var(--edu-surface);
    color: var(--edu-text);
    text-decoration: none;
    transition: box-shadow var(--edu-transition), border-color var(--edu-transition);
}

.edu-navbar-avatar:hover {
    border-color: var(--edu-border-strong);
    box-shadow: var(--edu-shadow-xs);
    color: var(--edu-text);
}

.notification-menu {
    width: min(380px, 92vw);
    max-height: 72vh;
    overflow-y: auto;
}

.notification-unread {
    background-color: var(--edu-primary-soft);
    border-left: 3px solid var(--edu-primary);
}

.edu-time {
    font-size: 0.72rem;
}

// Footer
.edu-footer {
    border-top: 1px solid var(--edu-border);
    background: var(--edu-surface);
    color: var(--edu-muted);
    padding: 1.1rem 0;
    font-size: 0.8125rem;
}

// -----------------------------------------------------------------------------
// Dashboard shared components
// -----------------------------------------------------------------------------

.edu-dashboard {
    padding-bottom: 1rem;
}

.edu-greeting {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.4rem;
}

.edu-greeting h1 {
    font-size: clamp(1.5rem, 1.2rem + 1.2vw, 2rem);
    font-weight: 800;
    letter-spacing: -0.03em;
    margin: 0.15rem 0 0.2rem;
}

.edu-greeting p {
    margin: 0;
    color: var(--edu-muted);
    font-size: 0.875rem;
}

.edu-quick-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

// --- Stat cards --------------------------------------------------------------
.edu-stat-card {
    position: relative;
    overflow: hidden;
}

.edu-stat-card .card-body {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
    min-height: 142px;
}

.edu-stat-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
}

.edu-stat-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 46px;
    height: 46px;
    flex: 0 0 46px;
    border-radius: 14px;
    font-size: 1.2rem;
    line-height: 1;
}

.edu-stat-icon.bg-primary {
    background: rgba(79, 70, 229, 0.12) !important;
    color: #4f46e5 !important;
}

.edu-stat-icon.bg-success {
    background: rgba(16, 185, 129, 0.13) !important;
    color: #059669 !important;
}

.edu-stat-icon.bg-info {
    background: rgba(14, 165, 233, 0.13) !important;
    color: #0284c7 !important;
}

.edu-stat-icon.bg-warning {
    background: rgba(245, 158, 11, 0.15) !important;
    color: #b45309 !important;
}

.edu-stat-icon.bg-danger {
    background: rgba(239, 68, 68, 0.13) !important;
    color: #dc2626 !important;
}

.edu-stat-icon.bg-secondary {
    background: rgba(124, 58, 237, 0.14) !important;
    color: #6d28d9 !important;
}

[data-bs-theme='dark'] .edu-stat-icon.bg-primary { color: #a5b4fc !important; }
[data-bs-theme='dark'] .edu-stat-icon.bg-success { color: #6ee7b7 !important; }
[data-bs-theme='dark'] .edu-stat-icon.bg-info { color: #7dd3fc !important; }
[data-bs-theme='dark'] .edu-stat-icon.bg-warning { color: #fcd34d !important; }
[data-bs-theme='dark'] .edu-stat-icon.bg-danger { color: #fca5a5 !important; }
[data-bs-theme='dark'] .edu-stat-icon.bg-secondary { color: #c4b5fd !important; }

.edu-stat-value {
    font-size: clamp(1.45rem, 1.25rem + 0.6vw, 1.85rem);
    font-weight: 800;
    letter-spacing: -0.03em;
    line-height: 1.1;
    font-variant-numeric: tabular-nums;
    color: var(--edu-text);
}

.edu-stat-label {
    color: var(--edu-muted);
    font-size: 0.8125rem;
    font-weight: 500;
}

.edu-stat-hint {
    color: var(--edu-muted);
    font-size: 0.72rem;
}

.edu-trend {
    display: inline-flex;
    align-items: center;
    gap: 0.18rem;
    font-size: 0.72rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.edu-trend.is-up { color: #059669; }
.edu-trend.is-down { color: #dc2626; }
.edu-trend.is-flat { color: var(--edu-muted); }

[data-bs-theme='dark'] .edu-trend.is-up { color: #6ee7b7; }
[data-bs-theme='dark'] .edu-trend.is-down { color: #fca5a5; }

.edu-sparkline {
    width: 96px;
    height: 34px;
    overflow: visible;
    opacity: 0.95;
}

.edu-sparkline polyline {
    fill: none;
    stroke-width: 2.2;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.edu-sparkline .sparkline-fill {
    stroke: none;
    opacity: 0.16;
}

// --- Chart cards -------------------------------------------------------------
.edu-chart-card .card-header {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.65rem;
}

.edu-chart-card .card-title {
    font-size: 0.95rem;
    font-weight: 700;
    margin: 0;
}

.edu-chart-card .card-subtitle {
    color: var(--edu-muted);
    font-size: 0.75rem;
    margin-top: 0.15rem;
}

.edu-chart {
    position: relative;
    width: 100%;
}

.edu-chart canvas {
    max-width: 100%;
}

.edu-chart-skeleton {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: flex-end;
    gap: 0.5rem;
    padding: 0.5rem 0;
    z-index: 2;
    background: var(--edu-surface);
    border-radius: 0.75rem;
}

.edu-chart-skeleton[hidden] {
    display: none !important;
}

.edu-chart-skeleton span {
    flex: 1 1 0;
    border-radius: 0.35rem;
    background: linear-gradient(
        90deg,
        var(--edu-surface-muted) 0%,
        var(--edu-border) 45%,
        var(--edu-surface-muted) 90%
    );
    background-size: 200% 100%;
    animation: edu-shimmer 1.2s linear infinite;
}

.edu-chart-skeleton span:nth-child(1) { height: 42%; }
.edu-chart-skeleton span:nth-child(2) { height: 66%; }
.edu-chart-skeleton span:nth-child(3) { height: 54%; }
.edu-chart-skeleton span:nth-child(4) { height: 82%; }
.edu-chart-skeleton span:nth-child(5) { height: 60%; }
.edu-chart-skeleton span:nth-child(6) { height: 92%; }
.edu-chart-skeleton span:nth-child(7) { height: 70%; }
.edu-chart-skeleton span:nth-child(8) { height: 48%; }

@keyframes edu-shimmer {
    from { background-position: 200% 0; }
    to { background-position: -200% 0; }
}

.edu-chart-range {
    width: auto;
    min-width: 118px;
    font-size: 0.78rem;
    padding: 0.35rem 1.8rem 0.35rem 0.6rem;
    border-radius: 0.55rem;
    background-color: var(--edu-surface-soft);
    border-color: var(--edu-border);
}

.edu-chart-total {
    font-variant-numeric: tabular-nums;
}

// --- Hero cards --------------------------------------------------------------
.edu-hero {
    position: relative;
    overflow: hidden;
    border-radius: var(--edu-radius-lg);
    border: 1px solid rgba(255, 255, 255, 0.08);
    background:
        radial-gradient(900px 360px at 90% -40%, rgba(124, 58, 237, 0.55), transparent 60%),
        linear-gradient(135deg, #1e1b4b 0%, #312e81 45%, #4f46e5 100%);
    color: #ffffff;
    box-shadow: 0 26px 60px -30px rgba(49, 46, 129, 0.85);
    padding: clamp(1.25rem, 1rem + 1.2vw, 2rem);
}

.edu-hero::after {
    content: '';
    position: absolute;
    right: -80px;
    bottom: -120px;
    width: 320px;
    height: 320px;
    border-radius: 50%;
    border: 44px solid rgba(255, 255, 255, 0.05);
    pointer-events: none;
}

.edu-hero > * {
    position: relative;
    z-index: 1;
}

.edu-hero h1,
.edu-hero h2 {
    color: #ffffff;
    letter-spacing: -0.03em;
}

.edu-hero .edu-hero-meta {
    color: rgba(255, 255, 255, 0.72);
    font-size: 0.875rem;
}

.edu-hero .edu-hero-label {
    color: #c7d2fe;
}

.edu-hero .btn-light {
    --bs-btn-bg: #ffffff;
    --bs-btn-color: #312e81;
    --bs-btn-hover-bg: #eef2ff;
    --bs-btn-hover-color: #1e1b4b;
    border-color: #ffffff;
}

.edu-hero .btn-outline-light {
    --bs-btn-color: #ffffff;
    --bs-btn-border-color: rgba(255, 255, 255, 0.45);
    --bs-btn-hover-bg: rgba(255, 255, 255, 0.14);
    --bs-btn-hover-border-color: #ffffff;
}

.edu-hero .edu-chip {
    background: rgba(255, 255, 255, 0.12);
    border-color: rgba(255, 255, 255, 0.18);
    color: #e0e7ff;
}

.edu-hero-live {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.35rem 0.7rem;
    border-radius: 999px;
    background: rgba(239, 68, 68, 0.18);
    border: 1px solid rgba(248, 113, 113, 0.4);
    color: #fecaca;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}

.edu-live-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #ef4444;
    box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.25);
    animation: edu-pulse 1.6s ease-in-out infinite;
}

@keyframes edu-pulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(0.82); opacity: 0.65; }
}

// --- Countdown ---------------------------------------------------------------
.edu-countdown {
    display: inline-flex;
    align-items: stretch;
    gap: 0.45rem;
}

.edu-countdown-unit {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-width: 58px;
    padding: 0.4rem 0.55rem;
    border-radius: 0.65rem;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.14);
}

.edu-countdown-value {
    font-size: 1.2rem;
    font-weight: 800;
    line-height: 1;
    font-variant-numeric: tabular-nums;
    color: #ffffff;
}

.edu-countdown-label {
    font-size: 0.58rem;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: rgba(255, 255, 255, 0.66);
    margin-top: 0.2rem;
}

.edu-countdown.is-compact .edu-countdown-unit {
    min-width: 46px;
    padding: 0.3rem 0.4rem;
}

.edu-countdown.is-compact .edu-countdown-value {
    font-size: 1rem;
}

// --- Avatar stack ------------------------------------------------------------
.edu-avatar-stack {
    display: inline-flex;
    align-items: center;
    padding-left: 0.35rem;
}

.edu-avatar-stack .avatar,
.edu-avatar-stack .avatar-sm {
    border: 2px solid var(--edu-hero-avatar-border, #312e81);
    margin-left: -0.45rem;
    background: var(--edu-surface-muted);
}

.edu-avatar-stack .edu-avatar-stack-more {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    margin-left: -0.45rem;
    background: rgba(255, 255, 255, 0.16);
    border: 2px solid #312e81;
    color: #ffffff;
    font-size: 0.68rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
}

// Outside hero cards the stack needs a light border.
.edu-avatar-stack.on-light .avatar,
.edu-avatar-stack.on-light .avatar-sm {
    border-color: var(--edu-surface);
}

.edu-avatar-stack.on-light .edu-avatar-stack-more {
    background: var(--edu-surface-muted);
    border-color: var(--edu-surface);
    color: var(--edu-muted-strong);
}

// --- Activity timeline -------------------------------------------------------
.edu-activity {
    position: relative;
    display: flex;
    gap: 0.85rem;
    padding: 0.8rem 0;
}

.edu-activity + .edu-activity {
    border-top: 1px solid var(--edu-border);
}

.edu-activity-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    flex: 0 0 38px;
    border-radius: 12px;
    font-size: 1rem;
}

.edu-activity-icon.bg-primary { background: rgba(79, 70, 229, 0.12); color: #4f46e5; }
.edu-activity-icon.bg-success { background: rgba(16, 185, 129, 0.13); color: #059669; }
.edu-activity-icon.bg-info { background: rgba(14, 165, 233, 0.13); color: #0284c7; }
.edu-activity-icon.bg-warning { background: rgba(245, 158, 11, 0.15); color: #b45309; }
.edu-activity-icon.bg-danger { background: rgba(239, 68, 68, 0.13); color: #dc2626; }

.edu-activity-text {
    font-size: 0.85rem;
    color: var(--edu-text-soft);
    line-height: 1.4;
}

.edu-activity-excerpt {
    color: var(--edu-muted);
    font-size: 0.78rem;
    margin-top: 0.15rem;
}

.edu-activity-time {
    color: var(--edu-muted);
    font-size: 0.72rem;
    margin-top: 0.2rem;
}

// --- Data table wrapper ------------------------------------------------------
.edu-data-table .card-header {
    align-items: center;
}

.edu-table-tools {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.edu-table-search {
    position: relative;
}

.edu-table-search i {
    position: absolute;
    left: 0.65rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--edu-muted);
    font-size: 0.8rem;
    pointer-events: none;
}

.edu-table-search .form-control {
    min-width: 190px;
    padding-left: 2rem;
    font-size: 0.8rem;
    background: var(--edu-surface-soft);
    border-color: transparent;
}

.edu-table-search .form-control:focus {
    background: var(--edu-surface);
    border-color: var(--edu-border-strong);
}

.edu-sortable {
    cursor: pointer;
    user-select: none;
    position: relative;
}

.edu-sortable:hover {
    color: var(--edu-text);
}

.edu-sortable::after {
    content: '\F282';
    font-family: 'bootstrap-icons';
    font-size: 0.62rem;
    margin-left: 0.3rem;
    opacity: 0.35;
}

.edu-sortable[aria-sort='ascending']::after {
    content: '\F286';
    opacity: 0.9;
}

.edu-sortable[aria-sort='descending']::after {
    content: '\F284';
    opacity: 0.9;
}

.edu-table-empty {
    padding: 1.5rem 1rem;
    text-align: center;
    color: var(--edu-muted);
    font-size: 0.85rem;
}

.edu-table-count {
    color: var(--edu-muted);
    font-size: 0.75rem;
    font-variant-numeric: tabular-nums;
}

// --- Schedule strip ----------------------------------------------------------
.edu-schedule {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 0.45rem;
}

.edu-schedule-day {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
    padding: 0.6rem 0.25rem;
    border-radius: 0.8rem;
    border: 1px solid var(--edu-border);
    background: var(--edu-surface-soft);
    min-height: 86px;
    text-align: center;
}

.edu-schedule-day.is-today {
    border-color: rgba(79, 70, 229, 0.4);
    background: var(--edu-primary-soft);
}

.edu-schedule-weekday {
    font-size: 0.62rem;
    font-weight: 700;
    letter-spacing: 0.09em;
    text-transform: uppercase;
    color: var(--edu-muted);
}

.edu-schedule-date {
    font-size: 0.95rem;
    font-weight: 800;
    font-variant-numeric: tabular-nums;
    color: var(--edu-text);
    line-height: 1.1;
}

.edu-schedule-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 22px;
    height: 22px;
    padding: 0 0.3rem;
    border-radius: 999px;
    background: var(--edu-surface-muted);
    color: var(--edu-muted-strong);
    font-size: 0.68rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
}

.edu-schedule-day.has-classes .edu-schedule-count {
    background: var(--edu-primary);
    color: #ffffff;
}

@media (max-width: 575.98px) {
    .edu-schedule {
        gap: 0.3rem;
    }

    .edu-schedule-day {
        min-height: 76px;
        padding: 0.45rem 0.15rem;
    }

    .edu-schedule-date {
        font-size: 0.85rem;
    }
}

// --- Panels / lists ----------------------------------------------------------
.edu-list {
    display: flex;
    flex-direction: column;
}

.edu-list-row {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.8rem 0;
}

.edu-list-row + .edu-list-row {
    border-top: 1px solid var(--edu-border);
}

.edu-list-row:first-child {
    padding-top: 0.2rem;
}

.edu-list-row:last-child {
    padding-bottom: 0.2rem;
}

.edu-list-title {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--edu-text);
    text-decoration: none;
    display: block;
}

.edu-list-title:hover {
    color: var(--edu-primary);
}

.edu-list-meta {
    color: var(--edu-muted);
    font-size: 0.74rem;
}

.edu-list-excerpt {
    color: var(--edu-muted-strong);
    font-size: 0.8rem;
    margin-top: 0.15rem;
}

.edu-report-actions {
    display: flex;
    gap: 0.35rem;
    flex-shrink: 0;
}

.edu-report-actions .btn {
    width: 34px;
    height: 34px;
    padding: 0;
}

// --- Feed preview ------------------------------------------------------------
.edu-feed-item {
    display: flex;
    gap: 0.75rem;
    padding: 0.75rem 0;
}

.edu-feed-item + .edu-feed-item {
    border-top: 1px solid var(--edu-border);
}

.edu-feed-body {
    font-size: 0.82rem;
    color: var(--edu-text-soft);
    margin: 0.2rem 0 0.25rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

// --- Feed sidebar widgets ----------------------------------------------------
.edu-widget-user {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    padding: 0.7rem 0;
}

.edu-widget-user + .edu-widget-user {
    border-top: 1px solid var(--edu-border);
}

.edu-widget-user .edu-list-title {
    font-size: 0.84rem;
}

.edu-widget-user .btn {
    white-space: nowrap;
}

.edu-trending-row {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    padding: 0.65rem 0;
    color: inherit;
    text-decoration: none;
}

.edu-trending-row + .edu-trending-row {
    border-top: 1px solid var(--edu-border);
}

.edu-trending-row:hover .edu-list-title {
    color: var(--edu-primary);
}

.edu-trending-rank {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border-radius: 12px;
    background: var(--edu-primary-soft);
    color: var(--edu-primary);
    font-weight: 700;
    flex-shrink: 0;
}

.edu-post-image {
    width: 100%;
    max-height: 520px;
    object-fit: cover;
    background: var(--edu-surface-muted);
}

.edu-post-comments .avatar,
.edu-post-comments .avatar-sm {
    width: 34px;
    height: 34px;
    font-size: 0.8rem;
}

// --- Empty states ------------------------------------------------------------
.edu-empty-illustration {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 76px;
    height: 76px;
    border-radius: 24px;
    background: var(--edu-primary-soft);
    color: var(--edu-primary);
    font-size: 1.9rem;
    margin-bottom: 0.9rem;
    position: relative;
}

.edu-empty-illustration::after {
    content: '';
    position: absolute;
    inset: -8px;
    border-radius: 30px;
    border: 1px dashed rgba(79, 70, 229, 0.22);
}

// --- Skeletons ---------------------------------------------------------------
.edu-skeleton {
    display: block;
    border-radius: 0.5rem;
    background: linear-gradient(
        90deg,
        var(--edu-surface-muted) 0%,
        var(--edu-border) 45%,
        var(--edu-surface-muted) 90%
    );
    background-size: 200% 100%;
    animation: edu-shimmer 1.2s linear infinite;
}

// -----------------------------------------------------------------------------
// Legacy application pages (feed, auth, live room) — kept intentionally
// -----------------------------------------------------------------------------

.post-body {
    white-space: pre-wrap;
    word-break: break-word;
}

.breadcrumb {
    --bs-breadcrumb-divider-color: var(--edu-muted);
}

[x-cloak] {
    display: none !important;
}

.jitsi-frame {
    position: relative;
    width: 100%;
    height: calc(100vh - 340px);
    min-height: 420px;
    border-radius: 0.75rem;
    overflow: hidden;
    background-color: #1c1c1c;
}

.jitsi-frame iframe {
    width: 100% !important;
    height: 100% !important;
    border: 0;
}

@media (max-width: 991.98px) {
    .jitsi-frame {
        height: calc(100vh - 260px);
        min-height: 360px;
        border-radius: 0.5rem;
    }
}

.edu-auth-body {
    background-color: var(--edu-surface);
    color: var(--edu-text);
}

.edu-auth-brand {
    position: relative;
    overflow: hidden;
    color: #fff;
    background: linear-gradient(145deg, #312e81 0%, #4338ca 45%, #4f46e5 100%);
}

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

.password-toggle {
    min-width: 48px;
}

@media (max-width: 991.98px) {
    .edu-auth-body {
        background-color: var(--edu-page-bg);
    }
}

// -----------------------------------------------------------------------------
// Responsive and touch refinements
// -----------------------------------------------------------------------------

@media (max-width: 991.98px) {
    .edu-main-inner {
        padding-top: 1.1rem;
    }

    .edu-sidebar {
        --bs-offcanvas-width: min(88vw, 312px);
    }
}

@media (max-width: 575.98px) {
    .btn,
    .form-control,
    .form-select,
    .nav-link,
    .dropdown-item {
        min-height: 44px;
    }

    .btn-sm {
        min-height: 44px;
    }

    .edu-icon-btn {
        width: 44px;
        height: 44px;
    }

    .edu-greeting {
        align-items: flex-start;
    }

    .edu-quick-actions {
        width: 100%;
    }

    .edu-quick-actions .btn {
        flex: 1 1 auto;
    }

    .edu-stat-card .card-body {
        min-height: 128px;
    }

    .edu-table-search .form-control {
        min-width: 0;
        width: 100%;
    }

    .edu-table-tools {
        width: 100%;
    }

    .edu-table-tools .edu-table-search {
        flex: 1 1 auto;
    }

    .edu-countdown {
        gap: 0.3rem;
    }

    .edu-countdown-unit {
        min-width: 48px;
        padding: 0.35rem 0.35rem;
    }

    .edu-countdown-value {
        font-size: 1.05rem;
    }
}

// Larger screens get a little more breathing room.
@media (min-width: 1920px) {
    .edu-main-inner {
        padding-top: 2rem;
    }

    .edu-sidebar {
        --edu-sidebar-width: 284px;
    }

    .edu-stat-card .card-body {
        min-height: 152px;
    }
}

// -----------------------------------------------------------------------------
// Phase 3C — component library additions
// -----------------------------------------------------------------------------
// Everything below is built from the tokens in _variables.scss: same spacing
// steps, same radii, same transitions, same z-index ladder.

// Comfortable reading width for long prose (about 70 characters).
.edu-measure {
    max-width: var(--edu-measure);
}

// --- Button loading state ----------------------------------------------------
// A form button that is waiting for the server: spinner in place of the label.
.btn.is-loading {
    position: relative;
    color: transparent !important;
    pointer-events: none;

    > * {
        visibility: hidden;
    }

    &::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 1rem;
        height: 1rem;
        margin: -0.5rem 0 0 -0.5rem;
        border: 2px solid currentColor;
        border-right-color: transparent;
        border-radius: 50%;
        color: #fff;
        animation: edu-spin 0.6s linear infinite;
    }
}

.btn-outline-primary.is-loading::after,
.btn-outline-secondary.is-loading::after,
.btn-light.is-loading::after {
    color: var(--edu-primary);
}

@keyframes edu-spin {
    to {
        transform: rotate(360deg);
    }
}

// --- Toasts ------------------------------------------------------------------
// Rendered by resources/js/toast.js from the data attributes of <x-flash />.
.edu-toast-stack {
    position: fixed;
    right: var(--edu-space-4);
    bottom: var(--edu-space-4);
    z-index: var(--edu-z-toast);
    display: flex;
    flex-direction: column;
    gap: var(--edu-space-2);
    width: min(360px, calc(100vw - 2 * var(--edu-space-4)));
    pointer-events: none;
}

.edu-toast {
    display: flex;
    align-items: flex-start;
    gap: var(--edu-space-2);
    padding: var(--edu-space-3) var(--edu-space-4);
    border-radius: var(--edu-radius-sm);
    border: 1px solid var(--edu-border);
    background: var(--edu-surface);
    color: var(--edu-text);
    box-shadow: var(--edu-shadow-pop);
    pointer-events: auto;
    animation: edu-toast-in 200ms var(--edu-ease);

    .btn-close {
        padding: 0.35rem;
        margin-left: auto;
    }
}

.edu-toast.is-success {
    border-left: 4px solid var(--edu-success);
}

.edu-toast.is-error {
    border-left: 4px solid var(--edu-danger);
}

.edu-toast.is-info {
    border-left: 4px solid var(--edu-info);
}

@keyframes edu-toast-in {
    from {
        opacity: 0;
        transform: translateY(8px);
    }
}

// --- Role cards on the register page ----------------------------------------
.edu-role-card {
    display: flex;
    align-items: flex-start;
    gap: var(--edu-space-3);
    padding: var(--edu-space-3) var(--edu-space-4);
    border: 1px solid var(--edu-border);
    border-radius: var(--edu-radius-sm);
    background: var(--edu-surface-soft);
    cursor: pointer;
    transition: border-color var(--edu-transition), background-color var(--edu-transition),
        box-shadow var(--edu-transition);

    &:hover {
        border-color: var(--edu-border-strong);
    }

    // The radio itself is hidden: the whole card is the hit area (44px+ tall).
    input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .edu-role-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        flex: 0 0 40px;
        border-radius: var(--edu-radius-xs);
        background: var(--edu-primary-soft);
        color: var(--edu-primary);
        font-size: 1.15rem;
    }

    // Selected state, driven by :has() plus a JS class for older browsers.
    &:has(input:checked),
    &.is-selected {
        border-color: var(--edu-primary);
        background: var(--edu-surface);
        box-shadow: 0 0 0 1px var(--edu-primary);
    }

    &:has(input:focus-visible),
    &.is-selected:focus-within {
        outline: 2px solid var(--edu-primary);
        outline-offset: 2px;
    }
}

// --- Password strength meter -------------------------------------------------
.edu-strength {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
    margin-top: var(--edu-space-2);
}

.edu-strength-track {
    display: flex;
    gap: 4px;
}

.edu-strength-seg {
    flex: 1;
    height: 6px;
    border-radius: 999px;
    background: var(--edu-surface-muted);
    transition: background-color var(--edu-transition);
}

.edu-strength[data-score='1'] .edu-strength-seg:nth-child(-n + 1),
.edu-strength[data-score='2'] .edu-strength-seg:nth-child(-n + 2) {
    background: var(--edu-danger);
}

.edu-strength[data-score='3'] .edu-strength-seg:nth-child(-n + 3) {
    background: var(--edu-warning);
}

.edu-strength[data-score='4'] .edu-strength-seg {
    background: var(--edu-success);
}

// --- Mobile bottom navigation ------------------------------------------------
.edu-mobile-nav {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: var(--edu-z-mobilenav);
    display: flex;
    align-items: stretch;
    justify-content: space-around;
    border-top: 1px solid var(--edu-border);
    background: var(--edu-surface);
    // Respect the iPhone home indicator area.
    padding-bottom: env(safe-area-inset-bottom, 0);
    box-shadow: 0 -6px 24px -18px rgba(15, 23, 42, 0.4);
}

.edu-mobile-nav-item {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 2px;
    // 44px minimum touch target.
    min-width: 56px;
    min-height: 56px;
    flex: 1;
    padding: 0.4rem 0.25rem;
    font-size: var(--edu-fs-xs);
    font-weight: 600;
    color: var(--edu-muted);
    text-decoration: none;

    i {
        font-size: 1.25rem;
    }

    &.active {
        color: var(--edu-primary);
    }

    &:hover {
        color: var(--edu-primary);
    }
}

// The badge sits on the icon, not on the label.
.edu-mobile-nav-badge {
    position: absolute;
    top: 4px;
    left: 50%;
    margin-left: 6px;
    font-size: 0.625rem;
    padding: 0.15em 0.4em;
}

// --- Floating "new post" button ---------------------------------------------
.edu-fab {
    position: fixed;
    right: var(--edu-space-4);
    bottom: calc(72px + env(safe-area-inset-bottom, 0));
    z-index: var(--edu-z-fab);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 56px;
    height: 56px;
    border: 0;
    border-radius: 50%;
    background: var(--edu-primary);
    color: #fff;
    font-size: 1.4rem;
    box-shadow: var(--edu-shadow-pop);
    transition: transform var(--edu-transition), background-color var(--edu-transition);

    &:hover,
    &:focus-visible {
        background: var(--edu-primary-dark);
        color: #fff;
    }

    &:active {
        transform: scale(0.94);
    }
}

@media (min-width: 992px) {
    .edu-fab {
        bottom: var(--edu-space-5);
        right: var(--edu-space-5);
    }
}

// --- Profile cover banner ----------------------------------------------------
.edu-cover {
    position: relative;
    height: 132px;
    border-radius: var(--edu-radius) var(--edu-radius) 0 0;
    background: linear-gradient(120deg, #312e81 0%, #4f46e5 55%, #7c3aed 100%);
    overflow: hidden;

    &::after {
        content: '';
        position: absolute;
        width: 260px;
        height: 260px;
        right: -80px;
        top: -140px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.1);
    }
}

.edu-cover-avatar {
    margin-top: -48px;
}

// --- Highlighted search match ------------------------------------------------
// The surrounding text keeps its colour; only the matched part is marked, so
// the highlight never hides information and always has contrast.
.edu-mark {
    padding: 0 0.15em;
    border-radius: var(--edu-radius-xs);
    background: rgba(245, 158, 11, 0.28);
    color: inherit;
    font-weight: 600;
}

[data-bs-theme='dark'] .edu-mark {
    background: rgba(245, 158, 11, 0.32);
}

// --- Filter chips ------------------------------------------------------------
.edu-filter-chip {
    display: inline-flex;
    align-items: center;
    gap: var(--edu-space-1);
    padding: 0.35rem 0.75rem;
    min-height: 36px;
    border: 1px solid var(--edu-border);
    border-radius: 999px;
    background: var(--edu-surface);
    color: var(--edu-text-soft);
    font-size: var(--edu-fs-small);
    font-weight: 600;
    text-decoration: none;
    transition: border-color var(--edu-transition), background-color var(--edu-transition),
        color var(--edu-transition);

    &:hover {
        border-color: var(--edu-primary);
        color: var(--edu-primary);
    }

    &.is-active {
        border-color: var(--edu-primary);
        background: var(--edu-primary);
        color: #fff;
    }
}

// --- Notification groups (Today / Earlier) -----------------------------------
.edu-notif-group-label {
    display: flex;
    align-items: center;
    gap: var(--edu-space-2);
    margin: var(--edu-space-4) 0 var(--edu-space-2);
    font-size: var(--edu-fs-xs);
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--edu-muted);

    &::after {
        content: '';
        flex: 1;
        height: 1px;
        background: var(--edu-border);
    }
}

.edu-notif-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--edu-primary);
    flex-shrink: 0;
}

// --- Sticky data tables + mobile card view -----------------------------------
.edu-table-sticky thead th {
    position: sticky;
    top: 0;
    z-index: var(--edu-z-base);
    background: var(--edu-surface);
    box-shadow: inset 0 -1px 0 var(--edu-border);
}

// Below lg each row becomes a card. The label of every cell comes from the
// data-label attribute, so nothing is lost when the header disappears.
@media (max-width: 991.98px) {
    .edu-table-cards {
        thead {
            display: none;
        }

        tbody tr {
            display: block;
            border: 1px solid var(--edu-border);
            border-radius: var(--edu-radius-sm);
            margin-bottom: var(--edu-space-3);
            padding: var(--edu-space-2);
            background: var(--edu-surface);
        }

        tbody td {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: var(--edu-space-3);
            border: 0;
            padding: 0.4rem 0.25rem;
            text-align: right;
        }

        tbody td::before {
            content: attr(data-label);
            font-weight: 600;
            font-size: var(--edu-fs-small);
            color: var(--edu-muted);
            text-align: left;
        }
    }
}

// --- Style guide helpers -----------------------------------------------------
.edu-swatch {
    height: 56px;
    border-radius: var(--edu-radius-sm);
    border: 1px solid var(--edu-border);
}

.edu-guide-block {
    padding: var(--edu-space-4);
    border: 1px solid var(--edu-border);
    border-radius: var(--edu-radius-sm);
    background: var(--edu-surface);
}

// --- Landing page (guests) ---------------------------------------------------
.edu-landing {
    background: var(--edu-surface);
}

.edu-landing-hero {
    position: relative;
    overflow: hidden;
    padding: var(--edu-space-8) 0 var(--edu-space-7);
    background: linear-gradient(180deg, var(--edu-surface-soft) 0%, var(--edu-surface) 100%);
}

.edu-landing-shape {
    position: absolute;
    border-radius: 50%;
    filter: blur(6px);
    pointer-events: none;
    opacity: 0.55;
}

.edu-landing-shape.is-one {
    width: 380px;
    height: 380px;
    top: -160px;
    right: -120px;
    background: radial-gradient(circle at 30% 30%, rgba(79, 70, 229, 0.35), transparent 70%);
}

.edu-landing-shape.is-two {
    width: 320px;
    height: 320px;
    bottom: -180px;
    left: -120px;
    background: radial-gradient(circle at 60% 40%, rgba(124, 58, 237, 0.3), transparent 70%);
}

.edu-landing-title {
    font-size: var(--edu-fs-display);
    line-height: 1.1;
    letter-spacing: -0.03em;
}

// Mockup of the live-class screen: pure CSS, no image request.
.edu-mockup {
    border-radius: var(--edu-radius);
    border: 1px solid var(--edu-border);
    background: var(--edu-surface);
    box-shadow: var(--edu-shadow-pop);
    overflow: hidden;
}

.edu-mockup-bar {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    padding: var(--edu-space-2) var(--edu-space-3);
    border-bottom: 1px solid var(--edu-border);
    background: var(--edu-surface-soft);
}

.edu-mockup-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--edu-border-strong);
}

.edu-mockup-stage {
    position: relative;
    aspect-ratio: 16 / 9;
    background: linear-gradient(140deg, #1e1b4b 0%, #312e81 50%, #4f46e5 100%);
}

.edu-mockup-tile {
    position: absolute;
    border-radius: var(--edu-radius-xs);
    background: rgba(255, 255, 255, 0.14);
    border: 1px solid rgba(255, 255, 255, 0.22);
}

.edu-mockup-bar-strip {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--edu-space-3);
    padding: var(--edu-space-3);
    background: var(--edu-surface-soft);
}

.edu-mockup-tool {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--edu-surface);
    border: 1px solid var(--edu-border);
    color: var(--edu-text-soft);
}

.edu-step-card {
    position: relative;
    padding: var(--edu-space-5);
    border: 1px solid var(--edu-border);
    border-radius: var(--edu-radius);
    background: var(--edu-surface);
    height: 100%;
}

.edu-step-number {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: var(--edu-radius-xs);
    background: var(--edu-primary-soft);
    color: var(--edu-primary);
    font-weight: 700;
}

.edu-feature-card {
    padding: var(--edu-space-5);
    border: 1px solid var(--edu-border);
    border-radius: var(--edu-radius);
    background: var(--edu-surface);
    height: 100%;
    transition: transform var(--edu-transition), box-shadow var(--edu-transition),
        border-color var(--edu-transition);

    &:hover {
        transform: translateY(-3px);
        border-color: var(--edu-border-strong);
        box-shadow: var(--edu-shadow-card);
    }
}

.edu-stats-strip {
    border-radius: var(--edu-radius);
    background: linear-gradient(120deg, #312e81 0%, #4f46e5 100%);
    color: #fff;
    padding: var(--edu-space-6) var(--edu-space-5);
}

.edu-stat-figure {
    font-size: clamp(1.5rem, 1.1rem + 1.4vw, 2.25rem);
    font-weight: 800;
    line-height: 1.1;
}

.edu-landing-section {
    padding: var(--edu-space-7) 0;
}

.edu-section-title {
    font-size: var(--edu-fs-h2);
    letter-spacing: -0.02em;
}

// A tidy accordion: no double borders, the open item is clearly marked.
.edu-faq .accordion-item {
    border: 1px solid var(--edu-border);
    border-radius: var(--edu-radius-sm);
    background: var(--edu-surface);
    margin-bottom: var(--edu-space-2);
    overflow: hidden;
}

.edu-faq .accordion-button {
    font-weight: 600;
    background: var(--edu-surface);
    color: var(--edu-text);
    box-shadow: none;

    &:not(.collapsed) {
        color: var(--edu-primary);
        background: var(--edu-surface-soft);
    }
}

.edu-landing-footer {
    padding: var(--edu-space-6) 0 var(--edu-space-5);
    border-top: 1px solid var(--edu-border);
    background: var(--edu-surface-soft);
}

// On phones the bottom navigation and the floating button need room.
@media (max-width: 991.98px) {
    .edu-has-mobile-nav {
        padding-bottom: calc(64px + env(safe-area-inset-bottom, 0));
    }
}

// --- Phase 3C additions ------------------------------------------------------

// Skip link: invisible until it receives keyboard focus, then a normal button.
.edu-skip-link {
    position: fixed;
    top: var(--edu-space-2);
    left: var(--edu-space-2);
    z-index: calc(var(--edu-z-fab) + 10);
    padding: 0.5rem 0.9rem;
    border-radius: var(--edu-radius-sm);
    background: var(--edu-surface);
    color: var(--edu-text);
    box-shadow: var(--edu-shadow-pop);
    font-weight: 600;
    text-decoration: none;

    &:focus {
        outline: 2px solid var(--edu-primary);
        outline-offset: 2px;
    }
}

// Sidebars that stay in view while the main column scrolls.
.edu-sticky-aside {
    position: static;
}

@media (min-width: 1200px) {
    .edu-sticky-aside {
        position: sticky;
        top: calc(var(--edu-navbar-height) + var(--edu-space-3));
    }
}

// Character ring in the feed composer.
.edu-char-ring {
    --edu-ring-progress: 0deg;
    --edu-ring-color: var(--edu-primary);
    position: relative;
    display: inline-grid;
    place-items: center;
    width: 42px;
    height: 42px;
    flex-shrink: 0;
    border-radius: 50%;
    background: conic-gradient(var(--edu-ring-color) var(--edu-ring-progress), var(--edu-border) 0);
    font-size: var(--edu-fs-xs);
    font-weight: 700;
    color: var(--edu-muted);
    transition: background var(--edu-transition);

    &::before {
        content: '';
        position: absolute;
        inset: 4px;
        border-radius: 50%;
        background: var(--edu-surface);
    }

    > * {
        position: relative;
        z-index: 1;
    }

    &.is-warning {
        --edu-ring-color: var(--edu-warning);
    }

    &.is-full {
        --edu-ring-color: var(--edu-danger);
    }
}

// Heart pop when a post is liked (disabled by prefers-reduced-motion).
.edu-like-pop {
    animation: edu-like-pop 220ms ease-out;
}

@keyframes edu-like-pop {
    0% { transform: scale(1); }
    40% { transform: scale(1.35); }
    70% { transform: scale(0.94); }
    100% { transform: scale(1); }
}

// Notification row icon.
.edu-notif-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--edu-primary-soft);
    color: var(--edu-primary);
    font-size: 1rem;

    .notification-unread & {
        background: var(--edu-primary);
        color: #fff;
    }
}

// Hashtag page hero.
.edu-hashtag-hero {
    display: grid;
    place-items: center;
    width: 64px;
    height: 64px;
    flex-shrink: 0;
    border-radius: var(--edu-radius);
    background: linear-gradient(135deg, var(--edu-primary) 0%, var(--edu-secondary) 100%);
    color: #fff;
    font-size: 2rem;
    font-weight: 700;
    line-height: 1;
}

// Admin tables: keep the post preview readable without stretching the table.
.edu-admin-post-cell {
    min-width: 16rem;
    max-width: 30rem;

    @media (max-width: 991.98px) {
        min-width: 0;
        max-width: none;
    }
}

// Accessibility: honour the operating-system "reduce motion" setting. One
// single media query for the whole stylesheet (Phase 3C merged the block that
// used to be duplicated here).
@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
        scroll-behavior: auto !important;
    }

    .edu-feature-card:hover,
    .edu-card-hover:hover {
        transform: none;
    }

    .edu-page,
    .edu-page.is-leaving {
        animation: none !important;
        transform: none !important;
        opacity: 1 !important;
    }

    .edu-chart-skeleton span,
    .edu-skeleton,
    .edu-live-dot,
    .edu-toast,
    .btn.is-loading::after {
        animation: none !important;
    }
}

```

# 14. Tests

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

## 15. Manual test checklist

### FR-1 — registration
1. `/register` shows the branded split-screen layout; on <992 px the form is full
   width and the panel stacks above it.
2. Choose **Teacher**, the optional **Subject** field appears; choose **Student**,
   it disappears (works without JS too, the field is simply hidden).
3. Submit empty: inline errors under name, email, password; no page reload loss.
4. Submit a taken email: "The email has already been taken."
5. Mismatched passwords: confirmation error, nothing is created.
6. Register a student → redirected to `/student/dashboard`, nav shows the student
   badge, success flash visible.
7. Register a teacher with a subject → `/teacher/dashboard`, subject saved.
8. Tamper with the form (`role=admin`) → validation error, no admin is created.
9. Show/hide password toggles on both password fields.

### FR-2 — login / logout
1. Wrong password → inline error, still on `/login`.
2. 6 failed attempts in a row → throttled message.
3. Log in as admin → `/admin/dashboard`; teacher → `/teacher/dashboard`;
   student → `/student/dashboard`.
4. Tick **Remember me**, close the browser, reopen → still signed in.
5. Logout from the navbar → back to `/`, session gone, `/dashboard` redirects to
   `/login`.
6. Guests opening `/dashboard` or any role dashboard are sent to `/login`.

### FR-3 — password reset
1. `/forgot-password` accepts an existing email and shows the generic
   "we emailed you" message (no account enumeration).
2. With `MAIL_MAILER=log`, `storage/logs/laravel.log` contains the branded email;
   with Mailtrap/SMTP the mail arrives in the inbox.
3. The link opens `/reset-password/{token}` with the email pre-filled.
4. Weak/short password → inline errors; mismatched confirmation → error.
5. Valid reset → flash "password reset", old password rejected, new one works.
6. Reusing the same link → "invalid token" error.

### FR-6 — roles and blocking
1. Student opening `/admin/dashboard` or `/teacher/dashboard` → **403**.
2. Teacher opening `/admin/dashboard` → **403**.
3. Admin opening `/admin/dashboard` → 200.
4. Block a user (admin → Users → Block), then try to log in → refused with a clear
   message, no session created.
5. Log in first, then block the account in another browser and click any link →
   the session is ended and the login page explains why.
6. `php artisan route:list --path=dashboard` shows `auth`, `role:…` and
   `not.blocked` on every dashboard route.

## 16. Done when

- [x] `php artisan migrate:fresh --seed` creates 1 admin, 3 teachers, 6 students.
- [x] `npm run build` succeeds with Bootstrap 5, Icons and Popper and no Tailwind
      anywhere (`grep -r tailwind resources package.json` returns nothing).
- [x] `public/storage` exists.
- [x] Registration accepts teacher/student only; `role=admin` is rejected.
- [x] Login redirects to the right dashboard per role and remembers the session.
- [x] Logout ends the session.
- [x] Password reset email renders and the token flow works.
- [x] Blocked users cannot log in and are logged out mid-session.
- [x] `role:` middleware returns 403 for the wrong role.
- [x] Phase 1 suite green: **22 passed, 74 assertions**.
- [x] Full suite green: **266 passed, 1401 assertions**.
