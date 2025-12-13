<?php

namespace SavyApps\LaravelStudio\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

use function Laravel\Prompts\info;
use function Laravel\Prompts\select;
use function Laravel\Prompts\table;
use function Laravel\Prompts\warning;

class PanelCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'studio:panel
                            {action? : Action to perform (list, export)}';

    /**
     * The console command description.
     */
    protected $description = 'Manage Laravel Studio panels';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action') ?? $this->promptForAction();

        return match($action) {
            'list' => $this->listPanels(),
            'export' => $this->exportPanels(),
            default => $this->error('Invalid action. Available actions: list, export'),
        };
    }

    /**
     * Prompt for action selection.
     */
    protected function promptForAction(): string
    {
        return select(
            label: 'What would you like to do?',
            options: [
                'list' => 'List all panels',
                'export' => 'Export panels from database to config format',
            ],
            default: 'list'
        );
    }

    /**
     * List all panels from configuration.
     */
    protected function listPanels(): int
    {
        $panels = config('studio.panels', []);

        if (empty($panels)) {
            warning('No panels configured in config/studio.php');
            return self::SUCCESS;
        }

        info('Configured Panels:');
        $this->newLine();

        $rows = [];
        foreach ($panels as $key => $config) {
            $roles = $config['roles'] ?? [$config['role'] ?? '-'];
            $rows[] = [
                $key,
                $config['label'] ?? '-',
                $config['path'] ?? '-',
                implode(', ', (array) $roles),
                count($config['resources'] ?? []),
                count($config['features'] ?? []),
            ];
        }

        table(
            ['Key', 'Label', 'Path', 'Roles', 'Resources', 'Features'],
            $rows
        );

        $this->newLine();
        $this->components->info('Total panels: ' . count($panels));

        return self::SUCCESS;
    }

    /**
     * Export panels from database to config format.
     */
    protected function exportPanels(): int
    {
        // Check if panels table exists
        if (!Schema::hasTable('panels')) {
            warning('Panels table does not exist in the database.');
            $this->newLine();
            $this->info('Panels are now managed via config/studio.php only.');
            return self::SUCCESS;
        }

        // Check if Panel model exists
        if (!class_exists('SavyApps\\LaravelStudio\\Models\\Panel')) {
            $this->error('Panel model not found. Cannot export from database.');
            return self::FAILURE;
        }

        try {
            $panels = \SavyApps\LaravelStudio\Models\Panel::all();

            if ($panels->isEmpty()) {
                warning('No panels found in database.');
                return self::SUCCESS;
            }

            info('Exporting panels from database:');
            $this->newLine();

            foreach ($panels as $panel) {
                $config = $panel->toConfig();

                $this->components->twoColumnDetail(
                    "<fg=cyan>{$panel->key}</>",
                    $panel->label
                );

                // Generate PHP array code
                $configCode = $this->generateConfigCode($config);

                $this->newLine();
                $this->line("<fg=yellow>Add this to config/studio.php under 'panels':</>");
                $this->line($configCode);
                $this->newLine();
                $this->line(str_repeat('-', 80));
            }

            $this->newLine();
            $this->components->warn('Copy the above configurations to config/studio.php manually.');
            $this->info('Then run: php artisan config:clear');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Export failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Generate PHP array code for a panel config.
     */
    protected function generateConfigCode(array $config, int $indent = 0): string
    {
        $spaces = str_repeat('    ', $indent);
        $lines = [];

        foreach ($config as $key => $value) {
            $keyStr = is_numeric($key) ? $key : "'{$key}'";

            if (is_array($value)) {
                if (empty($value)) {
                    $lines[] = "{$spaces}{$keyStr} => [],";
                } else {
                    $valueStr = $this->generateConfigCode($value, $indent + 1);
                    $lines[] = "{$spaces}{$keyStr} => [";
                    $lines[] = rtrim($valueStr, ',');
                    $lines[] = "{$spaces}],";
                }
            } elseif (is_bool($value)) {
                $valueStr = $value ? 'true' : 'false';
                $lines[] = "{$spaces}{$keyStr} => {$valueStr},";
            } elseif (is_null($value)) {
                $lines[] = "{$spaces}{$keyStr} => null,";
            } elseif (is_numeric($value)) {
                $lines[] = "{$spaces}{$keyStr} => {$value},";
            } else {
                $valueStr = "'" . addslashes($value) . "'";
                $lines[] = "{$spaces}{$keyStr} => {$valueStr},";
            }
        }

        return implode("\n", $lines);
    }
}
