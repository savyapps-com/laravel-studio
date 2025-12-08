<?php

namespace SavyApps\LaravelStudio\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeActionCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'studio:make-action {name : The name of the action}
                                                {--force : Overwrite existing action}';

    /**
     * The console command description.
     */
    protected $description = 'Create a new Laravel Studio action class';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name');

        // Determine action class name
        $actionClassName = Str::studly($name);
        if (! Str::endsWith($actionClassName, 'Action')) {
            $actionClassName .= 'Action';
        }

        // Determine paths and namespaces
        $actionPath = app_path('Resources/Actions/'.$actionClassName.'.php');
        $actionNamespace = 'App\\Resources\\Actions';

        // Check if file already exists
        if (file_exists($actionPath) && ! $this->option('force')) {
            $this->error("Action [{$actionClassName}] already exists!");
            $this->info('Use --force to overwrite.');

            return self::FAILURE;
        }

        // Generate label
        $label = Str::headline(Str::beforeLast($name, 'Action'));

        // Load stub and replace placeholders
        $stub = file_get_contents(__DIR__.'/../../../stubs/action.stub');
        $stub = str_replace(
            [
                '{{ namespace }}',
                '{{ class }}',
                '{{ label }}',
            ],
            [
                $actionNamespace,
                $actionClassName,
                $label,
            ],
            $stub
        );

        // Create directory if it doesn't exist
        $directory = dirname($actionPath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Write the file
        file_put_contents($actionPath, $stub);

        $this->components->info("Action [{$actionClassName}] created successfully.");

        // Show next steps
        $this->newLine();
        $this->components->info('Next steps:');
        $this->components->bulletList([
            "Implement the handle() method to define your action logic",
            "Optionally add fields() if the action requires user input",
            "Set requiresConfirmation to true if the action needs confirmation",
            "Add the action to your Resource's actions() method",
        ]);

        // Show example usage
        $this->newLine();
        $this->line('<fg=gray>// In your Resource class</>');
        $this->line('<fg=gray>public function actions(): array {</>');
        $this->line('<fg=yellow>    return [</>');
        $this->line("<fg=yellow>        {$actionClassName}::make(),</>");
        $this->line('<fg=yellow>    ];</>');
        $this->line('<fg=gray>}</>');

        return self::SUCCESS;
    }
}
