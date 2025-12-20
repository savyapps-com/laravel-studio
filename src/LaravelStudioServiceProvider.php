<?php

namespace SavyApps\LaravelStudio;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use SavyApps\LaravelStudio\Console\Commands\CleanupActivitiesCommand;
use SavyApps\LaravelStudio\Console\Commands\DoctorCommand;
use SavyApps\LaravelStudio\Console\Commands\InstallCommand;
use SavyApps\LaravelStudio\Console\Commands\MakeActionCommand;
use SavyApps\LaravelStudio\Console\Commands\MakeFieldCommand;
use SavyApps\LaravelStudio\Console\Commands\MakeFilterCommand;
use SavyApps\LaravelStudio\Console\Commands\MakePanelCommand;
use SavyApps\LaravelStudio\Console\Commands\MakeResourceCommand;
use SavyApps\LaravelStudio\Console\Commands\PanelCommand;
use SavyApps\LaravelStudio\Console\Commands\ResetApplicationCommand;
use SavyApps\LaravelStudio\Console\Commands\SyncPermissionsCommand;
use SavyApps\LaravelStudio\Http\Middleware\CheckResourcePermission;
use SavyApps\LaravelStudio\Http\Middleware\EnsureUserCanAccessPanel;
use SavyApps\LaravelStudio\Http\Middleware\TokenFromQueryParameter;
use SavyApps\LaravelStudio\Models\Permission;
use SavyApps\LaravelStudio\Models\Role;
use SavyApps\LaravelStudio\Observers\PermissionObserver;
use SavyApps\LaravelStudio\Observers\RoleObserver;
use SavyApps\LaravelStudio\Policies\PermissionPolicy;
use SavyApps\LaravelStudio\Policies\RolePolicy;
use SavyApps\LaravelStudio\Policies\UserPolicy;
use SavyApps\LaravelStudio\Services\ActivityService;
use SavyApps\LaravelStudio\Services\AuthorizationService;
use SavyApps\LaravelStudio\Services\BladeTemplateSecurityService;
use SavyApps\LaravelStudio\Services\CardService;
use SavyApps\LaravelStudio\Services\CommentService;
use SavyApps\LaravelStudio\Services\EmailTemplateService;
use SavyApps\LaravelStudio\Services\EmailVariableRegistry;
use SavyApps\LaravelStudio\Services\GlobalSearchService;
use SavyApps\LaravelStudio\Services\PanelService;
use SavyApps\LaravelStudio\Services\SettingsService;
use SavyApps\LaravelStudio\Support\ConfigValidator;

class LaravelStudioServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge package config with application config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/studio.php',
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

        // Register SettingsService as singleton
        $this->app->singleton(SettingsService::class, function ($app) {
            return new SettingsService();
        });

        // Register BladeTemplateSecurityService as singleton
        $this->app->singleton(BladeTemplateSecurityService::class, function ($app) {
            return new BladeTemplateSecurityService();
        });

        // Register EmailVariableRegistry as singleton
        $this->app->singleton(EmailVariableRegistry::class, function ($app) {
            return new EmailVariableRegistry();
        });

        // Register EmailTemplateService as singleton
        $this->app->singleton(EmailTemplateService::class, function ($app) {
            return new EmailTemplateService(
                $app->make(BladeTemplateSecurityService::class)
            );
        });

        // Register CommentService as singleton
        $this->app->singleton(CommentService::class, function ($app) {
            return new CommentService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Validate configuration (in non-production, logs warnings; throws on critical errors)
        $this->validateConfiguration();

        // Register middleware aliases
        $this->registerMiddleware();

        // Register model observers
        $this->registerObservers();

        // Register policies
        $this->registerPolicies();

        // Load package routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        // Load package migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Register authorization gates
        $this->registerAuthorizationGates();

        // Publish configuration file
        $this->publishes([
            __DIR__ . '/../config/studio.php' => config_path('studio.php'),
        ], 'studio-config');

        // Publish compiled frontend assets
        $this->publishes([
            __DIR__ . '/../dist' => public_path('vendor/laravel-studio'),
        ], 'studio-assets');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'studio-migrations');

        // Register Artisan commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                CleanupActivitiesCommand::class,
                DoctorCommand::class,
                InstallCommand::class,
                MakeActionCommand::class,
                MakeFieldCommand::class,
                MakeFilterCommand::class,
                MakePanelCommand::class,
                MakeResourceCommand::class,
                PanelCommand::class,
                ResetApplicationCommand::class,
                SyncPermissionsCommand::class,
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

        // Register token from query parameter middleware alias (for SSE/EventSource)
        $router->aliasMiddleware('token.query', TokenFromQueryParameter::class);
    }

    /**
     * Register model observers for cache invalidation.
     */
    protected function registerObservers(): void
    {
        // Only register observers if authorization is enabled
        if (!config('studio.authorization.enabled', true)) {
            return;
        }

        Role::observe(RoleObserver::class);
        Permission::observe(PermissionObserver::class);
    }

    /**
     * Register authorization policies.
     */
    protected function registerPolicies(): void
    {
        // Only register policies if authorization is enabled
        if (!config('studio.authorization.enabled', true)) {
            return;
        }

        // Register policies from config or use defaults
        $policies = config('studio.authorization.policies', []);

        // Register Role policy
        $roleModel = config('studio.authorization.models.role', Role::class);
        $rolePolicy = $policies['role'] ?? RolePolicy::class;
        Gate::policy($roleModel, $rolePolicy);

        // Register Permission policy
        $permissionModel = config('studio.authorization.models.permission', Permission::class);
        $permissionPolicy = $policies['permission'] ?? PermissionPolicy::class;
        Gate::policy($permissionModel, $permissionPolicy);

        // Register User policy if a user model is configured
        $userModel = config('studio.authorization.models.user');
        if ($userModel && class_exists($userModel)) {
            $userPolicy = $policies['user'] ?? UserPolicy::class;
            Gate::policy($userModel, $userPolicy);
        }
    }

    /**
     * Register authorization gates for permissions.
     */
    protected function registerAuthorizationGates(): void
    {
        // Only register gates if authorization is enabled
        if (!config('studio.authorization.enabled', true)) {
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

    /**
     * Validate the package configuration.
     *
     * Validates configuration values and logs warnings for non-critical issues.
     * Throws an exception for critical configuration errors.
     */
    protected function validateConfiguration(): void
    {
        // Skip validation during testing unless explicitly enabled
        if ($this->app->runningUnitTests() && !config('studio.validate_config_in_tests', false)) {
            return;
        }

        // Skip validation if explicitly disabled
        if (!config('studio.validate_config', true)) {
            return;
        }

        try {
            $validator = new ConfigValidator();
            $validator->validate();
        } catch (\InvalidArgumentException $e) {
            // Re-throw critical errors
            throw $e;
        } catch (\Exception $e) {
            // Log but don't crash for unexpected validation errors
            \Illuminate\Support\Facades\Log::warning(
                'Laravel Studio configuration validation failed unexpectedly: ' . $e->getMessage()
            );
        }
    }
}
