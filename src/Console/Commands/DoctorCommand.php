<?php

namespace SavyApps\LaravelStudio\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class DoctorCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'studio:doctor
                            {--fix : Attempt to automatically fix issues}
                            {--verbose : Show detailed information}';

    /**
     * The console command description.
     */
    protected $description = 'Check Laravel Studio installation health and diagnose issues';

    /**
     * Track check results.
     */
    protected array $checks = [];

    /**
     * Track issues found.
     */
    protected int $issuesFound = 0;

    /**
     * Track issues fixed.
     */
    protected int $issuesFixed = 0;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->newLine();
        $this->components->info('Laravel Studio Health Check');
        $this->components->info('============================');
        $this->newLine();

        // Run all checks
        $this->checkPackageInstallation();
        $this->checkConfigFile();
        $this->checkDatabaseConnection();
        $this->checkRequiredTables();
        $this->checkRequiredModels();
        $this->checkUserModel();
        $this->checkSanctumConfiguration();
        $this->checkFilePermissions();
        $this->checkCacheStatus();
        $this->checkFrontendAssets();

        // Display summary
        $this->newLine();
        $this->displaySummary();

        return $this->issuesFound === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Check if package is properly installed.
     */
    protected function checkPackageInstallation(): void
    {
        $this->runCheck('Package Installation', function () {
            // Check if package exists in vendor
            $packagePath = base_path('vendor/savyapps-com/laravel-studio');

            if (! is_dir($packagePath)) {
                return [
                    'status' => 'fail',
                    'message' => 'Package not found in vendor directory',
                    'solution' => 'Run: composer require savyapps-com/laravel-studio',
                ];
            }

            // Check service provider is registered
            $providers = config('app.providers', []);
            $autoDiscover = file_exists(base_path('bootstrap/cache/packages.php'));

            if (! $autoDiscover && ! in_array('SavyApps\LaravelStudio\LaravelStudioServiceProvider', $providers)) {
                return [
                    'status' => 'warn',
                    'message' => 'Service provider may not be registered',
                    'solution' => 'Laravel should auto-discover the package. Run: php artisan package:discover',
                ];
            }

            return ['status' => 'pass'];
        });
    }

    /**
     * Check if config file exists.
     */
    protected function checkConfigFile(): void
    {
        $this->runCheck('Configuration File', function () {
            $configPath = config_path('studio.php');

            if (! File::exists($configPath)) {
                return [
                    'status' => 'fail',
                    'message' => 'Config file not published',
                    'solution' => 'Run: php artisan vendor:publish --tag=laravel-studio-config',
                    'fixable' => true,
                    'fix' => function () {
                        $this->callSilently('vendor:publish', [
                            '--tag' => 'laravel-studio-config',
                        ]);
                        return File::exists(config_path('studio.php'));
                    },
                ];
            }

            // Check if config has required keys
            $config = config('studio');
            $requiredKeys = ['panels', 'resources'];
            $missingKeys = [];

            foreach ($requiredKeys as $key) {
                if (! isset($config[$key])) {
                    $missingKeys[] = $key;
                }
            }

            if (! empty($missingKeys)) {
                return [
                    'status' => 'warn',
                    'message' => 'Missing config keys: ' . implode(', ', $missingKeys),
                    'solution' => 'Check config/studio.php for missing sections',
                ];
            }

            return ['status' => 'pass'];
        });
    }

    /**
     * Check database connection.
     */
    protected function checkDatabaseConnection(): void
    {
        $this->runCheck('Database Connection', function () {
            try {
                DB::connection()->getPdo();

                $driver = DB::connection()->getDriverName();
                $database = DB::connection()->getDatabaseName();

                if ($this->option('verbose')) {
                    $this->line("  <fg=gray>Driver: {$driver}, Database: {$database}</>");
                }

                return ['status' => 'pass'];
            } catch (\Exception $e) {
                return [
                    'status' => 'fail',
                    'message' => 'Cannot connect to database: ' . $e->getMessage(),
                    'solution' => 'Check your .env database configuration (DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD)',
                ];
            }
        });
    }

    /**
     * Check if required database tables exist.
     */
    protected function checkRequiredTables(): void
    {
        $this->runCheck('Required Database Tables', function () {
            try {
                $requiredTables = [
                    'users',
                    'roles',
                    'permissions',
                    'role_permissions',
                    'personal_access_tokens',
                ];

                $missingTables = [];

                foreach ($requiredTables as $table) {
                    if (! Schema::hasTable($table)) {
                        $missingTables[] = $table;
                    }
                }

                if (! empty($missingTables)) {
                    return [
                        'status' => 'fail',
                        'message' => 'Missing tables: ' . implode(', ', $missingTables),
                        'solution' => 'Run: php artisan migrate',
                        'fixable' => true,
                        'fix' => function () {
                            $this->call('migrate', ['--force' => true]);
                            return true;
                        },
                    ];
                }

                if ($this->option('verbose')) {
                    $this->line('  <fg=gray>All ' . count($requiredTables) . ' required tables exist</>');
                }

                return ['status' => 'pass'];
            } catch (\Exception $e) {
                return [
                    'status' => 'fail',
                    'message' => 'Cannot check tables: ' . $e->getMessage(),
                    'solution' => 'Ensure database connection is working',
                ];
            }
        });
    }

    /**
     * Check if required models exist.
     */
    protected function checkRequiredModels(): void
    {
        $this->runCheck('Required Models', function () {
            // User model is in the app, Role and Permission are in the package
            $requiredModels = [
                'App\Models\User' => 'User model (app/Models/User.php)',
                'SavyApps\LaravelStudio\Models\Role' => 'Role model (package)',
                'SavyApps\LaravelStudio\Models\Permission' => 'Permission model (package)',
            ];

            $missingModels = [];

            foreach ($requiredModels as $class => $description) {
                if (! class_exists($class)) {
                    $missingModels[] = $description;
                }
            }

            if (! empty($missingModels)) {
                return [
                    'status' => 'fail',
                    'message' => 'Missing models: ' . implode(', ', $missingModels),
                    'solution' => 'Ensure the package is properly installed. Run: composer dump-autoload',
                ];
            }

            return ['status' => 'pass'];
        });
    }

    /**
     * Check if User model has required traits.
     */
    protected function checkUserModel(): void
    {
        $this->runCheck('User Model Configuration', function () {
            if (! class_exists('App\Models\User')) {
                return [
                    'status' => 'skip',
                    'message' => 'User model not found',
                ];
            }

            $user = new \App\Models\User();
            $issues = [];

            // Check for HasApiTokens trait (Sanctum)
            if (! method_exists($user, 'tokens')) {
                $issues[] = 'Missing HasApiTokens trait (Laravel Sanctum)';
            }

            // Check for HasRoles trait
            if (! method_exists($user, 'roles')) {
                $issues[] = 'Missing HasRoles trait';
            }

            // Check for HasMedia trait
            if (! method_exists($user, 'getMedia')) {
                $issues[] = 'Missing HasMedia interface/InteractsWithMedia trait (Spatie Media Library)';
            }

            if (! empty($issues)) {
                return [
                    'status' => 'fail',
                    'message' => implode('; ', $issues),
                    'solution' => 'Add required traits to App\Models\User. See: vendor/savyapps-com/laravel-studio/starters/default/backend/app/Models/User.php',
                ];
            }

            return ['status' => 'pass'];
        });
    }

    /**
     * Check Sanctum configuration.
     */
    protected function checkSanctumConfiguration(): void
    {
        $this->runCheck('Sanctum Configuration', function () {
            // Check if Sanctum config exists
            if (! File::exists(config_path('sanctum.php'))) {
                return [
                    'status' => 'fail',
                    'message' => 'Sanctum config not published',
                    'solution' => 'Run: php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"',
                    'fixable' => true,
                    'fix' => function () {
                        $this->callSilently('vendor:publish', [
                            '--provider' => 'Laravel\Sanctum\SanctumServiceProvider',
                        ]);
                        return File::exists(config_path('sanctum.php'));
                    },
                ];
            }

            // Check stateful domains
            $statefulDomains = config('sanctum.stateful', []);

            if (empty($statefulDomains)) {
                return [
                    'status' => 'warn',
                    'message' => 'No stateful domains configured',
                    'solution' => 'Add SANCTUM_STATEFUL_DOMAINS to your .env file',
                ];
            }

            if ($this->option('verbose')) {
                $this->line('  <fg=gray>Stateful domains: ' . implode(', ', (array) $statefulDomains) . '</>');
            }

            return ['status' => 'pass'];
        });
    }

    /**
     * Check file permissions.
     */
    protected function checkFilePermissions(): void
    {
        $this->runCheck('File Permissions', function () {
            $writablePaths = [
                storage_path('logs'),
                storage_path('framework/cache'),
                storage_path('framework/sessions'),
                storage_path('framework/views'),
                base_path('bootstrap/cache'),
            ];

            $issues = [];

            foreach ($writablePaths as $path) {
                if (File::exists($path) && ! is_writable($path)) {
                    $issues[] = str_replace(base_path() . '/', '', $path);
                }
            }

            if (! empty($issues)) {
                return [
                    'status' => 'fail',
                    'message' => 'Not writable: ' . implode(', ', $issues),
                    'solution' => 'Run: chmod -R 775 storage bootstrap/cache',
                ];
            }

            return ['status' => 'pass'];
        });
    }

    /**
     * Check cache status.
     */
    protected function checkCacheStatus(): void
    {
        $this->runCheck('Cache Status', function () {
            $issues = [];

            // Check if config is cached
            $configCached = File::exists(base_path('bootstrap/cache/config.php'));

            // Check if routes are cached
            $routesCached = File::exists(base_path('bootstrap/cache/routes-v7.php'));

            // In development, caching can cause issues
            if (config('app.debug') && ($configCached || $routesCached)) {
                $cachedItems = [];
                if ($configCached) {
                    $cachedItems[] = 'config';
                }
                if ($routesCached) {
                    $cachedItems[] = 'routes';
                }

                return [
                    'status' => 'warn',
                    'message' => 'Caches active in debug mode: ' . implode(', ', $cachedItems),
                    'solution' => 'Consider clearing caches in development: php artisan optimize:clear',
                ];
            }

            // In production, caching is recommended
            if (! config('app.debug') && ! $configCached) {
                return [
                    'status' => 'warn',
                    'message' => 'Caching not enabled in production',
                    'solution' => 'Run: php artisan optimize',
                ];
            }

            if ($this->option('verbose')) {
                $status = [];
                $status[] = 'Config: ' . ($configCached ? 'cached' : 'not cached');
                $status[] = 'Routes: ' . ($routesCached ? 'cached' : 'not cached');
                $this->line('  <fg=gray>' . implode(', ', $status) . '</>');
            }

            return ['status' => 'pass'];
        });
    }

    /**
     * Check frontend assets.
     */
    protected function checkFrontendAssets(): void
    {
        $this->runCheck('Frontend Assets', function () {
            // Check if package.json exists
            if (! File::exists(base_path('package.json'))) {
                return [
                    'status' => 'fail',
                    'message' => 'package.json not found',
                    'solution' => 'Run: php artisan studio:install to copy frontend files',
                ];
            }

            // Check if node_modules exists
            if (! is_dir(base_path('node_modules'))) {
                return [
                    'status' => 'fail',
                    'message' => 'node_modules not found',
                    'solution' => 'Run: npm install',
                ];
            }

            // Check if build output exists
            $buildPath = public_path('build');
            $manifestPath = public_path('build/manifest.json');

            if (! is_dir($buildPath) || ! File::exists($manifestPath)) {
                return [
                    'status' => 'fail',
                    'message' => 'Frontend not built',
                    'solution' => 'Run: npm run build',
                ];
            }

            // Check manifest age
            $manifestAge = time() - filemtime($manifestPath);
            $packageJsonAge = time() - filemtime(base_path('package.json'));

            if ($packageJsonAge < $manifestAge) {
                return [
                    'status' => 'warn',
                    'message' => 'package.json modified after last build',
                    'solution' => 'Consider running: npm install && npm run build',
                ];
            }

            if ($this->option('verbose')) {
                $this->line('  <fg=gray>Build age: ' . round($manifestAge / 3600, 1) . ' hours</>');
            }

            return ['status' => 'pass'];
        });
    }

    /**
     * Run a check and record results.
     */
    protected function runCheck(string $name, callable $check): void
    {
        $result = $check();

        $status = $result['status'] ?? 'pass';
        $message = $result['message'] ?? '';
        $solution = $result['solution'] ?? '';
        $fixable = $result['fixable'] ?? false;
        $fix = $result['fix'] ?? null;

        // Display check result
        $icon = match ($status) {
            'pass' => '<fg=green>✓</>',
            'fail' => '<fg=red>✗</>',
            'warn' => '<fg=yellow>⚠</>',
            'skip' => '<fg=gray>○</>',
            default => '<fg=gray>?</>',
        };

        $statusColor = match ($status) {
            'pass' => 'green',
            'fail' => 'red',
            'warn' => 'yellow',
            default => 'gray',
        };

        $this->line("{$icon} {$name}");

        if ($status !== 'pass' && $message) {
            $this->line("  <fg={$statusColor}>{$message}</>");
        }

        if ($status !== 'pass' && $solution) {
            $this->line("  <fg=gray>→ {$solution}</>");
        }

        // Track issues
        if ($status === 'fail') {
            $this->issuesFound++;

            // Attempt fix if requested
            if ($this->option('fix') && $fixable && $fix) {
                $this->line('  <fg=cyan>Attempting to fix...</>');
                try {
                    if ($fix()) {
                        $this->line('  <fg=green>✓ Fixed!</>');
                        $this->issuesFixed++;
                        $this->issuesFound--;
                    } else {
                        $this->line('  <fg=red>✗ Could not fix automatically</>');
                    }
                } catch (\Exception $e) {
                    $this->line("  <fg=red>✗ Fix failed: {$e->getMessage()}</>");
                }
            }
        } elseif ($status === 'warn') {
            $this->issuesFound++;
        }

        $this->checks[] = [
            'name' => $name,
            'status' => $status,
            'message' => $message,
            'solution' => $solution,
        ];
    }

    /**
     * Display summary of all checks.
     */
    protected function displaySummary(): void
    {
        $this->components->info('Summary');
        $this->line(str_repeat('-', 40));

        $passed = count(array_filter($this->checks, fn ($c) => $c['status'] === 'pass'));
        $failed = count(array_filter($this->checks, fn ($c) => $c['status'] === 'fail'));
        $warnings = count(array_filter($this->checks, fn ($c) => $c['status'] === 'warn'));
        $skipped = count(array_filter($this->checks, fn ($c) => $c['status'] === 'skip'));

        $this->line("<fg=green>Passed:</> {$passed}");

        if ($failed > 0) {
            $this->line("<fg=red>Failed:</> {$failed}");
        }

        if ($warnings > 0) {
            $this->line("<fg=yellow>Warnings:</> {$warnings}");
        }

        if ($skipped > 0) {
            $this->line("<fg=gray>Skipped:</> {$skipped}");
        }

        if ($this->option('fix') && $this->issuesFixed > 0) {
            $this->newLine();
            $this->line("<fg=green>Fixed:</> {$this->issuesFixed} issue(s)");
        }

        $this->newLine();

        if ($this->issuesFound === 0) {
            $this->components->info('✨ All checks passed! Laravel Studio is healthy.');
        } else {
            $remaining = $this->issuesFound - $this->issuesFixed;
            if ($remaining > 0) {
                $this->components->warn("{$remaining} issue(s) need attention.");

                if (! $this->option('fix')) {
                    $this->line('<fg=gray>Tip: Run with --fix to attempt automatic fixes</>');
                }
            }
        }
    }
}
