<?php

namespace SavyApps\LaravelStudio\Console\Commands;

use Illuminate\Console\Command;
use SavyApps\LaravelStudio\Models\Permission;
use SavyApps\LaravelStudio\Services\AuthorizationService;

class SyncPermissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'studio:sync-permissions
                            {--fresh : Delete all permissions before syncing}
                            {--clean : Remove orphaned permissions not in any resource}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync permissions from all registered resources';

    /**
     * Execute the console command.
     */
    public function handle(AuthorizationService $service): int
    {
        if ($this->option('fresh')) {
            if ($this->confirm('This will delete ALL existing permissions and their role assignments. Continue?', false)) {
                $this->warn('Deleting all existing permissions...');
                Permission::query()->delete();
            } else {
                $this->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        $this->info('Syncing permissions from resources...');

        $synced = $service->syncPermissions();

        if (empty($synced)) {
            $this->warn('No permissions found in registered resources.');
            $this->info('Make sure your resources define a permissions() method.');
            return Command::SUCCESS;
        }

        $this->info('Synced ' . count($synced) . ' permissions:');
        $this->newLine();

        // Group by resource
        $grouped = collect($synced)->groupBy(function ($permission) {
            return explode('.', $permission)[0] ?? 'other';
        });

        foreach ($grouped as $resource => $permissions) {
            $this->line("  <fg=cyan>{$resource}</>");
            foreach ($permissions as $permission) {
                $this->line("    - {$permission}");
            }
        }

        if ($this->option('clean')) {
            $this->newLine();
            $deleted = $service->cleanOrphanedPermissions();
            if ($deleted > 0) {
                $this->info("Cleaned up {$deleted} orphaned permissions.");
            } else {
                $this->info('No orphaned permissions to clean up.');
            }
        }

        $this->newLine();
        $this->info('Done!');

        return Command::SUCCESS;
    }
}
