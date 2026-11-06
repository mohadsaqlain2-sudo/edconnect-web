<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\LiveSignal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 10: the signaling API of the app's own WebRTC room
 * (FR-18, FR-20, FR-22, FR-28).
 *
 * Every endpoint re-applies ClassPolicy::join, so the tests below check the
 * exact same access rule as the room page: the owning teacher, or an enrolled
 * student, and never a hidden class.
 */
class LiveRoomApiTest extends TestCase
{
    use RefreshDatabase;

    /* -----------------------------------------------------------------
     | Access control (FR-20, FR-28)
     * ----------------------------------------------------------------- */

    public function test_a_guest_cannot_reach_the_signaling_api(): void
    {
        $classRoom = ClassRoom::factory()->live()->create();

        $this->post(route('live.join', $classRoom))->assertRedirect(route('login'));
        $this->get(route('live.signals', $classRoom))->assertRedirect(route('login'));
    }

    public function test_a_student_who_is_not_enrolled_gets_a_403(): void
    {
        $classRoom = ClassRoom::factory()->live()->create();
        $outsider = User::factory()->student()->create();

        $this->actingAs($outsider)
            ->postJson(route('live.join', $classRoom))
            ->assertForbidden();
    }

    public function test_an_admin_is_not_a_room_participant(): void
    {
        $classRoom = ClassRoom::factory()->live()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson(route('live.join', $classRoom))
            ->assertForbidden();
    }

    public function test_a_hidden_class_cannot_be_joined(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        $classRoom = ClassRoom::factory()->live()->hidden()->create(['teacher_id' => $teacher->id]);
        $classRoom->students()->attach($student->id);

        // Even the enrolled student is refused while an admin hid the class.
        $this->actingAs($student)
            ->postJson(route('live.join', $classRoom))
            ->assertForbidden();
    }

    /* -----------------------------------------------------------------
     | Joining (FR-20)
     * ----------------------------------------------------------------- */

    public function test_a_student_can_join_only_when_the_class_is_live(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        $scheduled = ClassRoom::factory()->scheduled()->create(['teacher_id' => $teacher->id]);
        $scheduled->students()->attach($student->id);

        // Not live yet: the API refuses with 409 (the page shows "not started").
        $this->actingAs($student)
            ->postJson(route('live.join', $scheduled))
            ->assertStatus(409);

        // The teacher may open the room at any time.
        $this->actingAs($teacher)
            ->postJson(route('live.join', $scheduled))
            ->assertOk()
            ->assertJsonPath('self.is_moderator', true);
    }

    public function test_joining_a_live_class_returns_the_ice_servers_and_the_roster(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        $classRoom = ClassRoom::factory()->live()->create(['teacher_id' => $teacher->id]);
        $classRoom->students()->attach($student->id);

        $response = $this->actingAs($student)
            ->postJson(route('live.join', $classRoom))
            ->assertOk()
            ->assertJsonPath('self.id', $student->id)
            ->assertJsonPath('self.is_moderator', false)
            ->assertJsonPath('ice_servers.0.urls', config('live.stun'));

        // The roster lists the participant who just joined.
        $participantIds = collect($response->json('participants'))->pluck('id');
        $this->assertTrue($participantIds->contains($student->id));
    }

    public function test_the_roster_carries_each_participants_profile_photo(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        $classRoom = ClassRoom::factory()->live()->create(['teacher_id' => $teacher->id]);
        $classRoom->students()->attach($student->id);

        // The joiner is handed their own photo (the default avatar, by default)...
        $joined = $this->actingAs($student)
            ->postJson(route('live.join', $classRoom))
            ->assertOk();

        $this->assertSame($student->photoUrl(), $joined->json('self.photo_url'));

        // ...and so is every other participant, so a camera-off tile can show a
        // face instead of only the initials.
        $teacherView = $this->actingAs($teacher)
            ->postJson(route('live.join', $classRoom))
            ->assertOk();

        $studentEntry = collect($teacherView->json('participants'))->firstWhere('id', $student->id);

        $this->assertNotNull($studentEntry);
        $this->assertSame($student->photoUrl(), $studentEntry['photo_url']);
    }

    /* -----------------------------------------------------------------
     | Signaling (FR-20, FR-22)
     * ----------------------------------------------------------------- */

