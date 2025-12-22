<?php

namespace App\Observers;

use SavyApps\LaravelStudio\Observers\UserSettingsObserver;

/**
 * User Observer that extends the core UserSettingsObserver.
 *
 * This observer handles default user settings creation. The core observer
 * provides all the default behavior - extend this class only if you need
 * to customize the settings initialization for your application.
 *
 * Configuration is done via config/studio.php under 'user_settings'.
 */
class UserObserver extends UserSettingsObserver
{
    // The parent class handles all default behavior.
    // Override methods here if you need custom logic.
    //
    // Example: Add custom settings for your application
    //
    // protected function getDefaultSettings(): array
    // {
    //     $settings = parent::getDefaultSettings();
    //
    //     // Add custom application-specific settings
    //     $settings[] = [
    //         'key' => 'custom_preference',
    //         'value' => 'default_value',
    //         'metadata' => [
    //             'type' => 'string',
    //             'group' => 'preferences',
    //             'label' => 'Custom Preference',
    //             'description' => 'A custom preference for your app',
    //             'icon' => 'settings',
    //             'is_public' => true,
    //             'order' => 10,
    //         ],
    //     ];
    //
    //     return $settings;
    // }
}
