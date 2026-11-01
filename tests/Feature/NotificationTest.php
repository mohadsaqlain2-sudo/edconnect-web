<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Post;
use App\Models\User;
use App\Notifications\NewFollower;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 6: notifications (FR-23, FR-24, FR-25).
 *
 * The test environment uses the "sync" queue driver, so a queued notification
 * is written to the notifications table straight away and can be asserted.
 */
class NotificationTest extends TestCase
{
    use RefreshDatabase;

    /* ---------------------------------------------------------------
     | Follow (FR-23)
     * --------------------------------------------------------------- */

    public function test_following_a_teacher_notifies_the_teacher(): void
    {
        $student = User::factory()->student()->create();
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($student)->post(route('follows.store', $teacher))->assertRedirect();

        $notification = $teacher->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame('new_follower', $notification->data['type']);
        // A brand new notification is unread.
        $this->assertNull($notification->read_at);
    }

    public function test_a_notification_stores_its_type_message_link_and_actor(): void
    {
        $student = User::factory()->student()->create(['name' => 'Actor Alice']);
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($student)->post(route('follows.store', $teacher));

        $data = $teacher->notifications()->first()->data;

        $this->assertSame('new_follower', $data['type']);
        $this->assertSame($student->id, $data['actor_id']);
        $this->assertSame('Actor Alice', $data['actor_name']);
        $this->assertSame($student->photoUrl(), $data['actor_photo']);
        $this->assertStringContainsString('Actor Alice', $data['message']);
        $this->assertSame(route('users.show', $student), $data['url']);
    }

    public function test_following_the_same_teacher_twice_notifies_only_once(): void
    {
        $student = User::factory()->student()->create();
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($student)->post(route('follows.store', $teacher));
        $this->actingAs($student)->post(route('follows.store', $teacher));

        $this->assertSame(1, $teacher->notifications()->count());
    }

    public function test_unfollowing_does_not_notify(): void
    {
        $student = User::factory()->student()->create();
        $teacher = User::factory()->teacher()->create();
        $student->follow($teacher);

        $this->actingAs($student)->delete(route('follows.destroy', $teacher));

        $this->assertSame(0, $teacher->notifications()->count());
    }

    /* ---------------------------------------------------------------
     | Likes (FR-23)
     * --------------------------------------------------------------- */

    public function test_liking_a_post_notifies_its_author(): void
    {
        $author = User::factory()->create();
        $liker = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        $this->actingAs($liker)->post(route('posts.like', $post));

        $this->assertSame('post_liked', $author->notifications()->first()?->data['type']);
    }

    public function test_liking_your_own_post_does_not_notify_you(): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        $this->actingAs($author)->post(route('posts.like', $post));