    public function test_a_signal_is_delivered_only_to_its_sender_and_target(): void
    {
        $teacher = User::factory()->teacher()->create();
        $studentA = User::factory()->student()->create();
        $studentB = User::factory()->student()->create();

        $classRoom = ClassRoom::factory()->live()->create(['teacher_id' => $teacher->id]);
        $classRoom->students()->attach([$studentA->id, $studentB->id]);

        $this->actingAs($teacher)
            ->postJson(route('live.signal', $classRoom), [
                'type' => 'offer',
                'to' => $studentA->id,
                'payload' => ['sdp' => 'v=0'],
            ])
            ->assertStatus(201);

        // The target receives it...
        $this->actingAs($studentA)
            ->getJson(route('live.signals', $classRoom))
            ->assertOk()
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.type', 'offer')
            ->assertJsonPath('messages.0.from', $teacher->id)
            ->assertJsonPath('messages.0.to', $studentA->id);

        // ...the sender sees their own message...
        $this->actingAs($teacher)
            ->getJson(route('live.signals', $classRoom))
            ->assertJsonCount(1, 'messages');

        // ...but a third participant never does.
        $this->actingAs($studentB)
            ->getJson(route('live.signals', $classRoom))
            ->assertJsonCount(0, 'messages');
    }

    public function test_a_chat_line_is_stored_and_broadcast_to_every_participant(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        $classRoom = ClassRoom::factory()->live()->create(['teacher_id' => $teacher->id]);
        $classRoom->students()->attach($student->id);

        $this->actingAs($student)
            ->postJson(route('live.chat', $classRoom), ['text' => 'Hello everybody'])
            ->assertStatus(201);

        // A chat line is a broadcast: stored with no target.
        $this->assertDatabaseHas('live_signals', [
            'class_id' => $classRoom->id,
            'sender_id' => $student->id,
            'target_id' => null,
            'type' => 'chat',
        ]);

        // The teacher's poll carries the message with its author and body.
        $this->actingAs($teacher)
            ->getJson(route('live.signals', $classRoom))
            ->assertOk()
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.type', 'chat')
            ->assertJsonPath('messages.0.payload.text', 'Hello everybody');
    }

    public function test_leaving_the_room_drops_the_presence(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        $classRoom = ClassRoom::factory()->live()->create(['teacher_id' => $teacher->id]);
        $classRoom->students()->attach($student->id);

        // The student joins, so their tile appears in the roster.
        $this->actingAs($student)
            ->postJson(route('live.join', $classRoom))
            ->assertOk();

        $this->actingAs($student)
            ->postJson(route('live.leave', $classRoom))
            ->assertOk()
            ->assertJsonPath('ok', true);

        // The teacher joining afterwards must not see the student any more.
        $response = $this->actingAs($teacher)
            ->postJson(route('live.join', $classRoom))
            ->assertOk();

        $participantIds = collect($response->json('participants'))->pluck('id');
        $this->assertFalse($participantIds->contains($student->id));
        $this->assertTrue($participantIds->contains($teacher->id));
    }

    /* -----------------------------------------------------------------
     | The polling cursor and the visibility filter (fixed behaviour)
     * ----------------------------------------------------------------- */

    public function test_last_id_never_skips_a_visible_message(): void
    {
        $teacher = User::factory()->teacher()->create();
        $studentA = User::factory()->student()->create();
        $studentB = User::factory()->student()->create();

        $classRoom = ClassRoom::factory()->live()->create(['teacher_id' => $teacher->id]);
        $classRoom->students()->attach([$studentA->id, $studentB->id]);

        // A message addressed to A...
        $offer = LiveSignal::create([
            'class_id' => $classRoom->id,
            'sender_id' => $teacher->id,
            'target_id' => $studentA->id,
            'type' => 'offer',
            'payload' => ['sdp' => 'v=0'],
        ]);

        // ...then a HIGHER id addressed to B, which A is never allowed to see.
        $other = LiveSignal::create([
            'class_id' => $classRoom->id,
            'sender_id' => $teacher->id,
            'target_id' => $studentB->id,
            'type' => 'offer',
            'payload' => ['sdp' => 'v=1'],
        ]);

        $this->assertGreaterThan($offer->id, $other->id);

        $first = $this->actingAs($studentA)
            ->getJson(route('live.signals', $classRoom))
            ->assertOk()
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.id', $offer->id);

        // The cursor stops at A's newest visible row, never at B's row.
        $this->assertSame($offer->id, $first->json('last_id'));
        $this->assertNotSame($other->id, $first->json('last_id'));

        // Polling again with that cursor does not jump past the invisible row.
        $second = $this->actingAs($studentA)
            ->getJson(route('live.signals', $classRoom).'?after='.$first->json('last_id'))
            ->assertOk()
            ->assertJsonCount(0, 'messages');
        $this->assertSame($offer->id, $second->json('last_id'));

        // A brand new visible message still arrives on the next poll.
        $answer = LiveSignal::create([
            'class_id' => $classRoom->id,
            'sender_id' => $teacher->id,
            'target_id' => $studentA->id,
            'type' => 'answer',
            'payload' => ['sdp' => 'v=2'],
        ]);

        $third = $this->actingAs($studentA)
            ->getJson(route('live.signals', $classRoom).'?after='.$first->json('last_id'))
            ->assertOk()
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.id', $answer->id)
            ->assertJsonPath('messages.0.type', 'answer');

        $this->assertSame($answer->id, $third->json('last_id'));
    }

