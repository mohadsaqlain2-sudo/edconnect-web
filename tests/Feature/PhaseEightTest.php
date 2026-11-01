<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use App\Services\ImageUploadService;
use App\Support\DashboardMetrics;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Phase 8: security audit, performance pass, usability polish and the
 * requirement-wide regression net (SRS 5.1 - 5.4).
 *
 * The page-level tests already prove the features. This suite pins the
 * cross-cutting rules that no single feature owns:
 *
 *  - security headers, CSRF on every write form, mass-assignment safety,
 *    escaped output and safe search,
 *  - upload hardening (MIME/size, decode/re-encode, random names, no PHP),
 *  - rate limits and Form Request validation,
 *  - friendly error pages, meta tags, favicon and the guest landing page,
 *  - query budgets, five-minute dashboard caches, hot-path indexes and the
 *    "under three seconds" page-load target.
 */
class PhaseEightTest extends TestCase
{
    use RefreshDatabase;

    /* ===============================================================
     | Security headers and document head
     * =============================================================== */

    public function test_security_headers_are_sent_on_every_page(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('X-XSS-Protection', '0')
            ->assertHeader(
                'Permissions-Policy',
                'camera=(), microphone=(), geolocation=()'
            );

        // PHP must not advertise its version to the client.
        $this->assertFalse($response->headers->has('X-Powered-By'));
    }

    public function test_the_jitsi_room_relaxes_framing_for_the_video_domain(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $classRoom = ClassRoom::factory()->live()->create(['teacher_id' => $teacher->id]);
        $classRoom->students()->attach($student->id);

        $response = $this->actingAs($student)->get(route('classes.room', $classRoom));

        $response->assertOk();

        // The page embeds Jitsi, so X-Frame-Options must not block it.
        $response->assertHeaderMissing('X-Frame-Options');

        $csp = (string) $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString('frame-src', $csp);
        $this->assertStringContainsString('https://'.config('services.jitsi.domain'), $csp);

        $permissions = (string) $response->headers->get('Permissions-Policy');
        $this->assertStringContainsString('camera=(self', $permissions);
        $this->assertStringContainsString('microphone=(self', $permissions);

        // The normal pages keep the strict default.
        $plain = $this->get('/');
        $plain->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    public function test_the_layout_ships_meta_social_and_favicon_tags(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<meta name="description"', false)
            ->assertSee('<link rel="canonical"', false)
            ->assertSee('property="og:title"', false)
            ->assertSee('property="og:description"', false)
            ->assertSee('name="twitter:card"', false)
            ->assertSee('favicon.ico', false)
            ->assertSee('apple-touch-icon', false)
            ->assertSee('theme-color', false)
            // Guest landing page title from the title slot.
            ->assertSee('<title>Learn together, anywhere · EduConnect</title>', false);

        // The link above must point at a real icon, not the 0-byte placeholder
        // that shipped with the framework skeleton.
        $favicon = public_path('favicon.ico');
        $this->assertFileExists($favicon);
        $this->assertGreaterThan(0, filesize($favicon));

        $header = unpack('vreserved/vtype/vcount', (string) file_get_contents($favicon, false, null, 0, 6));
        $this->assertSame(1, $header['type'], 'favicon.ico must be a valid icon file');
        $this->assertGreaterThanOrEqual(1, $header['count']);
    }

    public function test_every_post_form_carries_a_csrf_token(): void
    {
        // Static audit of the Blade templates: a form that writes must always
        // render @csrf, including the modal/component forms.
        $violations = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $contents = $file->getContents();

            preg_match_all('/<form\b[^>]*>/i', $contents, $matches, PREG_OFFSET_CAPTURE);

            foreach ($matches[0] as [$tag, $offset]) {
                // GET forms (search/filter) do not change state and need no token.
                if (preg_match('/method\s*=\s*["\']?post["\']?/i', $tag) !== 1) {
                    continue;
                }

                $end = strpos($contents, '</form>', $offset);
                $form = $end === false
                    ? substr($contents, $offset)
                    : substr($contents, $offset, $end - $offset);

                if (! str_contains($form, '@csrf')) {
                    $violations[] = $file->getRelativePathname().': '.trim($tag);
                }
            }
        }

        $this->assertSame(
            [],
            $violations,
            "Write forms without @csrf:\n".implode("\n", $violations)
        );
    }