        $this->assertSame(0, $author->notifications()->count());
    }

    public function test_unliking_does_not_remove_or_add_a_notification(): void
    {
        $author = User::factory()->create();
        $liker = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        $this->actingAs($liker)->post(route('posts.like', $post));
        $this->actingAs($liker)->delete(route('posts.unlike', $post));

        // The "you were liked" message stays in the inbox.
        $this->assertSame(1, $author->notifications()->count());
    }

    /* ---------------------------------------------------------------
     | Comments (FR-23)
     * --------------------------------------------------------------- */

    public function test_commenting_on_a_post_notifies_its_author(): void
    {
        $author = User::factory()->create();
        $commenter = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        $this->actingAs($commenter)->post(route('comments.store', $post), [
            'body' => 'Very useful, thank you!',
        ])->assertRedirect();

        $notification = $author->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame('post_commented', $notification->data['type']);
    }

    public function test_commenting_on_your_own_post_does_not_notify_you(): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        $this->actingAs($author)->post(route('comments.store', $post), [
            'body' => 'Adding something to my own post.',
        ]);

        $this->assertSame(0, $author->notifications()->count());
    }

    /* ---------------------------------------------------------------
     | New class from a followed teacher (FR-23)
     * --------------------------------------------------------------- */

    public function test_creating_a_class_notifies_the_followers_of_the_teacher(): void
    {
        $teacher = User::factory()->teacher()->create();
        $follower = User::factory()->student()->create();
        $follower->follow($teacher);

        $this->actingAs($teacher)->post(route('classes.store'), [
            'title' => 'Introduction to Algebra',
            'subject' => 'Mathematics',
            'date' => now()->addDays(3)->toDateString(),
            'time' => '10:00',
            'duration' => 60,
        ])->assertRedirect();

        $this->assertSame('new_class', $follower->notifications()->first()?->data['type']);
    }

    public function test_creating_a_class_does_not_notify_the_teacher_themselves(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)->post(route('classes.store'), [
            'title' => 'No Self Notification',
            'date' => now()->addDays(3)->toDateString(),
            'time' => '12:00',
            'duration' => 60,
        ])->assertRedirect();

        $this->assertSame(0, $teacher->notifications()->count());
    }

    public function test_a_blocked_follower_is_not_notified_of_a_new_class(): void
    {
        $teacher = User::factory()->teacher()->create();
        $blocked = User::factory()->student()->blocked()->create();
        $blocked->follow($teacher);

        $this->actingAs($teacher)->post(route('classes.store'), [
            'title' => 'Blocked Followers Stay Quiet',
            'date' => now()->addDays(3)->toDateString(),
            'time' => '12:30',
            'duration' => 45,
        ])->assertRedirect();

        $this->assertSame(0, $blocked->notifications()->count());
    }

    public function test_a_class_does_not_notify_people_who_do_not_follow_the_teacher(): void
    {
        $teacher = User::factory()->teacher()->create();
        $stranger = User::factory()->student()->create();

        $this->actingAs($teacher)->post(route('classes.store'), [
            'title' => 'Advanced Physics',
            'date' => now()->addDays(3)->toDateString(),
            'time' => '11:00',
            'duration' => 45,
        ]);

        $this->assertSame(0, $stranger->notifications()->count());
    }

    /* ---------------------------------------------------------------
     | Unread counter and polling endpoint (FR-24)
     * --------------------------------------------------------------- */

    public function test_the_poll_endpoint_returns_the_unread_counter_and_the_latest_entries(): void
    {
        $user = User::factory()->create();
        $user->notify(new NewFollower(User::factory()->student()->create(['name' => 'Polling Penny'])));

        $this->actingAs($user)
            ->getJson(route('notifications.poll'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('html', fn (string $html) => str_contains($html, 'Polling Penny'));
    }

    public function test_the_bell_and_the_notifications_page_show_the_unread_count(): void
    {
        $user = User::factory()->create();
        $user->notify(new NewFollower(User::factory()->student()->create()));

        // The bell is part of every page of the layout.
        $this->actingAs($user)
            ->get(route('feed.index'))
            ->assertOk()
            ->assertSee('data-notification-badge', false);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('started following you');
    }

    public function test_the_unread_badge_is_hidden_when_there_is_nothing_to_read(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('feed.index'))
            ->assertOk()
            // The badge exists for the polling script but starts hidden.
            ->assertSee('badge rounded-pill text-bg-danger d-none', false);
    }

    public function test_the_poll_endpoint_requires_a_logged_in_user(): void
    {
        $this->getJson(route('notifications.poll'))->assertUnauthorized();
    }

    /* ---------------------------------------------------------------
     | Mark as read (FR-25)
     * --------------------------------------------------------------- */

    public function test_a_user_can_mark_one_notification_as_read(): void
    {
        $user = User::factory()->create();
        $user->notify(new NewFollower(User::factory()->student()->create()));
        $id = $user->notifications()->first()->id;

        $this->actingAs($user)->patch(route('notifications.read', $id))->assertRedirect();

        $this->assertNotNull($user->notifications()->first()->read_at);
        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    public function test_a_user_can_mark_all_notifications_as_read(): void
    {
        $user = User::factory()->create();
        $user->notify(new NewFollower(User::factory()->student()->create()));
        $user->notify(new NewFollower(User::factory()->student()->create()));

        $this->assertSame(2, $user->unreadNotifications()->count());

        $this->actingAs($user)->patch(route('notifications.read-all'))->assertRedirect();

        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    public function test_marking_one_notification_as_read_answers_json_for_the_ajax_button(): void
    {
        $user = User::factory()->create();
        $user->notify(new NewFollower(User::factory()->student()->create()));
        $id = $user->notifications()->first()->id;

        $this->actingAs($user)
            ->patchJson(route('notifications.read', $id))
            ->assertOk()
            ->assertJsonPath('read', true)
            ->assertJsonPath('unread_count', 0);
    }

    /* ---------------------------------------------------------------
     | Filter and delete (FR-25)
     * --------------------------------------------------------------- */

    public function test_the_notifications_page_can_be_filtered_to_unread_only(): void
    {
        $user = User::factory()->create();
        $user->notify(new NewFollower(User::factory()->student()->create(['name' => 'Unread Ursula'])));
        $user->notify(new NewFollower(User::factory()->student()->create(['name' => 'Read Rita'])));

        // Pick the entry by its actor instead of by its timestamp: two rows
        // created in the same second would make "newest" ambiguous.
        $user->notifications
            ->first(fn ($notification) => $notification->data['actor_name'] === 'Read Rita')
            ->markAsRead();

        // Without a filter both entries are listed...
        $all = $this->actingAs($user)->get(route('notifications.index'));
        $all->assertOk();
        $this->assertSame(2, $all->viewData('notifications')->total());

        // ...with the filter only the unread one. The bell dropdown always
        // shows the newest entries, so the assertion looks at the page data
        // instead of at the rendered HTML.
        $unread = $this->actingAs($user)
            ->get(route('notifications.index', ['filter' => 'unread']));

        $unread->assertOk();
        $this->assertSame(1, $unread->viewData('notifications')->total());
        $this->assertSame('Unread Ursula', $unread->viewData('notifications')->first()->data['actor_name']);
    }

    public function test_marking_all_as_read_only_touches_my_notifications(): void
    {
        $mine = User::factory()->create();
        $other = User::factory()->create();

        $mine->notify(new NewFollower(User::factory()->student()->create()));
        $other->notify(new NewFollower(User::factory()->student()->create()));

        $this->actingAs($other)->patch(route('notifications.read-all'))->assertRedirect();

        $this->assertSame(0, $other->unreadNotifications()->count());
        $this->assertSame(1, $mine->unreadNotifications()->count());
    }

    public function test_a_user_can_delete_their_own_notification(): void
    {
        $user = User::factory()->create();
        $user->notify(new NewFollower(User::factory()->student()->create()));
        $id = $user->notifications()->first()->id;

        $this->actingAs($user)->delete(route('notifications.destroy', $id))->assertRedirect();

        $this->assertSame(0, $user->notifications()->count());
    }

    public function test_a_user_cannot_delete_somebody_elses_notification(): void
    {
        $owner = User::factory()->create();
        $owner->notify(new NewFollower(User::factory()->student()->create()));
        $id = $owner->notifications()->first()->id;

        $this->actingAs(User::factory()->create())
            ->delete(route('notifications.destroy', $id))
            ->assertNotFound();

        $this->assertSame(1, $owner->notifications()->count());
    }

    public function test_opening_a_notification_marks_it_read_and_redirects_to_its_page(): void
    {
        $user = User::factory()->create();
        $follower = User::factory()->student()->create();
        $user->notify(new NewFollower($follower));
        $id = $user->notifications()->first()->id;

        $this->actingAs($user)
            ->get(route('notifications.open', $id))
            ->assertRedirect(route('users.show', $follower));

        $this->assertNotNull($user->notifications()->first()->read_at);
    }

    public function test_a_user_cannot_read_somebody_elses_notification(): void
    {
        $owner = User::factory()->create();
        $owner->notify(new NewFollower(User::factory()->student()->create()));
        $id = $owner->notifications()->first()->id;

        $other = User::factory()->create();

        // The lookup goes through the relation of the current user, so a
        // foreign id is simply "not found".
        $this->actingAs($other)->patch(route('notifications.read', $id))->assertNotFound();
        $this->actingAs($other)->get(route('notifications.open', $id))->assertNotFound();

        $this->assertNull($owner->notifications()->first()->read_at);
    }

    /* ---------------------------------------------------------------
     | classes:notify-starting-soon (FR-23)
     * --------------------------------------------------------------- */

    public function test_the_starting_soon_command_notifies_the_teacher_and_the_students_once(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        // The class starts in ten minutes, inside the 15 minute window.
        $start = now()->addMinutes(10);

        $classRoom = ClassRoom::factory()->create([
            'teacher_id' => $teacher->id,
            'status' => ClassRoom::STATUS_SCHEDULED,
            'date' => $start->toDateString(),
            'time' => $start->format('H:i:s'),
            'duration' => 60,
        ]);
        $classRoom->students()->attach($student->id);

        $this->artisan('classes:notify-starting-soon')->assertSuccessful();

        $this->assertSame(1, $teacher->notifications()->count());
        $this->assertSame(1, $student->notifications()->count());
        $this->assertSame('class_starting_soon', $student->notifications()->first()->data['type']);
        // The flag column is set, so the reminder can never be sent twice.
        $this->assertNotNull($classRoom->fresh()->starting_soon_notified_at);

        // Second run: nothing new.
        $this->artisan('classes:notify-starting-soon')->assertSuccessful();

        $this->assertSame(1, $teacher->notifications()->count());
        $this->assertSame(1, $student->notifications()->count());
    }

    public function test_the_starting_soon_command_skips_students_who_are_not_enrolled(): void
    {
        $teacher = User::factory()->teacher()->create();
        $enrolled = User::factory()->student()->create();
        $outsider = User::factory()->student()->create();

        $start = now()->addMinutes(10);

        $classRoom = ClassRoom::factory()->create([
            'teacher_id' => $teacher->id,
            'status' => ClassRoom::STATUS_SCHEDULED,
            'date' => $start->toDateString(),
            'time' => $start->format('H:i:s'),
            'duration' => 60,
        ]);
        $classRoom->students()->attach($enrolled->id);

        $this->artisan('classes:notify-starting-soon')->assertSuccessful();

        $this->assertSame(1, $enrolled->notifications()->count());
        $this->assertSame(0, $outsider->notifications()->count());
    }

    public function test_the_starting_soon_command_ignores_classes_that_start_later(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        $start = now()->addHours(3);

        $classRoom = ClassRoom::factory()->create([
            'teacher_id' => $teacher->id,
            'status' => ClassRoom::STATUS_SCHEDULED,
            'date' => $start->toDateString(),
            'time' => $start->format('H:i:s'),
            'duration' => 60,
        ]);
        $classRoom->students()->attach($student->id);

        $this->artisan('classes:notify-starting-soon')->assertSuccessful();

        $this->assertSame(0, $teacher->notifications()->count());
        $this->assertSame(0, $student->notifications()->count());
        $this->assertNull($classRoom->fresh()->starting_soon_notified_at);
    }

    public function test_the_starting_soon_command_ignores_hidden_classes(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        $start = now()->addMinutes(10);

        $classRoom = ClassRoom::factory()->hidden()->create([
            'teacher_id' => $teacher->id,
            'status' => ClassRoom::STATUS_SCHEDULED,
            'date' => $start->toDateString(),
            'time' => $start->format('H:i:s'),
            'duration' => 60,
        ]);
        $classRoom->students()->attach($student->id);

        $this->artisan('classes:notify-starting-soon')->assertSuccessful();

        $this->assertSame(0, $student->notifications()->count());
        $this->assertSame(0, $teacher->notifications()->count());
    }
}
