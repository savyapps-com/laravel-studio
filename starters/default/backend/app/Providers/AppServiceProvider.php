<?php

namespace App\Providers;

use App\Models\User;
use App\Observers\PanelObserver;
use App\Observers\UserObserver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use SavyApps\LaravelStudio\Models\Panel;

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
        Panel::observe(PanelObserver::class);

        // Register event listeners
        Event::listen(
            \Illuminate\Auth\Events\Registered::class,
            \App\Listeners\SendWelcomeEmail::class,
        );

        // Disable implicit route model binding for resources
        Route::bind('user', fn (string $value) => $value);

        // Register panel route pattern constraint (validates against active panels)
        Route::pattern('panel', $this->getPanelPattern());
    }

    /**
     * Get the regex pattern for valid panel keys.
     */
    protected function getPanelPattern(): string
    {
        // Get active panels from database if table exists
        if (Schema::hasTable('panels')) {
            $panelKeys = Cache::remember('active_panel_keys', 3600, function () {
                return DB::table('panels')
                    ->where('is_active', true)
                    ->pluck('key')
                    ->toArray();
            });

            if (! empty($panelKeys)) {
                return implode('|', $panelKeys);
            }
        }

        // Fallback for fresh install before migrations
        return 'admin';
    }
}
