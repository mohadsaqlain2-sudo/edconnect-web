<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FR-27, FR-28: admin user, post and class management.
 */
class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    /* -----------------------------------------------------------------
     | Access control
     * ----------------------------------------------------------------- */

    public function test_only_admins_can_reach_the_management_pages(): void
    {
        $routes = [
            'admin.users.index',
            'admin.posts.index',
            'admin.classes.index',
            'admin.reports.index',
        ];

        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        foreach ($routes as $name) {
            $this->actingAs($admin)->get(route($name))->assertOk();
            $this->actingAs($teacher)->get(route($name))->assertForbidden();
            $this->actingAs($student)->get(route($name))->assertForbidden();
        }
    }

    /* -----------------------------------------------------------------
     | Users (FR-27)
     * ----------------------------------------------------------------- */

    public function test_admins_can_search_and_filter_users(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create(['name' => 'Alice Teacher']);
        $blocked = User::factory()->student()->blocked()->create(['name' => 'Bob Blocked']);

        $response = $this->actingAs($admin)
            ->get(route('admin.users.index', ['q' => 'Alice']))
            ->assertOk();

        $users = $response->viewData('users');
        $this->assertSame([$teacher->id], $users->pluck('id')->all());

        $response = $this->actingAs($admin)
            ->get(route('admin.users.index', ['blocked' => '1']))
            ->assertOk();

        $ids = $response->viewData('users')->pluck('id')->all();
        $this->assertContains($blocked->id, $ids);
        $this->assertNotContains($teacher->id, $ids);
    }

    public function test_an_admin_can_block_and_unblock_a_user(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.block', $student))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue($student->fresh()->is_blocked);

        // The blocked account can no longer browse the app. Refresh the model
        // first: actingAs() would otherwise reuse the stale is_blocked = false.
        $this->actingAs($student->refresh())
            ->get(route('feed.index'))
            ->assertRedirect(route('login'));

        $this->actingAs($admin)
            ->patch(route('admin.users.unblock', $student))
            ->assertSessionHas('success');

        $this->assertFalse($student->fresh()->is_blocked);
    }

    public function test_an_admin_cannot_block_or_delete_themselves_or_another_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.block', $admin))
            ->assertForbidden();

        $this->actingAs($admin)
            ->patch(route('admin.users.block', $otherAdmin))
            ->assertForbidden();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $otherAdmin))
            ->assertForbidden();

        $this->assertFalse($admin->fresh()->is_blocked);
        $this->assertDatabaseHas('users', ['id' => $otherAdmin->id]);
    }

    public function test_deleting_a_user_removes_their_content_and_reports(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();

        $post = Post::factory()->create(['user_id' => $student->id]);
        Report::factory()->create([
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $student))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $student->id]);
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
        $this->assertDatabaseCount('reports', 0);
    }

    /* -----------------------------------------------------------------
     | Posts (FR-28)
     * ----------------------------------------------------------------- */

    public function test_an_admin_can_hide_unhide_and_delete_a_post(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->create(['body' => 'Something inappropriate']);

        $this->actingAs($admin)
            ->patch(route('admin.posts.hide', $post))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue($post->fresh()->is_hidden);

        $this->actingAs($admin)
            ->patch(route('admin.posts.hide', $post))
            ->assertRedirect();

        $this->assertFalse($post->fresh()->is_hidden);

        $this->actingAs($admin)
            ->delete(route('admin.posts.destroy', $post))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_hiding_a_post_marks_its_pending_reports_reviewed(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->create();

        $report = Report::factory()->create([
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
        ]);

        $this->actingAs($admin)->patch(route('admin.posts.hide', $post));

        $this->assertSame(Report::STATUS_REVIEWED, $report->fresh()->status);
    }

    public function test_admins_can_filter_posts_by_visibility(): void
    {
        $admin = User::factory()->admin()->create();
        $visible = Post::factory()->create();
        $hidden = Post::factory()->hidden()->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.posts.index', ['hidden' => '1']))
            ->assertOk();

        $posts = $response->viewData('posts');

        $this->assertSame([$hidden->id], $posts->pluck('id')->all());
        $this->assertNotContains($visible->id, $posts->pluck('id')->all());
    }

    /* -----------------------------------------------------------------
     | Classes (FR-28)
     * ----------------------------------------------------------------- */

    public function test_an_admin_can_hide_unhide_and_delete_a_class(): void
    {
        $admin = User::factory()->admin()->create();
        $classRoom = ClassRoom::factory()->create(['title' => 'Bad class']);

        $this->actingAs($admin)
            ->patch(route('admin.classes.hide', $classRoom))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue($classRoom->fresh()->is_hidden);

        // A hidden class is gone from the student catalogue.
        $this->actingAs(User::factory()->student()->create())
            ->get(route('classes.discover'))
            ->assertOk()
            ->assertDontSee('Bad class');

        // ... but the teacher still sees it (ClassPolicy::view).
        $this->actingAs($classRoom->teacher)
            ->get(route('classes.show', $classRoom))
            ->assertOk();

        $this->actingAs($admin)
            ->patch(route('admin.classes.hide', $classRoom))
            ->assertRedirect();

        $this->assertFalse($classRoom->fresh()->is_hidden);

        $this->actingAs($admin)
            ->delete(route('admin.classes.destroy', $classRoom))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('classes', ['id' => $classRoom->id]);
    }

    public function test_the_class_list_can_be_filtered_by_status(): void
    {
        $admin = User::factory()->admin()->create();
        $live = ClassRoom::factory()->live()->create();
        ClassRoom::factory()->completed()->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.classes.index', ['status' => ClassRoom::STATUS_LIVE]))
            ->assertOk();

        $classes = $response->viewData('classes');

        $this->assertSame([$live->id], $classes->pluck('id')->all());
    }
}
