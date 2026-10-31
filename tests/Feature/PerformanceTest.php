<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Guards the performance requirements in SRS 5.1: the feed and the admin
 * tables must paginate and must not run one query per card (N+1).
 */
class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_feed_paginates_and_keeps_its_query_count_bounded(): void
    {
        $viewer = User::factory()->student()->create();
        $author = User::factory()->teacher()->create();
        $viewer->follow($author);

        // More posts than one page, each with hashtags, likes and comments.
        Post::factory()->count(20)->for($author)->create();

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($viewer)->get(route('feed.index'));

        $queries = count(DB::getQueryLog());

        $response->assertOk();
        // One page of ten posts, newest first, with the total kept for links.
        $response->assertViewHas('posts', static function ($posts): bool {
            return $posts->count() === 10 && $posts->total() === 20;
        });

        // Session, user, count and feed queries - but never 20+ (one per card).
        $this->assertLessThanOrEqual(
            15,
            $queries,
            "The feed ran {$queries} queries for 20 posts; eager loading is missing."
        );
    }

    public function test_the_admin_user_table_paginates(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(30)->student()->create();

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertViewHas('users', static function ($users): bool {
            return $users->count() === 20 && $users->total() >= 30;
        });
    }
}
