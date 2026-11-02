<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Post;
use App\Models\User;
use App\Support\LocaleNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 3F: the adaptive layer.
 *
 * Phase 3D proved the layout works at fixed breakpoints. This suite pins the
 * parts that make it adapt on its own:
 *
 *  - every page ships the PWA head, the network banner and the adaptive
 *    bootstrap (device / input / orientation / network flags),
 *  - right-to-left locales mirror the document,
 *  - the manifest, service worker, offline page and icons are publishable,
 *  - numbers, dates and cards follow the locale and their container,
 *  - the service worker never caches authenticated HTML.
 */
class PhaseThreeFTest extends TestCase
{
    use RefreshDatabase;

    /* ===============================================================
     | Document shell: every page, both layouts
     * =============================================================== */

    public function test_authenticated_pages_ship_the_adaptive_pwa_shell(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->get(route('student.dashboard'));

        $response->assertOk()
            ->assertSee('dir="ltr"', false)
            ->assertSee('manifest.webmanifest', false)
            ->assertSee('icons/apple-touch-icon.png', false)
            ->assertSee('name="apple-mobile-web-app-capable"', false)
            ->assertSee('name="mobile-web-app-capable"', false)
            ->assertSee('id="eduNetworkBanner"', false)
            ->assertSee('id="eduShortcutsDialog"', false)
            ->assertSee('id="eduInstallDialog"', false)
            ->assertSee('data-shortcut-dashboard', false)
            ->assertSee('data-shortcut-feed', false)
            ->assertSee('data-large-text-toggle', false)
            ->assertSee('d.dataset.device', false)
            ->assertSee('data-install-app', false);
    }

    public function test_guest_pages_ship_the_same_head_and_display_tools(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('manifest.webmanifest', false)
            ->assertSee('icons/apple-touch-icon.png', false)
            ->assertSee('id="eduNetworkBanner"', false)
            ->assertSee('class="edu-auth-tools"', false)
            ->assertSee('data-theme-toggle', false)
            ->assertSee('data-large-text-toggle', false)
            ->assertSee('id="eduShortcutsDialog"', false);
    }

    public function test_right_to_left_locales_mirror_the_document(): void
    {
        try {
            app()->setLocale('ar');

            $this->get('/')
                ->assertOk()
                ->assertSee('lang="ar"', false)
                ->assertSee('dir="rtl"', false);
        } finally {
            app()->setLocale('en');
        }
    }

    /* ===============================================================
     | PWA assets
     * =============================================================== */

    public function test_the_manifest_is_valid_and_installable(): void
    {
        $path = public_path('manifest.webmanifest');
        $this->assertFileExists($path);

        $manifest = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('EduConnect', $manifest['name']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('any', $manifest['orientation']);
        $this->assertSame('/', $manifest['start_url']);
        $this->assertSame('/', $manifest['scope']);

        $icons = collect($manifest['icons']);
        $this->assertTrue($icons->contains(fn (array $icon) => $icon['sizes'] === '192x192'));
        $this->assertTrue($icons->contains(fn (array $icon) => $icon['sizes'] === '512x512' && $icon['purpose'] === 'any'));
        $this->assertTrue($icons->contains(fn (array $icon) => $icon['sizes'] === '512x512' && $icon['purpose'] === 'maskable'));

        foreach ($icons as $icon) {
            if (! str_ends_with($icon['src'], '.png')) {
                continue;
            }

            $this->assertFileExists(public_path(ltrim($icon['src'], '/')), $icon['src'].' is missing');
        }

        $this->assertNotEmpty($manifest['shortcuts']);
    }

    public function test_the_service_worker_and_offline_page_are_published(): void
    {
        $this->assertFileExists(public_path('sw.js'));
        $this->assertFileExists(public_path('offline.html'));
        $this->assertFileExists(public_path('icons/icon.svg'));

        $worker = (string) file_get_contents(public_path('sw.js'));

        $this->assertStringContainsString("const OFFLINE_URL = '/offline.html'", $worker);
        $this->assertStringContainsString('SKIP_WAITING', $worker);
        $this->assertStringContainsString('/notifications/poll', $worker);

        // Authenticated HTML must never become a shared cache entry.
        $this->assertStringContainsString('isPublicShell', $worker);
        $this->assertStringContainsString("url.pathname === '/'", $worker);

        $offline = (string) file_get_contents(public_path('offline.html'));
        $this->assertStringContainsString('You are offline', $offline);
        $this->assertStringContainsString('navigator.onLine', $offline);
    }

    public function test_the_adaptive_runtime_loads_before_the_feature_modules(): void
    {
        $app = (string) file_get_contents(resource_path('js/app.js'));

        $adaptive = strpos($app, "import './adaptive'");
        $layout = strpos($app, "import './layout'");
        $feed = strpos($app, "import './feed'");

        $this->assertNotFalse($adaptive, 'adaptive.js must be imported');
        $this->assertNotFalse($layout);
        $this->assertNotFalse($feed);
        $this->assertTrue($adaptive < $layout, 'adaptive.js must run before layout.js');
        $this->assertTrue($adaptive < $feed, 'adaptive.js must run before feed.js');

        // The adaptive stylesheet must stay after the responsive layer.
        $scss = (string) file_get_contents(resource_path('sass/app.scss'));
        $responsive = strpos($scss, "@import 'responsive'");
        $adaptiveScss = strpos($scss, "@import 'adaptive'");

        $this->assertNotFalse($responsive);
        $this->assertNotFalse($adaptiveScss);
        $this->assertTrue($adaptiveScss > $responsive, 'adaptive.scss must be imported last');
    }

    /* ===============================================================
     | Locale-aware output
     * =============================================================== */

    public function test_numbers_follow_the_locale_with_an_intl_fallback(): void
    {
        try {
            app()->setLocale('en');
            $english = LocaleNumber::format(1234);

            app()->setLocale('de');
            $german = LocaleNumber::format(1234);

            $this->assertNotSame($english, $german);
            $this->assertSame(5, strlen($english));
            $this->assertSame(5, strlen($german));
            $this->assertStringContainsString('234', $english);
            $this->assertStringContainsString('234', $german);
        } finally {
            app()->setLocale('en');
        }
    }

    public function test_class_cards_use_a_translated_date_and_a_container(): void
    {
        $classRoom = ClassRoom::factory()->create([
            'date' => '2026-03-04',
            'time' => '10:00:00',
        ]);

        try {
            app()->setLocale('de');
            Carbon::setLocale('de');

            $html = (string) Blade::render(
                '<x-class-card :class-room="$classRoom" />',
                ['classRoom' => $classRoom]
            );

            $this->assertStringContainsString('edu-class-card', $html);
            $this->assertStringContainsString('<time datetime="2026-03-04">', $html);
            $this->assertStringContainsString('Mär', $html);
        } finally {
            app()->setLocale('en');
            Carbon::setLocale('en');
        }
    }

    public function test_post_images_reserve_space_and_blur_up(): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD is required to create the fixture image.');
        }

