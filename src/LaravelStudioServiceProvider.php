<?php

namespace SavyApps\LaravelStudio;

use Illuminate\Support\ServiceProvider;
use SavyApps\LaravelStudio\Console\Commands\InstallCommand;
use SavyApps\LaravelStudio\Console\Commands\MakeResourceCommand;
use SavyApps\LaravelStudio\Console\Commands\MakeFilterCommand;
use SavyApps\LaravelStudio\Console\Commands\MakeActionCommand;

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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Load package routes
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

        // Publish configuration file
        $this->publishes([
            __DIR__.'/../config/studio.php' => config_path('studio.php'),
        ], 'studio-config');

        // Publish compiled frontend assets
        $this->publishes([
            __DIR__.'/../dist' => public_path('vendor/laravel-studio'),
        ], 'studio-assets');

        // Register Artisan commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                MakeResourceCommand::class,
                MakeFilterCommand::class,
                MakeActionCommand::class,
            ]);
        }
    }
}
