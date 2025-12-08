<?php

namespace SavyApps\LaravelStudio;

use Illuminate\Support\ServiceProvider;
use SavyApps\LaravelStudio\Console\Commands\InstallCommand;
use SavyApps\LaravelStudio\Console\Commands\MakeResourceCommand;
use SavyApps\LaravelStudio\Console\Commands\MakeFilterCommand;
use SavyApps\LaravelStudio\Console\Commands\MakeActionCommand;
use SavyApps\LaravelStudio\Console\Commands\SyncPermissionsCommand;
use SavyApps\LaravelStudio\Console\Commands\CleanupActivitiesCommand;
use SavyApps\LaravelStudio\Http\Middleware\CheckResourcePermission;
use SavyApps\LaravelStudio\Http\Middleware\EnsureUserCanAccessPanel;
use SavyApps\LaravelStudio\Services\ActivityService;
use SavyApps\LaravelStudio\Services\AuthorizationService;
use SavyApps\LaravelStudio\Services\CardService;
use SavyApps\LaravelStudio\Services\GlobalSearchService;
use SavyApps\LaravelStudio\Services\PanelService;

class LaravelStudioServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge package config with application config
        $this->mergeConfigFrom(
            __DIR__.'/../config/studio.php',
            'studio'
        );

        // Register PanelService as singleton
        $this->app->singleton(PanelService::class, function ($app) {
            return new PanelService();
        });

        // Register AuthorizationService as singleton
        $this->app->singleton(AuthorizationService::class, function ($app) {
            return new AuthorizationService();
        });

        // Register ActivityService as singleton
        $this->app->singleton(ActivityService::class, function ($app) {
            return new ActivityService();
        });

        // Register GlobalSearchService as singleton
        $this->app->singleton(GlobalSearchService::class, function ($app) {
            return new GlobalSearchService();
        });

        // Register CardService as singleton
        $this->app->singleton(CardService::class, function ($app) {
            return new CardService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register middleware aliases
        $this->registerMiddleware();

        // Load package routes
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

        // Load package migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Register authorization gates
        $this->registerAuthorizationGates();

        // Publish configuration file
        $this->publishes([
            __DIR__.'/../config/studio.php' => config_path('studio.php'),
        ], 'studio-config');

        // Publish compiled frontend assets
        $this->publishes([
            __DIR__.'/../dist' => public_path('vendor/laravel-studio'),
        ], 'studio-assets');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'studio-migrations');

        // Register Artisan commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                MakeResourceCommand::class,
                MakeFilterCommand::class,
                MakeActionCommand::class,
                SyncPermissionsCommand::class,
                CleanupActivitiesCommand::class,
            ]);
        }
    }

    /**
     * Register middleware aliases.
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];

        // Register panel middleware alias
        $router->aliasMiddleware('panel', EnsureUserCanAccessPanel::class);

        // Register permission middleware alias
        $router->aliasMiddleware('permission', CheckResourcePermission::class);

        // Legacy aliases for backward compatibility
        $router->aliasMiddleware('studio.panel', EnsureUserCanAccessPanel::class);
        $router->aliasMiddleware('studio.permission', CheckResourcePermission::class);
    }

    /**
     * Register authorization gates for permissions.
     */
    protected function registerAuthorizationGates(): void
    {
        if (!config('studio.authorization.register_gates', true)) {
            return;
        }

        // Defer gate registration until after the app has booted
        $this->app->booted(function () {
            try {
                $service = $this->app->make(AuthorizationService::class);
                $service->registerGates();
            } catch (\Exception $e) {
                // Silently fail if database is not ready
            }
        });
    }
}
