<?php

namespace App\Providers;

use App\Models\User;
use App\Observers\UserObserver;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        User::observe(UserObserver::class);

        // Register event listeners
        Event::listen(
            \Illuminate\Auth\Events\Registered::class,
            \SavyApps\LaravelStudio\Listeners\SendWelcomeEmail::class,
        );

        // Disable implicit route model binding for resources
        Route::bind('user', fn (string $value) => $value);

        // Register panel route pattern constraint (validates against config panels)
        Route::pattern('panel', $this->getPanelPattern());
    }

    /**
     * Get the regex pattern for valid panel keys from config.
     */
    protected function getPanelPattern(): string
    {
        $panelKeys = array_keys(config('studio.panels', []));

        if (! empty($panelKeys)) {
            return implode('|', $panelKeys);
        }

        // Fallback for fresh install
        return 'admin';
    }
}
