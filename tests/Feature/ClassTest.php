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

        // Not live yet: the friendly "not started" page, not the room (FR-20).
        $this->actingAs($student)
            ->get(route('classes.room', $scheduled))
            ->assertOk()
            ->assertSee('This class has not started yet')
            ->assertDontSee('data-jitsi', false);

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
        $response->assertSee(config('services.jitsi.domain'));
        $response->assertSee('data-jitsi', false);
        $response->assertSee('data-jitsi-retry', false);
        $response->assertSee($student->name);
        $response->assertSee($student->email);
        // The class title is the Jitsi subject and the user's avatar travels too.
        $response->assertSee('data-subject="'.e($classRoom->title).'"', false);
        $response->assertSee('data-avatar="'.e(url($student->photoUrl())).'"', false);
        // The hangup redirect lands on the class page with a flash message.
        $response->assertSee(route('classes.leave', $classRoom));
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

    /* -----------------------------------------------------------------
     | Duration limits and the automatic end of a class (FR-21)
     * ----------------------------------------------------------------- */

    /**
     * A class lasts between 15 minutes and 4 hours.
     */
    public function test_the_duration_must_be_between_fifteen_and_two_hundred_forty_minutes(): void
    {
        $this->travelTo(Carbon::parse('2026-06-15 12:00:00'));

        $teacher = User::factory()->teacher()->create();

        $payload = [
            'title' => 'Duration check',
            'date' => '2026-06-20',
            'time' => '14:00',
        ];

        // 14 minutes is too short, 241 minutes too long.
        foreach ([14, 241] as $duration) {
            $this->actingAs($teacher)
                ->post(route('classes.store'), ['duration' => $duration] + $payload)
                ->assertSessionHasErrors('duration');
        }

        // Both boundaries are accepted.
        foreach ([15, 240] as $duration) {
            $this->actingAs($teacher)
                ->post(route('classes.store'), ['duration' => $duration] + $payload)
                ->assertSessionHasNoErrors();
        }

        $this->assertDatabaseCount('classes', 2);
    }

    /**
     * classes:complete-finished closes a running class once its duration is over.
     */
    public function test_a_live_class_is_completed_once_its_end_time_has_passed(): void
    {
        $this->travelTo(Carbon::parse('2026-06-15 12:00:00'));

        $teacher = User::factory()->teacher()->create();

        // Started two hours ago and 60 minutes long: long over.
        $finished = ClassRoom::factory()->live()->create([
            'teacher_id' => $teacher->id,
            'date' => '2026-06-15',
            'time' => '10:00',
            'duration' => 60,
        ]);

        // Started ten minutes ago and 60 minutes long: still running.
        $running = ClassRoom::factory()->live()->create([
            'teacher_id' => $teacher->id,
            'date' => '2026-06-15',
            'time' => '11:50',
            'duration' => 60,
        ]);

        // A scheduled class is the teacher's business, not the command's.
        $scheduled = ClassRoom::factory()->scheduled()->create([
            'teacher_id' => $teacher->id,
            'date' => '2026-06-15',
            'time' => '09:00',
            'duration' => 60,
        ]);

        $this->artisan('classes:complete-finished')->assertSuccessful();

        $this->assertSame(ClassRoom::STATUS_COMPLETED, $finished->refresh()->status);
        $this->assertSame(ClassRoom::STATUS_LIVE, $running->refresh()->status);
        $this->assertSame(ClassRoom::STATUS_SCHEDULED, $scheduled->refresh()->status);
    }

    /**
     * The Carbon timing helper words a date as "Starts in 2h 15m" / "Live now".
     */
    public function test_the_timing_helper_describes_when_a_class_happens(): void
    {
        $this->travelTo(Carbon::parse('2026-06-15 12:00:00'));

        $teacher = User::factory()->teacher()->create();

        // Starts in exactly 2 hours and 15 minutes.
        $soon = ClassRoom::factory()->scheduled()->create([
            'teacher_id' => $teacher->id,
            'date' => '2026-06-15',
            'time' => '14:15',
            'duration' => 60,
        ]);

        $this->assertSame('Starts in 2h 15m', $soon->timelineLabel());

        $live = ClassRoom::factory()->live()->create([
            'teacher_id' => $teacher->id,
            'date' => '2026-06-15',
            'time' => '11:30',
            'duration' => 60,
        ]);

        $this->assertSame('Live now', $live->timelineLabel());

        $done = ClassRoom::factory()->completed()->create([
            'teacher_id' => $teacher->id,
            'date' => '2026-06-15',
            'time' => '09:00',
            'duration' => 60,
        ]);

        $this->assertSame('Ended', $done->timelineLabel());

        // The hint is rendered on the class cards, not only available in PHP.
        $this->actingAs($teacher)
            ->get(route('classes.index'))
            ->assertOk()
            ->assertSee('Starts in 2h 15m')
            ->assertSee('Live now')
            ->assertSee('Ended');

        // The same wording is available as a Blade accessor.
        $this->assertSame('Starts in 2h 15m', $soon->timeline_label);
        $this->assertSame('Live now', $live->timeline_label);
    }

    /* -----------------------------------------------------------------
     | Phase 5 audit additions: filters, strict transitions and leaving
     * ----------------------------------------------------------------- */

    /**
     * The teacher's own list can be narrowed to one status (FR-15).
     */
    public function test_a_teacher_can_filter_their_classes_by_status(): void
    {
        $this->travelTo(Carbon::parse('2026-06-15 12:00:00'));

        $teacher = User::factory()->teacher()->create();

        $scheduled = ClassRoom::factory()->scheduled()->create([
            'teacher_id' => $teacher->id,
            'title' => 'Scheduled algebra',
        ]);
        $live = ClassRoom::factory()->live()->create([
            'teacher_id' => $teacher->id,
            'title' => 'Live physics',
        ]);
        $completed = ClassRoom::factory()->completed()->create([
            'teacher_id' => $teacher->id,
            'title' => 'Completed history',
        ]);

        $response = $this->actingAs($teacher)
            ->get(route('classes.index', ['status' => ClassRoom::STATUS_SCHEDULED]));

        $response->assertOk();
        $response->assertSee('Scheduled algebra');
        $response->assertDontSee('Live physics');
        $response->assertDontSee('Completed history');
        $this->assertSame(
            [$scheduled->id],
            $response->viewData('classes')->pluck('id')->all()
        );
        $this->assertSame(ClassRoom::STATUS_SCHEDULED, $response->viewData('status'));

        // An unknown status is ignored instead of returning an empty list.
        $all = $this->actingAs($teacher)
            ->get(route('classes.index', ['status' => 'paused']));

        $this->assertEqualsCanonicalizing(
            [$scheduled->id, $live->id, $completed->id],
            $all->viewData('classes')->pluck('id')->all()
        );
        $this->assertSame('', $all->viewData('status'));
    }

    /**
     * Students can search the subject and filter on one teacher (FR-17).
     */
    public function test_a_student_can_search_by_subject_and_filter_by_teacher(): void
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

        $physics = ClassRoom::factory()->scheduled()->create([
            'teacher_id' => $david->id,
            'title' => 'Newton laws',
            'subject' => 'Physics',
        ]);

        // Search now also looks inside the subject column.
        $this->actingAs($student)
            ->get(route('classes.discover', ['q' => 'Mathematics']))
            ->assertSee('Algebra basics')
            ->assertDontSee('Newton laws');

        // Teacher filter: only Maria's classes.
        $byTeacher = $this->actingAs($student)
            ->get(route('classes.discover', ['teacher' => $maria->id]));

        $byTeacher->assertOk();
        $byTeacher->assertSee('Algebra basics');
        $byTeacher->assertDontSee('Newton laws');
        $byTeacher->assertSee('Maria Rossi');
        $byTeacher->assertSee('David Chen'); // still available in the dropdown
        $this->assertSame([$algebra->id], $byTeacher->viewData('classes')->pluck('id')->all());

        // The teacher drop down only offers teachers who actually teach.
        $this->assertSame(
            [$maria->id, $david->id],
            $byTeacher->viewData('teachers')->pluck('id')->sort()->values()->all()
        );

        // A nonsense teacher id is ignored, not a 500.
        $this->actingAs($student)
            ->get(route('classes.discover', ['teacher' => 'abc']))
            ->assertOk()
            ->assertSee('Algebra basics')
            ->assertSee('Newton laws');

        // The physics filter still works together with the teacher filter.
        $this->actingAs($student)
            ->get(route('classes.discover', ['subject' => 'Physics', 'teacher' => $maria->id]))
            ->assertOk()
            ->assertDontSee('Newton laws');

        $this->assertNotNull($physics);
    }

    /**
     * Only scheduled -> live -> completed is accepted (FR-21).
     */
    public function test_only_the_scheduled_live_completed_chain_is_allowed(): void
    {
        $teacher = User::factory()->teacher()->create();

        $scheduled = ClassRoom::factory()->scheduled()->create(['teacher_id' => $teacher->id]);
        $live = ClassRoom::factory()->live()->create(['teacher_id' => $teacher->id]);
        $completed = ClassRoom::factory()->completed()->create(['teacher_id' => $teacher->id]);

        // A scheduled class cannot jump straight to completed.
        $this->actingAs($teacher)
            ->patch(route('classes.status', $scheduled), ['status' => ClassRoom::STATUS_COMPLETED])
            ->assertSessionHas('error');
        $this->assertSame(ClassRoom::STATUS_SCHEDULED, $scheduled->refresh()->status);

        // A live class cannot go backwards.
        $this->actingAs($teacher)
            ->patch(route('classes.status', $live), ['status' => ClassRoom::STATUS_SCHEDULED])
            ->assertSessionHas('error');
        $this->assertSame(ClassRoom::STATUS_LIVE, $live->refresh()->status);

        // A completed class cannot be reopened.
        $this->actingAs($teacher)
            ->patch(route('classes.status', $completed), ['status' => ClassRoom::STATUS_LIVE])
            ->assertSessionHas('error');
        $this->assertSame(ClassRoom::STATUS_COMPLETED, $completed->refresh()->status);

        // The legal chain still works.
        $this->actingAs($teacher)
            ->patch(route('classes.status', $scheduled), ['status' => ClassRoom::STATUS_LIVE])
            ->assertRedirect();
        $this->assertSame(ClassRoom::STATUS_LIVE, $scheduled->refresh()->status);

        $this->actingAs($teacher)
            ->patch(route('classes.status', $scheduled), ['status' => ClassRoom::STATUS_COMPLETED])
            ->assertRedirect();
        $this->assertSame(ClassRoom::STATUS_COMPLETED, $scheduled->refresh()->status);
    }

    /**
     * The hangup redirect returns to the class page with a flash message.
     */
    public function test_leaving_the_room_redirects_back_with_a_flash_message(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $outsider = User::factory()->student()->create();

        $classRoom = ClassRoom::factory()->live()->create(['teacher_id' => $teacher->id]);
        $classRoom->students()->attach($student->id);

        $this->actingAs($student)
            ->get(route('classes.leave', $classRoom))
            ->assertRedirect(route('classes.show', $classRoom))
            ->assertSessionHas('success');

        // The teacher can leave too; an unenrolled student cannot use the route.
        $this->actingAs($teacher)->get(route('classes.leave', $classRoom))->assertRedirect();
        $this->actingAs($outsider)->get(route('classes.leave', $classRoom))->assertForbidden();
    }

    /**
     * The dashboards expose working join links and count the right classes.
     */
    public function test_the_dashboards_offer_working_join_buttons_and_upcoming_counts(): void
    {
        $this->travelTo(Carbon::parse('2026-06-15 12:00:00'));

        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        $live = ClassRoom::factory()->live()->create([
            'teacher_id' => $teacher->id,
            'title' => 'Live now class',
            'date' => '2026-06-15',
            'time' => '11:00',
            'duration' => 120,
        ]);

        $future = ClassRoom::factory()->scheduled()->create([
            'teacher_id' => $teacher->id,
            'title' => 'Future class',
            'date' => '2026-06-20',
            'time' => '10:00',
            'duration' => 60,
        ]);

        $live->students()->attach($student->id);
        $future->students()->attach($student->id);

        // The student sees a real Join live link to the room.
        $studentDashboard = $this->actingAs($student)->get(route('student.dashboard'));

        $studentDashboard->assertOk();
        $studentDashboard->assertSee(route('classes.room', $live), false);
        $studentDashboard->assertSee('Join live');
        $this->assertSame(1, $studentDashboard->viewData('stats')['upcoming']);

        // The teacher sees the live room link and the correct upcoming count.
        $teacherDashboard = $this->actingAs($teacher)->get(route('teacher.dashboard'));

        $teacherDashboard->assertOk();
        $teacherDashboard->assertSee(route('classes.room', $live), false);
        $this->assertSame(1, $teacherDashboard->viewData('stats')['upcoming']);
    }
}
