<?php

namespace SavyApps\LaravelStudio\Console\Commands;

use Illuminate\Console\Command;
use SavyApps\LaravelStudio\Services\ActivityService;

class CleanupActivitiesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'studio:cleanup-activities
                            {--days= : Number of days to keep (overrides config)}
                            {--dry-run : Show how many would be deleted without deleting}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up old activity log entries';

    /**
     * Execute the console command.
     */
    public function handle(ActivityService $activityService): int
    {
        $days = $this->option('days') ?? config('studio.activity_log.cleanup_days', 90);
        $dryRun = $this->option('dry-run');

        if ($days <= 0) {
            $this->warn('Cleanup is disabled (days is 0 or negative). Set cleanup_days in config or use --days option.');
            return Command::SUCCESS;
        }

        $cutoffDate = now()->subDays($days);

        if ($dryRun) {
            $count = \SavyApps\LaravelStudio\Models\Activity::where('created_at', '<', $cutoffDate)->count();
            $this->info("Would delete {$count} activities older than {$days} days (before {$cutoffDate->toDateString()})");
            return Command::SUCCESS;
        }

        $this->info("Cleaning up activities older than {$days} days...");

        $count = $activityService->cleanup($days);

        if ($count > 0) {
            $this->info("Successfully deleted {$count} old activity records.");
        } else {
            $this->info('No activities to clean up.');
        }

        return Command::SUCCESS;
    }
}
