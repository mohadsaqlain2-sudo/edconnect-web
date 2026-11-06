<?php

namespace App\Console\Commands;

use App\Models\ClassRoom;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

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
        // A class that was started today has to be considered too, even when
        // its planned slot belongs to another day (the teacher may start
        // early), so the actual start date is part of the filter as well. The
        // exact moment is then compared in PHP, which behaves the same on
        // MySQL and on the SQLite database used by the tests.
        $candidates = ClassRoom::query()
            ->where('status', ClassRoom::STATUS_LIVE)
            ->where(function (Builder $query): void {
                $query->whereDate('date', '<=', now()->toDateString())
                    ->orWhereDate('started_at', '<=', now()->toDateString());
            })
            ->get();

        $completed = 0;

        foreach ($candidates as $classRoom) {
            // The full duration counts from the real start, so a class the
            // teacher started late stays live instead of being closed at once.
            if ($classRoom->finishedAt()->isFuture()) {
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