    public function test_leaving_the_room_broadcasts_a_bye_to_the_others(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        $classRoom = ClassRoom::factory()->live()->create(['teacher_id' => $teacher->id]);
        $classRoom->students()->attach($student->id);

        $this->actingAs($student)
            ->postJson(route('live.join', $classRoom))
            ->assertOk();

        $this->actingAs($student)
            ->postJson(route('live.leave', $classRoom))
            ->assertOk();

        // The leave is stored as a broadcast "bye" row.
        $this->assertDatabaseHas('live_signals', [
            'class_id' => $classRoom->id,
            'sender_id' => $student->id,
            'target_id' => null,
            'type' => 'bye',
        ]);

        // The other participants pick it up on their next poll.
        $this->actingAs($teacher)
            ->getJson(route('live.signals', $classRoom))
            ->assertOk()
            ->assertJsonPath('messages.0.type', 'bye')
            ->assertJsonPath('messages.0.from', $student->id);

        // A broadcast is delivered to the leaver as well.
        $this->actingAs($student)
            ->getJson(route('live.signals', $classRoom))
            ->assertOk()
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.type', 'bye');
    }

    public function test_the_visibility_filter_is_targeted_or_mine_or_broadcast(): void
    {
        $teacher = User::factory()->teacher()->create();
        $studentA = User::factory()->student()->create();
        $studentB = User::factory()->student()->create();

        $classRoom = ClassRoom::factory()->live()->create(['teacher_id' => $teacher->id]);
        $classRoom->students()->attach([$studentA->id, $studentB->id]);

        // A broadcast chat line from A: everyone sees it, including A.
        $this->actingAs($studentA)
            ->postJson(route('live.chat', $classRoom), ['text' => 'Hello everybody'])
            ->assertStatus(201);

        // An offer from the teacher to B only: only the teacher and B see it.
        $this->actingAs($teacher)
            ->postJson(route('live.signal', $classRoom), [
                'type' => 'offer',
                'to' => $studentB->id,
                'payload' => ['sdp' => 'v=0'],
            ])
            ->assertStatus(201);

        // B receives the broadcast and the offer addressed to them.
        $this->actingAs($studentB)
            ->getJson(route('live.signals', $classRoom))
            ->assertOk()
            ->assertJsonCount(2, 'messages');

        // A only receives their own broadcast, never the offer to B.
        $this->actingAs($studentA)
            ->getJson(route('live.signals', $classRoom))
            ->assertOk()
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.type', 'chat')
            ->assertJsonPath('messages.0.from', $studentA->id);

        // The teacher receives the broadcast and their own offer to B.
        $this->actingAs($teacher)
            ->getJson(route('live.signals', $classRoom))
            ->assertOk()
            ->assertJsonCount(2, 'messages');
    }

    public function test_the_signal_endpoint_still_rejects_a_non_signalling_type(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        $classRoom = ClassRoom::factory()->live()->create(['teacher_id' => $teacher->id]);
        $classRoom->students()->attach($student->id);

        // "bye" is a server-side message: it may not be forged through signal.
        $this->actingAs($teacher)
            ->postJson(route('live.signal', $classRoom), [
                'type' => 'bye',
                'payload' => [],
            ])
            ->assertStatus(422);
    }
}
