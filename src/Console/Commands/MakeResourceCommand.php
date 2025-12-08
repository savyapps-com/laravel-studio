<?php

namespace SavyApps\LaravelStudio\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeResourceCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'studio:make-resource {name : The name of the resource}
                                                  {--model= : The model class name}
                                                  {--force : Overwrite existing resource}';

    /**
     * The console command description.
     */
    protected $description = 'Create a new Laravel Studio resource class';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $modelName = $this->option('model') ?: $this->guessModelName($name);

        // Determine resource class name
        $resourceClassName = Str::studly($name);
        if (! Str::endsWith($resourceClassName, 'Resource')) {
            $resourceClassName .= 'Resource';
        }

        // Determine paths and namespaces
        $resourcePath = app_path('Resources/'.$resourceClassName.'.php');
        $resourceNamespace = 'App\\Resources';
        $modelNamespace = 'App\\Models';

        // Check if file already exists
        if (file_exists($resourcePath) && ! $this->option('force')) {
            $this->error("Resource [{$resourceClassName}] already exists!");
            $this->info('Use --force to overwrite.');

            return self::FAILURE;
        }

        // Generate labels
        $labelSingular = Str::headline(Str::singular($name));
        $labelPlural = Str::headline(Str::plural($name));

        // Load stub and replace placeholders
        $stub = file_get_contents(__DIR__.'/../../../stubs/resource.stub');
        $stub = str_replace(
            [
                '{{ namespace }}',
                '{{ class }}',
                '{{ model }}',
                '{{ modelNamespace }}',
                '{{ labelSingular }}',
                '{{ labelPlural }}',
            ],
            [
                $resourceNamespace,
                $resourceClassName,
                $modelName,
                $modelNamespace,
                $labelSingular,
                $labelPlural,
            ],
            $stub
        );

        // Create directory if it doesn't exist
        $directory = dirname($resourcePath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Write the file
        file_put_contents($resourcePath, $stub);

        $this->components->info("Resource [{$resourceClassName}] created successfully.");

        // Show next steps
        $this->newLine();
        $this->components->info('Next steps:');
        $this->components->bulletList([
            "Register your resource in config/studio.php",
            "Add fields to indexFields(), showFields(), and formFields() methods",
            "Optionally add filters() and actions()",
        ]);

        // Show example registration
        $this->newLine();
        $this->line('<fg=gray>// config/studio.php</>');
        $this->line("<fg=gray>'resources' => [</>");
        $resourceKey = Str::plural(Str::kebab(Str::beforeLast($name, 'Resource')));
        $this->line("<fg=yellow>    '{$resourceKey}' => \\{$resourceNamespace}\\{$resourceClassName}::class,</>");
        $this->line('<fg=gray>],</>');

        return self::SUCCESS;
    }

    /**
     * Guess the model name from the resource name.
     */
    protected function guessModelName(string $name): string
    {
        // Remove 'Resource' suffix if present
        $name = Str::beforeLast($name, 'Resource');

        // Convert to singular studly case
        return Str::studly(Str::singular($name));
    }
}
