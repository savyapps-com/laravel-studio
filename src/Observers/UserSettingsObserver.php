<?php

namespace SavyApps\LaravelStudio\Observers;

use Illuminate\Database\Eloquent\Model;
use SavyApps\LaravelStudio\Models\SettingList;
use SavyApps\LaravelStudio\Services\SettingsService;

/**
 * Observer for initializing default user settings when a user is created.
 *
 * This observer can be registered on any User model to automatically create
 * default settings for new users. The settings are configurable via the
 * 'studio.user_settings' config key.
 *
 * Usage in your AppServiceProvider:
 *   User::observe(\SavyApps\LaravelStudio\Observers\UserSettingsObserver::class);
 *
 * Or register automatically via config:
 *   'user_settings' => [
 *       'observer_enabled' => true,
 *       ...
 *   ]
 */
class UserSettingsObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(Model $user): void
    {
        $settingsService = app(SettingsService::class);
        $defaultSettings = $this->getDefaultSettings();

        foreach ($defaultSettings as $settingData) {
            // Create the setting with proper value encoding
            $setting = $settingsService->set(
                $settingData['key'],
                $settingData['value'],
                'user',
                $user->id
            );

            // Update additional metadata fields
            $setting->update([
                'type' => $settingData['metadata']['type'],
                'group' => $settingData['metadata']['group'],
                'label' => $settingData['metadata']['label'],
                'description' => $settingData['metadata']['description'],
                'icon' => $settingData['metadata']['icon'],
                'is_public' => $settingData['metadata']['is_public'],
                'referenceable_type' => $settingData['metadata']['referenceable_type'] ?? null,
                'referenceable_id' => $settingData['metadata']['referenceable_id'] ?? null,
                'order' => $settingData['metadata']['order'],
            ]);
        }
    }

    /**
     * Handle the User "deleting" event.
     */
    public function deleting(Model $user): void
    {
        // Clean up user settings when user is deleted
        if (method_exists($user, 'settings')) {
            $user->settings()->delete();
        }
    }

    /**
     * Get default settings configuration.
     */
    protected function getDefaultSettings(): array
    {
        $settings = [];

        // Get theme setting
        $defaultTheme = config('studio.user_settings.defaults.theme', 'default');
        $themeList = SettingList::where('key', 'themes')
            ->where('value', $defaultTheme)
            ->first();

        $settings[] = [
            'key' => config('studio.user_settings.keys.theme', 'user_theme'),
            'value' => $defaultTheme,
            'metadata' => [
                'type' => 'reference',
                'group' => 'appearance',
                'label' => 'Theme',
                'description' => 'User interface color theme',
                'icon' => 'palette',
                'is_public' => true,
                'referenceable_type' => $themeList ? SettingList::class : null,
                'referenceable_id' => $themeList?->id,
                'order' => 1,
            ],
        ];

        // Get admin layout setting
        $defaultLayout = config('studio.user_settings.defaults.admin_layout', 'classic');
        $layoutList = SettingList::where('key', 'admin_layouts')
            ->where('value', $defaultLayout)
            ->first();

        $settings[] = [
            'key' => config('studio.user_settings.keys.admin_layout', 'user_admin_layout'),
            'value' => $defaultLayout,
            'metadata' => [
                'type' => 'reference',
                'group' => 'appearance',
                'label' => 'Admin Layout',
                'description' => 'Admin panel layout preference',
                'icon' => 'layout',
                'is_public' => true,
                'referenceable_type' => $layoutList ? SettingList::class : null,
                'referenceable_id' => $layoutList?->id,
                'order' => 2,
            ],
        ];

        // Get dark mode setting
        $settings[] = [
            'key' => config('studio.user_settings.keys.dark_mode', 'dark_mode'),
            'value' => config('studio.user_settings.defaults.dark_mode', false),
            'metadata' => [
                'type' => 'boolean',
                'group' => 'appearance',
                'label' => 'Dark Mode',
                'description' => 'Enable dark mode for the interface',
                'icon' => 'moon',
                'is_public' => true,
                'order' => 3,
            ],
        ];

        // Get items per page setting
        $settings[] = [
            'key' => config('studio.user_settings.keys.items_per_page', 'items_per_page'),
            'value' => config('studio.user_settings.defaults.items_per_page', 25),
            'metadata' => [
                'type' => 'integer',
                'group' => 'general',
                'label' => 'Items Per Page',
                'description' => 'Number of items to display per page',
                'icon' => 'list',
                'is_public' => true,
                'order' => 1,
            ],
        ];

        // Allow additional settings from config
        $additionalSettings = config('studio.user_settings.additional', []);
        foreach ($additionalSettings as $additional) {
            $settings[] = $additional;
        }

        return $settings;
    }
}