    /* ===============================================================
     | Form Requests and mass-assignment safety
     * =============================================================== */

    public function test_the_comment_form_request_rejects_empty_and_long_bodies(): void
    {
        $author = User::factory()->student()->create();
        $commenter = User::factory()->student()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        $this->actingAs($commenter)
            ->post(route('comments.store', $post), ['body' => ''])
            ->assertSessionHasErrors('body');

        $this->actingAs($commenter)
            ->post(route('comments.store', $post), ['body' => str_repeat('a', 281)])
            ->assertSessionHasErrors('body');

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_a_hidden_post_cannot_collect_new_comments(): void
    {
        $author = User::factory()->student()->create();
        $other = User::factory()->student()->create();
        $post = Post::factory()->hidden()->create(['user_id' => $author->id]);

        // StoreCommentRequest authorizes against PostPolicy::view, which hides
        // moderated posts from everybody except the author and admins.
        $this->actingAs($other)
            ->post(route('comments.store', $post), ['body' => 'Sneaky comment'])
            ->assertForbidden();

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_the_class_status_form_request_rejects_unknown_statuses(): void
    {
        $owner = User::factory()->teacher()->create();
        $stranger = User::factory()->teacher()->create();
        $classRoom = ClassRoom::factory()->create([
            'teacher_id' => $owner->id,
            'status' => ClassRoom::STATUS_SCHEDULED,
        ]);

        $this->actingAs($owner)
            ->patch(route('classes.status', $classRoom), ['status' => 'exploded'])
            ->assertSessionHasErrors('status');

        $this->assertSame(ClassRoom::STATUS_SCHEDULED, $classRoom->fresh()->status);

        // Only the owning teacher may change the status (ClassPolicy).
        $this->actingAs($stranger)
            ->patch(route('classes.status', $classRoom), ['status' => ClassRoom::STATUS_LIVE])
            ->assertForbidden();

        $this->assertSame(ClassRoom::STATUS_SCHEDULED, $classRoom->fresh()->status);
    }

    public function test_account_deletion_requires_the_current_password(): void
    {
        $user = User::factory()->student()->create();

        $this->actingAs($user)
            ->delete(route('profile.destroy'), ['password' => 'wrong-password'])
            ->assertSessionHasErrorsIn('userDeletion', 'password');

        // The account is still there.
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_write_requests_cannot_smuggle_protected_columns(): void
    {
        $author = User::factory()->student()->create();
        $victim = User::factory()->student()->create();

        // A crafted post tries to set the author and the moderation flag.
        $this->actingAs($author)->post(route('posts.store'), [
            'body' => 'A perfectly normal post',
            'user_id' => $victim->id,
            'is_hidden' => 1,
        ])->assertRedirect(route('feed.index'));

        $post = Post::query()->firstOrFail();
        $this->assertSame($author->id, $post->user_id);
        $this->assertFalse($post->is_hidden);

        // A crafted profile update tries to become an administrator and to
        // block/unblock itself.
        $this->actingAs($author)->patch(route('profile.update'), [
            'name' => $author->name,
            'email' => $author->email,
            'role' => User::ROLE_ADMIN,
            'is_blocked' => 1,
        ])->assertRedirect(route('profile.edit'));

        $author->refresh();
        $this->assertSame(User::ROLE_STUDENT, $author->role);
        $this->assertFalse($author->is_blocked);

        // The public registration form cannot create an admin either.
        $this->actingAs($author)->post(route('logout'));

        $this->post(route('register'), [
            'name' => 'Wannabe Admin',
            'email' => 'wannabe@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => User::ROLE_ADMIN,
        ])->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'wannabe@example.test']);
    }

    /* ===============================================================
     | Upload hardening
     * =============================================================== */

    public function test_a_disguised_php_upload_is_rejected_before_it_reaches_the_disk(): void
    {
        Storage::fake('public');

        $user = User::factory()->student()->create();

        $this->actingAs($user)->post(route('posts.store'), [
            'body' => 'Look at this photo',
            'image' => UploadedFile::fake()->createWithContent(
                'shell.jpg',
                '<?php echo "pwned"; ?>'
            ),
        ])->assertSessionHasErrors('image');

        $this->assertDatabaseCount('posts', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_the_image_service_rejects_a_file_that_is_not_decodable(): void
    {
        Storage::fake('public');

        $service = app(ImageUploadService::class);
        $file = UploadedFile::fake()->createWithContent('broken.jpg', 'not an image');

        try {
            $service->storePostImage($file);
            $this->fail('A non-image file was accepted by ImageUploadService.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('image', $exception->errors());
        }

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_uploaded_images_are_resized_re_encoded_and_renamed(): void
    {
        Storage::fake('public');

        $service = app(ImageUploadService::class);

        // 2400x1200 is larger than the 1600x1600 ceiling.
        $path = $service->storePostImage(
            UploadedFile::fake()->image('holiday.jpg', 2400, 1200)
        );

        $this->assertMatchesRegularExpression(
            '#^posts/[A-Za-z0-9]{40}\.(webp|jpg)$#',
            $path,
            'The stored name must be random and use an extension we chose.'
        );

        Storage::disk('public')->assertExists($path);

        $size = getimagesize(Storage::disk('public')->path($path));

        $this->assertIsArray($size);
        $this->assertSame(1600, $size[0]);
        $this->assertSame(800, $size[1]);

        // Even a .php name is stored under a safe extension: the client name
        // never decides what lands on disk.
        $evilNamed = UploadedFile::fake()->image('payload.php', 40, 40);
        $safePath = $service->storePostImage($evilNamed);

        $this->assertStringEndsNotWith('.php', $safePath);
        $this->assertMatchesRegularExpression('#\.(webp|jpg)$#', $safePath);
        Storage::disk('public')->assertExists($safePath);
    }

    /* ===============================================================
     | Rate limiting
     * =============================================================== */

    public function test_login_is_locked_after_five_failed_attempts(): void
    {
        $user = User::factory()->create();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('login'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('email');
        }

        $locked = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $locked->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Too many login attempts',
            (string) session('errors')->first('email')
        );
        $this->assertGuest();
    }

    public function test_registration_is_rate_limited_to_five_per_minute(): void
    {
        // Invalid payloads never create a user (and never log in), so the
        // throttle middleware is what answers the sixth request.
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('register'), [])->assertSessionHasErrors('name');
        }

        $this->post(route('register'), [])->assertStatus(429);
    }

    public function test_every_expensive_write_route_has_a_throttle(): void
    {
        $routes = [
            'posts.store',
            'comments.store',
            'posts.like',
            'reports.posts.store',
            'reports.comments.store',
            'follows.store',
            'notifications.poll',
            'search.suggest',
            'password.email',
        ];

        foreach ($routes as $name) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, "Route {$name} is missing.");

            $middleware = implode('|', $route->gatherMiddleware());
            $this->assertStringContainsString(
                'throttle:',
                $middleware,
                "Route {$name} has no rate limit: {$middleware}"
            );
        }

        $this->assertStringContainsString(
            'throttle:',
            implode('|', Route::getRoutes()->getByName('password.store')->gatherMiddleware())
        );
    }

