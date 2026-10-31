<?php

namespace App\Console\Commands;

use App\Models\ClassRoom;
use Illuminate\Console\Command;

/**
 * FR-21: close a running class automatically.
 *
 * A class that is still marked "live" long after start time + duration has
 * passed would keep its Join button and its "Live now" badge forever, so the
 * scheduler (see routes/console.php) runs this command every minute and moves
 * such classes to "completed".
 *
 * Only classes that are live are touched: a scheduled class that nobody started
 * stays scheduled until its teacher decides what to do with it.
 */
class CompleteFinishedClasses extends Command
{
    /**
     * @var string
     */
    protected $signature = 'classes:complete-finished';

    /**
     * @var string
     */
    protected $description = 'Mark live classes as completed once their start time plus duration has passed';

    public function handle(): int
    {
        // Cheap pre-filter: a class starting tomorrow cannot have ended yet.
        // The exact moment is then compared in PHP, which behaves the same on
        // MySQL and on the SQLite database used by the tests.
        $candidates = ClassRoom::query()
            ->where('status', ClassRoom::STATUS_LIVE)
            ->whereDate('date', '<=', now()->toDateString())
            ->get();

        $completed = 0;

        foreach ($candidates as $classRoom) {
            if ($classRoom->endsAt()->isFuture()) {
                continue;   // still running
            }

            // Use the same transition whitelist as the teacher's buttons (FR-21).
            if (! $classRoom->canTransitionTo(ClassRoom::STATUS_COMPLETED)) {
                continue;
            }

            $classRoom->forceFill(['status' => ClassRoom::STATUS_COMPLETED])->save();

            $completed++;
        }

        $this->info("Marked {$completed} finished class(es) as completed.");

        return self::SUCCESS;
    }
}
