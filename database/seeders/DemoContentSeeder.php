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