    public function test_named_rate_limiters_are_registered(): void
    {
        foreach ([
            'register',
            'password-reset',
            'posts',
            'comments',
            'likes',
            'reports',
            'follows',
            'notifications-poll',
            'search-suggest',
        ] as $name) {
            $this->assertTrue(
                RateLimiter::limiter($name) !== null,
                "Named limiter {$name} is missing."
            );
        }
    }

    public function test_the_database_backup_command_is_scheduled_daily(): void
    {
        $this->artisan('backup:database')->assertSuccessful();

        $schedule = app(Schedule::class);
        $events = collect($schedule->events());
        $this->assertTrue(
            $events->contains(fn ($event) => str_contains($event->command ?? '', 'backup:database')),
            'backup:database command is not scheduled.'
        );
    }

    public function test_comments_are_rate_limited_per_user(): void
    {
        $author = User::factory()->student()->create();
        $commenter = User::factory()->student()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        // 30 comments per minute are allowed; the 31st is refused.
        for ($i = 1; $i <= 30; $i++) {
            $this->actingAs($commenter)
                ->post(route('comments.store', $post), ['body' => 'Comment '.$i])
                ->assertRedirect();
        }

        $this->actingAs($commenter)
            ->post(route('comments.store', $post), ['body' => 'One too many'])
            ->assertStatus(429);
    }

