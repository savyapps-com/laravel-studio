<?php

namespace SavyApps\LaravelStudio\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use SavyApps\LaravelStudio\Console\FileMapping;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'studio:install
                            {--default : Install default starter}
                            {--minimal : Install minimal starter (future)}
                            {--all : Install everything without prompts}
                            {--no-examples : Skip example resources}
                            {--skip-migrations : Skip running migrations}
                            {--skip-seeders : Skip running seeders}
                            {--skip-npm : Skip npm install}
                            {--skip-dependencies : Skip dependency checking and installation}
                            {--force : Overwrite existing files}
                            {--dry-run : Preview what will be installed}';

    /**
     * The console command description.
     */
    protected $description = 'Install Laravel Studio starter pack';

    /**
     * Track errors during installation.
     */
    protected array $errors = [];

    /**
     * Track warnings during installation.
     */
    protected array $warnings = [];

    /**
     * Track successful steps.
     */
    protected array $successSteps = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->displayHeader();

        // Generate application key if not set
        $this->ensureApplicationKey();

        // Check and install required dependencies (unless skipped)
        if (! $this->option('skip-dependencies')) {
            $dependenciesSatisfied = $this->checkAndInstallDependencies();

            if (! $dependenciesSatisfied) {
                return self::FAILURE;
            }
        } else {
            $this->components->warn('Skipping dependency check (--skip-dependencies flag used)');
            $this->newLine();
        }

        // Determine starter type
        $starter = $this->determineStarter();

        if ($starter === 'none') {
            $this->components->info('Skipping starter installation.');

            return self::SUCCESS;
        }

        if ($starter === 'minimal') {
            $this->components->warn('Minimal starter is not yet available. Using default starter.');
            $starter = 'default';
        }

        $this->newLine();

        // Show what will be installed
        if ($this->option('dry-run')) {
            $this->components->info('DRY RUN MODE - No files will be copied');
            $this->newLine();
        }

        // Install backend files
        $installBackend = $this->option('all') || $this->confirm('Install backend files?', true);
        if ($installBackend) {
            try {
                $this->installBackendFiles($starter);
                if (! $this->option('dry-run')) {
                    $this->addSuccess('Backend files installed');
                }
            } catch (\Exception $e) {
                $this->addError(
                    'Failed to install backend files',
                    $e->getMessage(),
                    'Check file permissions and try again'
                );
            }
        }

        // Install frontend files
        $installFrontend = $this->option('all') || $this->confirm('Install frontend files?', true);
        if ($installFrontend) {
            try {
                $this->installFrontendFiles($starter);
                if (! $this->option('dry-run')) {
                    $this->addSuccess('Frontend files installed');
                }
            } catch (\Exception $e) {
                $this->addError(
                    'Failed to install frontend files',
                    $e->getMessage(),
                    'Check file permissions and try again'
                );
            }
        }

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->components->info('Dry run complete. No files were modified.');

            return self::SUCCESS;
        }

        $this->newLine();

        // Run migrations
        if (! $this->option('skip-migrations') && ($this->option('all') || $this->confirm('Run migrations?', true))) {
            try {
                // Check database connection before running migrations
                if ($this->checkDatabaseConnection()) {
                    $success = $this->components->task('Running migrations', function () {
                        try {
                            $this->call('migrate');

                            return true;
                        } catch (\Exception) {
                            return false;
                        }
                    });

                    if ($success) {
                        $this->addSuccess('Database migrations completed');
                    } else {
                        $this->addError(
                            'Migrations failed',
                            'Some migrations could not be executed',
                            'Run: php artisan migrate --force'
                        );
                    }
                } else {
                    $this->addWarning(
                        'Skipped migrations - database connection failed',
                        'Configure your database in .env and run: php artisan migrate'
                    );
                }
            } catch (\Exception $e) {
                $this->addError(
                    'Failed to run migrations',
                    $e->getMessage(),
                    'Configure database and run: php artisan migrate'
                );
            }
        }

        // Run seeders
        if (! $this->option('skip-seeders') && ($this->option('all') || $this->confirm('Run seeders?', true))) {
            try {
                $success = $this->components->task('Running seeders', function () {
                    try {
                        $this->call('db:seed');

                        return true;
                    } catch (\Exception) {
                        return false;
                    }
                });

                if ($success) {
                    $this->addSuccess('Database seeding completed');
                } else {
                    $this->addError(
                        'Seeding failed',
                        'Some seeders could not be executed',
                        'Run: php artisan db:seed --force'
                    );
                }
            } catch (\Exception $e) {
                $this->addError(
                    'Failed to run seeders',
                    $e->getMessage(),
                    'Run manually: php artisan db:seed'
                );
            }
        }

        // Install npm dependencies
        if (! $this->option('skip-npm') && ($this->option('all') || $this->confirm('Install frontend dependencies (npm install)?', true))) {
            try {
                $success = $this->components->task('Installing frontend dependencies', function () {
                    exec('npm install 2>&1', $output, $returnCode);

                    return $returnCode === 0;
                });

                if ($success) {
                    $this->addSuccess('NPM dependencies installed');
                } else {
                    $this->addWarning(
                        'NPM installation failed',
                        'Run manually: npm install'
                    );
                }
            } catch (\Exception $e) {
                $this->addWarning(
                    'Failed to install NPM dependencies',
                    'Run manually: npm install'
                );
            }
        }

        $this->newLine();

        // Verify critical configuration files
        $this->verifyConfiguration();

        $this->displayInstallationSummary();
        $this->newLine();

        if (empty($this->errors)) {
            $this->displaySuccess();
            $this->newLine();
            $this->displayNextSteps();

            return self::SUCCESS;
        } else {
            $this->displayErrors();
            $this->newLine();
            $this->displayNextSteps();

            return self::FAILURE;
        }
    }

    /**
     * Display the command header.
     */
    protected function displayHeader(): void
    {
        $this->components->info('Laravel Studio - Starter Pack Installation');
        $this->components->info('Version: 1.0.0');
    }

    /**
     * Ensure application key is set.
     */
    protected function ensureApplicationKey(): void
    {
        try {
            $appKey = config('app.key');

            if (empty($appKey) || $appKey === 'base64:') {
                $this->newLine();
                $this->components->warn('Application key is not set');

                if ($this->option('all') || $this->confirm('Generate application key?', true)) {
                    $this->components->task('Generating application key', function () {
                        $this->call('key:generate', ['--force' => true]);

                        return true;
                    });
                    $this->addSuccess('Application key generated successfully');
                    $this->newLine();
                } else {
                    $this->addWarning(
                        'Application key not set',
                        'Run: php artisan key:generate'
                    );
                    $this->newLine();
                }
            } else {
                $this->addSuccess('Application key is already set');
            }
        } catch (\Exception $e) {
            $this->addError(
                'Failed to generate application key',
                $e->getMessage(),
                'Run manually: php artisan key:generate'
            );
        }
    }

    /**
     * Check and install required dependencies.
     *
     * @return bool Returns true if all dependencies are satisfied, false otherwise
     */
    protected function checkAndInstallDependencies(): bool
    {
        try {
            $this->newLine();
            $this->components->info('Checking required dependencies...');

            // Required composer packages
            $requiredPackages = [
                'spatie/laravel-medialibrary' => '^11.0',
                'laravel/sanctum' => '^4.0',
            ];

            $missingPackages = [];

            foreach ($requiredPackages as $package => $version) {
                if (! $this->isComposerPackageInstalled($package)) {
                    $missingPackages[$package] = $version;
                }
            }

            if (! empty($missingPackages)) {
                $this->components->warn('Missing required composer packages:');
                foreach ($missingPackages as $package => $version) {
                    $this->components->bulletList(["$package:$version"]);
                }
                $this->newLine();

                if ($this->option('all') || $this->confirm('Install missing composer packages?', true)) {
                    $success = $this->installComposerPackages($missingPackages);

                    if (! $success) {
                        $this->components->error('Failed to install required packages. Cannot proceed with installation.');
                        $this->newLine();
                        $this->components->info('Please install the required packages manually:');
                        $this->line('  composer require '.implode(' ', array_map(
                            fn ($pkg, $ver) => "$pkg:$ver",
                            array_keys($missingPackages),
                            array_values($missingPackages)
                        )));
                        $this->newLine();

                        return false;
                    }
                } else {
                    $this->components->error('Required packages must be installed to proceed.');
                    $this->newLine();
                    $this->components->info('Please install the required packages:');
                    $this->line('  composer require '.implode(' ', array_map(
                        fn ($pkg, $ver) => "$pkg:$ver",
                        array_keys($missingPackages),
                        array_values($missingPackages)
                    )));
                    $this->newLine();

                    return false;
                }
            } else {
                $this->components->info('✓ All required composer packages are installed');
                $this->addSuccess('All required composer packages are installed');

                // Verify that required classes are loadable
                $this->verifyRequiredClasses();
            }

            // Publish vendor assets if needed
            $this->publishVendorAssets();

            $this->newLine();

            return true;
        } catch (\Exception $e) {
            $this->addError(
                'Failed to check dependencies',
                $e->getMessage(),
                'Run: composer dump-autoload && composer install'
            );

            return false;
        }
    }

    /**
     * Verify that required classes from packages are loadable.
     */
    protected function verifyRequiredClasses(): void
    {
        $requiredClasses = [
            'Laravel\Sanctum\HasApiTokens',
            'Spatie\MediaLibrary\HasMedia',
            'Spatie\MediaLibrary\InteractsWithMedia',
            'Spatie\MediaLibrary\MediaLibraryServiceProvider',
        ];

        $missingClasses = [];
        foreach ($requiredClasses as $class) {
            if (! class_exists($class) && ! trait_exists($class) && ! interface_exists($class)) {
                $missingClasses[] = $class;
            }
        }

        if (! empty($missingClasses)) {
            $this->newLine();
            $this->components->warn('Required classes not found in autoload. Regenerating autoload files...');

            $this->components->task('Regenerating autoload files', function () {
                exec('COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --no-interaction 2>&1', $output, $returnCode);

                return $returnCode === 0;
            });

            // Verify again after regenerating autoload
            $stillMissing = [];
            foreach ($requiredClasses as $class) {
                if (! class_exists($class) && ! trait_exists($class) && ! interface_exists($class)) {
                    $stillMissing[] = $class;
                }
            }

            if (! empty($stillMissing)) {
                $this->components->error('Failed to load required classes:');
                foreach ($stillMissing as $class) {
                    $this->components->bulletList([$class]);
                }
                $this->newLine();
                $this->components->error('Please run: composer dump-autoload && composer install');

                throw new \RuntimeException(
                    'Required classes could not be loaded: ' . implode(', ', $stillMissing)
                );
            }
        }
    }

    /**
     * Publish vendor assets for required packages.
     * Note: Media library migrations are NOT published here because the starter pack
     * includes its own media migration with proper Schema::hasTable checks.
     */
    protected function publishVendorAssets(): void
    {
        $this->newLine();

        // Publish Spatie Media Library config only (migrations are in starter pack)
        if (! File::exists(config_path('media-library.php'))) {
            $this->components->task('Publishing Spatie Media Library config', function () {
                $this->call('vendor:publish', [
                    '--provider' => 'Spatie\MediaLibrary\MediaLibraryServiceProvider',
                    '--tag' => 'medialibrary-config',
                ]);

                return true;
            });
        }

        // Note: We intentionally DO NOT publish medialibrary-migrations here
        // The starter pack already includes a more robust media migration
        // with Schema::hasTable and Schema::hasColumn checks

        // Publish Laravel Sanctum config only (migrations are in starter pack)
        if (! File::exists(config_path('sanctum.php'))) {
            $this->components->task('Publishing Laravel Sanctum config', function () {
                $this->call('vendor:publish', [
                    '--provider' => 'Laravel\Sanctum\SanctumServiceProvider',
                    '--tag' => 'sanctum-config',
                ]);

                return true;
            });
        }
    }

    /**
     * Check database connection.
     */
    protected function checkDatabaseConnection(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Exception $e) {
            $this->components->error('Database connection failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Check if a composer package is installed.
     */
    protected function isComposerPackageInstalled(string $package): bool
    {
        // Quick check - if vendor directory exists, package is installed
        $vendorPath = base_path("vendor/{$package}");
        if (is_dir($vendorPath)) {
            return true;
        }

        // Check composer.lock (most reliable - package is actually installed)
        $composerLock = base_path('composer.lock');

        if (file_exists($composerLock)) {
            $lockContent = @file_get_contents($composerLock);
            if ($lockContent !== false) {
                $lockData = json_decode($lockContent, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    // Check in packages
                    if (isset($lockData['packages']) && is_array($lockData['packages'])) {
                        foreach ($lockData['packages'] as $installedPackage) {
                            if (isset($installedPackage['name']) && $installedPackage['name'] === $package) {
                                return true;
                            }
                        }
                    }

                    // Check in packages-dev
                    if (isset($lockData['packages-dev']) && is_array($lockData['packages-dev'])) {
                        foreach ($lockData['packages-dev'] as $installedPackage) {
                            if (isset($installedPackage['name']) && $installedPackage['name'] === $package) {
                                return true;
                            }
                        }
                    }
                }
            }
        }

        // Fallback: check composer.json (package is required but may not be installed yet)
        $composerJson = base_path('composer.json');

        if (file_exists($composerJson)) {
            $jsonContent = @file_get_contents($composerJson);
            if ($jsonContent !== false) {
                $jsonData = json_decode($jsonContent, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    // Check in require
                    if (isset($jsonData['require'][$package])) {
                        return true;
                    }

                    // Check in require-dev
                    if (isset($jsonData['require-dev'][$package])) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Install missing composer packages.
     *
     * @return bool Returns true if packages were installed successfully, false otherwise
     */
    protected function installComposerPackages(array $packages): bool
    {
        try {
            $packageStrings = [];
            foreach ($packages as $package => $version) {
                $packageStrings[] = "$package:$version";
            }

            $packagesStr = implode(' ', $packageStrings);

            $success = $this->components->task('Installing composer packages', function () use ($packagesStr) {
                $command = "COMPOSER_ALLOW_SUPERUSER=1 composer require $packagesStr --no-interaction 2>&1";
                exec($command, $output, $returnCode);

                if ($returnCode !== 0) {
                    $this->components->error('Failed to install composer packages');
                    $this->line(implode("\n", $output));

                    return false;
                }

                return true;
            });

            if (! $success) {
                $this->addError(
                    'Failed to install composer packages',
                    'Composer installation failed',
                    'Run manually: composer require '.$packagesStr
                );

                return false;
            }

            // Regenerate autoload files after installing packages
            $this->components->task('Regenerating autoload files', function () {
                exec('COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --no-interaction 2>&1', $output, $returnCode);

                return $returnCode === 0;
            });

            // Clear Laravel caches to ensure new packages are recognized
            $this->components->task('Clearing application caches', function () {
                $this->call('config:clear');
                $this->call('cache:clear');
                $this->call('route:clear');
                $this->call('view:clear');

                return true;
            });

            $this->newLine();
            $this->components->info('✓ Composer packages installed successfully');
            $this->addSuccess('Composer packages installed successfully');

            return true;
        } catch (\Exception $e) {
            $this->addError(
                'Failed to install composer packages',
                $e->getMessage(),
                'Run manually: composer require spatie/laravel-medialibrary laravel/sanctum'
            );

            return false;
        }
    }

    /**
     * Determine which starter to install.
     */
    protected function determineStarter(): string
    {
        if ($this->option('all') || $this->option('default')) {
            return 'default';
        }

        if ($this->option('minimal')) {
            return 'minimal';
        }

        $choice = $this->components->choice(
            'Which starter would you like to install?',
            [
                'default' => 'Default (Full admin panel + auth + settings)',
                'minimal' => 'Minimal (Auth only - coming soon)',
                'none' => 'Skip (Use core package only)',
            ],
            'default'
        );

        return $choice;
    }

    /**
     * Install backend files.
     */
    protected function installBackendFiles(string $starter): void
    {
        $this->components->info('Installing backend files...');

        $files = FileMapping::getDefaultBackendFiles();
        $starterPath = __DIR__.'/../../../starters/'.$starter.'/backend';
        $copiedCount = 0;
        $skippedCount = 0;

        foreach ($files as $source => $destination) {
            $sourcePath = $starterPath.'/'.$source;
            $destPath = base_path($destination);

            if (! File::exists($sourcePath)) {
                $this->components->warn("Source not found: {$source}");

                continue;
            }

            if ($this->option('dry-run')) {
                $this->components->twoColumnDetail(
                    $destination,
                    '<fg=gray>would copy</>'
                );

                continue;
            }

            // Check if destination exists
            if (File::exists($destPath) && ! $this->option('force')) {
                $protected = in_array($destination, FileMapping::getProtectedFiles());

                if ($protected) {
                    $overwrite = $this->confirm("⚠️  Protected file {$destination} exists. Overwrite?", false);
                } else {
                    $overwrite = $this->confirm("File {$destination} exists. Overwrite?", false);
                }

                if (! $overwrite) {
                    $this->components->twoColumnDetail(
                        $destination,
                        '<fg=yellow>skipped</>'
                    );
                    $skippedCount++;

                    continue;
                }
            }

            // Ensure parent directory exists
            $parentDir = dirname($destPath);
            if (! File::isDirectory($parentDir)) {
                File::makeDirectory($parentDir, 0755, true);
            }

            // Copy file or directory
            if (File::isDirectory($sourcePath)) {
                File::copyDirectory($sourcePath, $destPath);
            } else {
                File::copy($sourcePath, $destPath);
            }

            $this->components->twoColumnDetail(
                $destination,
                '<fg=green>copied</>'
            );
            $copiedCount++;
        }

        $this->newLine();
        $this->components->info("Backend: {$copiedCount} copied, {$skippedCount} skipped");
    }

    /**
     * Install frontend files.
     */
    protected function installFrontendFiles(string $starter): void
    {
        $this->components->info('Installing frontend files...');

        $files = FileMapping::getDefaultFrontendFiles();
        $starterPath = __DIR__.'/../../../starters/'.$starter.'/frontend';
        $copiedCount = 0;
        $skippedCount = 0;

        foreach ($files as $source => $destination) {
            $sourcePath = $starterPath.'/'.$source;
            $destPath = base_path($destination);

            if (! File::exists($sourcePath)) {
                $this->components->warn("Source not found: {$source}");

                continue;
            }

            if ($this->option('dry-run')) {
                $this->components->twoColumnDetail(
                    $destination,
                    '<fg=gray>would copy</>'
                );

                continue;
            }

            // Check if destination exists
            if (File::exists($destPath) && ! $this->option('force')) {
                $protected = in_array($destination, FileMapping::getProtectedFiles());

                if ($protected) {
                    $overwrite = $this->confirm("⚠️  Protected file {$destination} exists. Overwrite?", false);
                } else {
                    $overwrite = $this->confirm("File {$destination} exists. Overwrite?", false);
                }

                if (! $overwrite) {
                    $this->components->twoColumnDetail(
                        $destination,
                        '<fg=yellow>skipped</>'
                    );
                    $skippedCount++;

                    continue;
                }
            }

            // Ensure parent directory exists
            $parentDir = dirname($destPath);
            if (! File::isDirectory($parentDir)) {
                File::makeDirectory($parentDir, 0755, true);
            }

            // Copy file or directory
            if (File::isDirectory($sourcePath)) {
                File::copyDirectory($sourcePath, $destPath);
            } else {
                File::copy($sourcePath, $destPath);
            }

            $this->components->twoColumnDetail(
                $destination,
                '<fg=green>copied</>'
            );
            $copiedCount++;
        }

        $this->newLine();
        $this->components->info("Frontend: {$copiedCount} copied, {$skippedCount} skipped");
    }

    /**
     * Display success message.
     */
    protected function displaySuccess(): void
    {
        $this->components->info('✨ Starter pack installed successfully!');
    }

    /**
     * Display next steps.
     */
    protected function displayNextSteps(): void
    {
        $this->components->info('Next steps:');
        $this->components->bulletList([
            'Review the installed files in your project',
            'Update .env with your configuration (DB, mail, etc.)',
            'Run: npm run build',
            'Register your resources in config/studio.php',
            'Start your dev server: php artisan serve',
            'Visit your application and log in!',
        ]);

        $this->newLine();
        $this->line('<fg=gray>💡 Tip: All published files are now part of your project.</>');
        $this->line('<fg=gray>   You can customize them freely without affecting package updates.</>');
    }

    /**
     * Add a success step.
     */
    protected function addSuccess(string $message): void
    {
        $this->successSteps[] = $message;
    }

    /**
     * Add a warning.
     */
    protected function addWarning(string $title, string $solution): void
    {
        $this->warnings[] = [
            'title' => $title,
            'solution' => $solution,
        ];
    }

    /**
     * Add an error.
     */
    protected function addError(string $title, string $message, string $solution): void
    {
        $this->errors[] = [
            'title' => $title,
            'message' => $message,
            'solution' => $solution,
        ];
    }

    /**
     * Verify critical configuration after installation.
     */
    protected function verifyConfiguration(): void
    {
        $bootstrapPath = base_path('bootstrap/app.php');

        if (! File::exists($bootstrapPath)) {
            $this->addError(
                'Missing bootstrap/app.php',
                'The bootstrap configuration file is missing',
                'Copy from: packages/laravel-studio/starters/default/backend/bootstrap/app.php'
            );

            return;
        }

        $bootstrapContent = File::get($bootstrapPath);

        // Check if API routes are registered
        if (! str_contains($bootstrapContent, "api: __DIR__.'/../routes/api.php'")) {
            $this->addWarning(
                'API routes not registered in bootstrap/app.php',
                'Add this line to withRouting(): api: __DIR__.\'/../routes/api.php\''
            );

            $this->components->warn('⚠️  IMPORTANT: Your bootstrap/app.php is missing API route registration!');
            $this->newLine();

            if ($this->confirm('Would you like to fix this automatically?', true)) {
                try {
                    $this->fixBootstrapAppFile($bootstrapPath);
                    $this->components->info('✓ bootstrap/app.php has been updated with API routes configuration');
                    $this->addSuccess('bootstrap/app.php configuration fixed');

                    // Remove the warning since we fixed it
                    $this->warnings = array_filter($this->warnings, function ($warning) {
                        return ! str_contains($warning['title'], 'API routes not registered');
                    });
                } catch (\Exception $e) {
                    $this->addError(
                        'Failed to fix bootstrap/app.php',
                        $e->getMessage(),
                        'Manually copy from: packages/laravel-studio/starters/default/backend/bootstrap/app.php'
                    );
                }
            } else {
                $this->components->warn('Please update bootstrap/app.php manually to include API routes');
            }
        }
    }

    /**
     * Fix the bootstrap/app.php file with correct configuration.
     */
    protected function fixBootstrapAppFile(string $bootstrapPath): void
    {
        $starterBootstrap = __DIR__.'/../../../starters/default/backend/bootstrap/app.php';

        if (! File::exists($starterBootstrap)) {
            throw new \Exception('Source bootstrap/app.php not found in starter package');
        }

        // Backup the existing file
        $backupPath = $bootstrapPath.'.backup';
        File::copy($bootstrapPath, $backupPath);

        try {
            // Copy the correct bootstrap file
            File::copy($starterBootstrap, $bootstrapPath);

            $this->components->info("✓ Created backup: {$backupPath}");
        } catch (\Exception $e) {
            // Restore backup on failure
            if (File::exists($backupPath)) {
                File::copy($backupPath, $bootstrapPath);
            }
            throw $e;
        }
    }

    /**
     * Display installation summary.
     */
    protected function displayInstallationSummary(): void
    {
        $this->components->info('Installation Summary:');
        $this->newLine();

        // Display successful steps
        if (! empty($this->successSteps)) {
            $this->components->info('✓ Completed Steps:');
            foreach ($this->successSteps as $step) {
                $this->line("  <fg=green>✓</> {$step}");
            }
            $this->newLine();
        }

        // Display warnings
        if (! empty($this->warnings)) {
            $this->components->warn('⚠ Warnings ('.count($this->warnings).'):');
            foreach ($this->warnings as $warning) {
                $this->line("  <fg=yellow>⚠</> {$warning['title']}");
                $this->line("    <fg=gray>→ {$warning['solution']}</>");
            }
            $this->newLine();
        }

        // Display errors count (detailed errors shown later)
        if (! empty($this->errors)) {
            $this->components->error('✗ Errors ('.count($this->errors).'):');
            foreach ($this->errors as $error) {
                $this->line("  <fg=red>✗</> {$error['title']}");
            }
            $this->newLine();
        }
    }

    /**
     * Display detailed errors with solutions.
     */
    protected function displayErrors(): void
    {
        $this->components->error('Installation completed with errors:');
        $this->newLine();

        foreach ($this->errors as $index => $error) {
            $this->line('<fg=red>Error '.($index + 1).":</> {$error['title']}");
            $this->line("<fg=gray>Details:</> {$error['message']}");
            $this->line("<fg=yellow>Solution:</> {$error['solution']}");
            $this->newLine();
        }

        $this->components->warn('Please fix the errors above and complete the installation manually.');
    }
}
