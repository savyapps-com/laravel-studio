<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;
use SavyApps\LaravelStudio\Models\Panel;

class PanelObserver
{
    /**
     * Handle the Panel "saved" event (covers both created and updated).
     */
    public function saved(Panel $panel): void
    {
        $this->clearPanelCache();
    }

    /**
     * Handle the Panel "deleted" event.
     */
    public function deleted(Panel $panel): void
    {
        $this->clearPanelCache();
    }

    /**
     * Clear the panel keys cache.
     */
    protected function clearPanelCache(): void
    {
        Cache::forget('active_panel_keys');
    }
}