    public function test_reports_are_rate_limited_per_user(): void
    {
        $reporter = User::factory()->student()->create();
        $author = User::factory()->teacher()->create();

        // Ten reports per minute: one per post, so every request is unique.
        for ($i = 1; $i <= 10; $i++) {
            $post = Post::factory()->create(['user_id' => $author->id]);

            $this->actingAs($reporter)
                ->post(route('reports.posts.store', $post), ['reason' => 'spam'])
                ->assertRedirect();
        }

        $extra = Post::factory()->create(['user_id' => $author->id]);

        $this->actingAs($reporter)
            ->post(route('reports.posts.store', $extra), ['reason' => 'spam'])
            ->assertStatus(429);
    }

    /* ===============================================================
     | Usability: error pages, escaping, landing page
     * =============================================================== */

    public function test_all_friendly_error_pages_render(): void
    {
        $this->get('/this-page-does-not-exist')
            ->assertNotFound()
            ->assertSee('Page not found');

        $forbidden = $this->get('/admin/dashboard');
        $forbidden->assertRedirect(route('login'));

        $student = User::factory()->student()->create();
        $this->actingAs($student)
            ->get('/admin/dashboard')
            ->assertForbidden()
            ->assertSee('You are not allowed to open this page');

        foreach ([403, 404, 419, 500, 503] as $code) {
            $html = view('errors.'.$code)->render();
            $this->assertStringContainsString('Back to home', $html);
            $this->assertStringContainsString('Still stuck?', $html);
        }

        $this->assertStringContainsString(
            'Something went wrong on our side',
            view('errors.500')->render()
        );
    }

    public function test_user_content_is_escaped_in_the_feed(): void
    {
        $viewer = User::factory()->student()->create();
        $payload = '<script>alert(1)</script>';

        Post::factory()->create([
            'user_id' => $viewer->id,
            'body' => $payload.' #safety',
        ]);

        $response = $this->actingAs($viewer)->get(route('feed.index'));

        $response->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee($payload, false);

        // The hashtag part is still linked, because linkify() escapes first.
        $response->assertSee('#safety');
    }

    public function test_search_input_cannot_break_the_query(): void
    {
        $viewer = User::factory()->student()->create();
        User::factory()->create(['name' => 'Secret Colleague']);

        $this->actingAs($viewer)
            ->get(route('search.index', ['q' => "' OR 1=1 --"]))
            ->assertOk()
            ->assertDontSee('Secret Colleague');
    }

    /* ===============================================================
     | Performance: query budgets, caching, indexes and page timing
     * =============================================================== */

    public function test_dashboards_stay_within_a_query_budget(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        User::factory()->count(4)->student()->create();
        Post::factory()->count(8)->create();
        ClassRoom::factory()->count(4)->create(['teacher_id' => $teacher->id]);
        Report::factory()->count(3)->create();

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $queries = count(DB::getQueryLog());

        $response->assertOk();
        $this->assertLessThanOrEqual(
            45,
            $queries,
            "The admin dashboard ran {$queries} queries; eager loading or caching regressed."
        );

        $this->assertCount(5, $response->viewData('stats'));
        $this->actingAs($student)->get(route('student.dashboard'))->assertOk();
        $this->actingAs($teacher)->get(route('teacher.dashboard'))->assertOk();
    }

