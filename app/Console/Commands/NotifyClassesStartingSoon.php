<?php

namespace App\Console\Commands;

use App\Models\ClassRoom;
use App\Notifications\ClassStartingSoon;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * FR-23: remind the teacher and the enrolled students a few minutes before a
 * class starts.
 *
 * The scheduler calls this command every minute (see routes/console.php).
 * The "starting_soon_notified_at" column is the flag that makes sure a class
 * is announced exactly once: it is written in the same pass that queues the
 * notifications, and only rows with a NULL flag are picked up.
 */
class NotifyClassesStartingSoon extends Command
{
    /**
     * @var string
     */
    protected $signature = 'classes:notify-starting-soon {--minutes=15 : How far ahead to look}';

    /**
     * @var string
     */
    protected $description = 'Notify the teacher and enrolled students of classes starting in the next 15 minutes';

    public function handle(): int
    {
        $from = now();
        $until = now()->addMinutes((int) $this->option('minutes'));

        // Cheapest possible pre-filter: only scheduled, visible classes on the
        // right day that were never announced. The exact minute is then checked
        // in PHP, which behaves the same on MySQL and on SQLite (tests).
        $candidates = ClassRoom::query()
            ->visible()
            ->where('status', ClassRoom::STATUS_SCHEDULED)
            ->whereNull('starting_soon_notified_at')
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $until->toDateString())
            ->with(['teacher', 'students'])
            ->get();

        $notified = 0;

        foreach ($candidates as $classRoom) {
            if (! $this->startsWithin($classRoom, $from, $until)) {
                continue;
            }

            // Enrolled students first, then the teacher who runs the class.
            foreach ($classRoom->students as $student) {
                $student->notify(new ClassStartingSoon($classRoom));
            }

            $classRoom->teacher?->notify(new ClassStartingSoon($classRoom));

            // One shot: the flag keeps the reminder from being sent again.
            $classRoom->forceFill(['starting_soon_notified_at' => now()])->save();

            $notified++;
        }

        $this->info("Starting-soon notifications queued for {$notified} class(es).");

        return self::SUCCESS;
    }

    /**
     * Does the class start between $from and $until?
     */
    private function startsWithin(ClassRoom $classRoom, Carbon $from, Carbon $until): bool
    {
        $startsAt = $classRoom->startsAt();

        return $startsAt->greaterThanOrEqualTo($from) && $startsAt->lessThanOrEqualTo($until);
    }
}