        Storage::fake('public');

        $path = 'posts/phase3f-'.uniqid().'.png';
        $image = imagecreatetruecolor(4, 3);
        ob_start();
        imagepng($image);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);

        Storage::disk('public')->put($path, $bytes);

        $user = User::factory()->student()->create();
        $post = Post::factory()->create(['user_id' => $user->id, 'image' => $path]);
        Cache::forget('post-image-dimensions:'.$path);

        $this->actingAs($user);

        $html = (string) Blade::render('<x-post-card :post="$post" />', ['post' => $post->fresh()]);

        $this->assertStringContainsString('edu-post-media', $html);
        $this->assertStringContainsString('data-blur-up', $html);
        $this->assertStringContainsString('--edu-media-ratio: 4 / 3', $html);
        $this->assertStringContainsString('loading="lazy"', $html);
        $this->assertStringContainsString('decoding="async"', $html);
    }

    /* ===============================================================
     | Component + page adaptations
     * =============================================================== */

    public function test_data_tables_are_named_keyboard_scroll_regions(): void
    {
        $html = (string) Blade::render(<<<'BLADE'
            <x-data-table id="phase3f" title="People">
                <x-slot name="head"><tr><th>Name</th></tr></x-slot>
                <tr><td>Ada</td></tr>
            </x-data-table>
        BLADE);

        $this->assertStringContainsString('edu-data-table', $html);
        $this->assertStringContainsString('role="region"', $html);
        $this->assertStringContainsString('tabindex="0"', $html);
        $this->assertStringContainsString('People — table', $html);
        $this->assertStringContainsString('<caption class="visually-hidden">', $html);
    }

    public function test_class_grids_and_the_detail_page_use_the_adaptive_layouts(): void
    {
        $student = User::factory()->student()->create();
        $teacher = User::factory()->teacher()->create();
        $classRoom = ClassRoom::factory()->create(['teacher_id' => $teacher->id]);

        $this->actingAs($student)
            ->get(route('classes.discover'))
            ->assertOk()
            ->assertSee('edu-auto-grid', false);

        $this->actingAs($teacher)
            ->get(route('classes.index'))
            ->assertOk()
            ->assertSee('edu-auto-grid', false);

        $this->actingAs($teacher)
            ->get(route('classes.show', $classRoom))
            ->assertOk()
            ->assertSee('edu-split-view', false);
    }

    public function test_every_core_page_ships_the_adaptive_shell(): void
    {
        $student = User::factory()->student()->create();
        $teacher = User::factory()->teacher()->create();
        $admin = User::factory()->admin()->create();

        $pages = [
            [$student, route('feed.index')],
            [$student, route('notifications.index')],
            [$student, route('search.index')],
            [$student, route('profile.edit')],
            [$student, route('posts.create')],
            [$teacher, route('classes.create')],
            [$admin, route('admin.dashboard')],
            [$admin, route('admin.users.index')],
            [$admin, route('admin.posts.index')],
            [$admin, route('admin.classes.index')],
            [$admin, route('admin.reports.index')],
            [$admin, route('admin.style-guide')],
        ];

        foreach ($pages as [$user, $url]) {
            $response = $this->actingAs($user)->get($url);

            $response->assertOk();
            $response->assertSee('manifest.webmanifest', false);
            $response->assertSee('id="eduNetworkBanner"', false);
            $response->assertSee('d.dataset.device', false);
        }

        // Error pages use the guest layout and keep the same shell.
        $this->get('/phase-3f-does-not-exist')
            ->assertNotFound()
            ->assertSee('manifest.webmanifest', false)
            ->assertSee('id="eduNetworkBanner"', false);
    }
}