    public function test_dashboard_metrics_are_cached_for_five_minutes(): void
    {
        $metrics = app(DashboardMetrics::class);

        $before = $metrics->adminStats();
        $this->assertTrue(Cache::has('dashboard.admin.stats.v1'));

        // A new user changes the real count, but the cached number stays put.
        User::factory()->student()->create();
        $cached = $metrics->adminStats();
        $this->assertSame($before['users'], $cached['users']);

        // Clearing the key proves the stale value came from the cache.
        Cache::forget('dashboard.admin.stats.v1');
        $fresh = $metrics->adminStats();
        $this->assertSame($before['users'] + 1, $fresh['users']);

        // The pending reports counter is cached under its own key.
        Report::factory()->create();
        $this->assertSame(1, $metrics->adminPendingReports());
        $this->assertTrue(Cache::has('dashboard.admin.pending-reports.v1'));

        Report::factory()->create();
        $this->assertSame(1, $metrics->adminPendingReports());
        Cache::forget('dashboard.admin.pending-reports.v1');
        $this->assertSame(2, $metrics->adminPendingReports());

        // Teacher and student keys are scoped per user, never shared.
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $metrics->teacherStats($teacher);
        $metrics->studentStats($student);

        $this->assertTrue(Cache::has("dashboard.teacher.{$teacher->id}.stats.v1"));
        $this->assertTrue(Cache::has("dashboard.student.{$student->id}.stats.v1"));
        $this->assertFalse(Cache::has("dashboard.teacher.{$student->id}.stats.v1"));
    }

    public function test_the_hot_query_indexes_are_present(): void
    {
        $indexes = [
            ['posts', 'phase8_posts_user_created_index'],
            ['posts', 'phase8_posts_hidden_created_index'],
            ['comments', 'phase8_comments_post_created_index'],
            ['comments', 'phase8_comments_user_created_index'],
            ['likes', 'phase8_likes_user_created_index'],
            ['follows', 'phase8_follows_follower_created_index'],
            ['follows', 'phase8_follows_following_created_index'],
            ['classes', 'phase8_classes_status_date_index'],
            ['classes', 'phase8_classes_teacher_date_index'],
            ['classes', 'phase8_classes_hidden_date_index'],
            ['class_enrollments', 'phase8_enrollments_student_created_index'],
            ['reports', 'phase8_reports_status_created_index'],
            ['reports', 'phase8_reports_reportable_status_index'],
            ['notifications', 'phase8_notifications_unread_index'],
            ['users', 'phase8_users_role_blocked_index'],
        ];

        foreach ($indexes as [$table, $index]) {
            $this->assertTrue(
                Schema::hasIndex($table, $index),
                "Missing index {$index} on {$table}."
            );
        }
    }

    public function test_the_primary_pages_load_in_under_three_seconds(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $classRoom = ClassRoom::factory()->scheduled()->create(['teacher_id' => $teacher->id]);
        $classRoom->students()->attach($student->id);
        Post::factory()->count(10)->create(['user_id' => $teacher->id]);

        $pages = [
            'guest landing' => [null, '/'],
            'login' => [null, route('login')],
            'feed' => [$student, route('feed.index')],
            'search' => [$student, route('search.index', ['q' => 'math'])],
            'class catalogue' => [$student, route('classes.discover')],
            'student dashboard' => [$student, route('student.dashboard')],
            'profile' => [$student, route('profile.edit')],
            'notifications' => [$student, route('notifications.index')],
            'teacher dashboard' => [$teacher, route('teacher.dashboard')],
            'my classes' => [$teacher, route('classes.index')],
            'admin dashboard' => [$admin, route('admin.dashboard')],
            'admin users' => [$admin, route('admin.users.index')],
        ];

        foreach ($pages as $label => [$user, $url]) {
            // Warm up view compilation so disk/CPU jitter does not skew the response time
            if ($user === null) {
                $this->get($url);
            } else {
                $this->actingAs($user)->get($url);
            }

            // Measure the fastest of three warm requests. A loaded CI machine
            // can stall a single request for a second or two; using the best
            // run keeps the assertion about the application, not the scheduler.
            // A real regression is still caught because all three runs are slow.
            $best = INF;
            $response = null;

            for ($attempt = 0; $attempt < 3; $attempt++) {
                $started = microtime(true);

                $response = $user === null
                    ? $this->get($url)
                    : $this->actingAs($user)->get($url);

                $best = min($best, microtime(true) - $started);
            }

            $response->assertOk();
            $this->assertLessThan(
                3.0,
                $best,
                "The {$label} page took ".number_format($best, 2).'s (target: under 3s).'
            );
        }
    }
}
