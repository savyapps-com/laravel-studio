<?php

namespace SavyApps\LaravelStudio\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeFilterCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'studio:make-filter {name : The name of the filter}
                                                {--force : Overwrite existing filter}';

    /**
     * The console command description.
     */
    protected $description = 'Create a new Laravel Studio filter class';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name');

        // Determine filter class name
        $filterClassName = Str::studly($name);
        if (! Str::endsWith($filterClassName, 'Filter')) {
            $filterClassName .= 'Filter';
        }

        // Determine paths and namespaces
        $filterPath = app_path('Resources/Filters/'.$filterClassName.'.php');
        $filterNamespace = 'App\\Resources\\Filters';

        // Check if file already exists
        if (file_exists($filterPath) && ! $this->option('force')) {
            $this->error("Filter [{$filterClassName}] already exists!");
            $this->info('Use --force to overwrite.');

            return self::FAILURE;
        }

        // Load stub and replace placeholders
        $stub = file_get_contents(__DIR__.'/../../../stubs/filter.stub');
        $stub = str_replace(
            [
                '{{ namespace }}',
                '{{ class }}',
            ],
            [
                $filterNamespace,
                $filterClassName,
            ],
            $stub
        );

        // Create directory if it doesn't exist
        $directory = dirname($filterPath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Write the file
        file_put_contents($filterPath, $stub);

        $this->components->info("Filter [{$filterClassName}] created successfully.");

        // Show next steps
        $this->newLine();
        $this->components->info('Next steps:');
        $this->components->bulletList([
            "Implement the apply() method to define your filter logic",
            "Define available options in the options() method",
            "Add the filter to your Resource's filters() method",
        ]);

        // Show example usage
        $this->newLine();
        $this->line('<fg=gray>// In your Resource class</>');
        $this->line('<fg=gray>public function filters(): array {</>');
        $this->line("<fg=yellow>    return [</>");
        $this->line("<fg=yellow>        {$filterClassName}::make(),</>");
        $this->line('<fg=yellow>    ];</>');
        $this->line('<fg=gray>}</>');

        return self::SUCCESS;
    }
}
