<?php

namespace SavyApps\LaravelStudio\Console;

class FileMapping
{
    /**
     * Get the backend files to copy for the default starter.
     *
     * @return array<string, string> Map of source => destination paths
     */
    public static function getDefaultBackendFiles(): array
    {
        return [
            'app/Http' => 'app/Http',
            'app/Services' => 'app/Services',
            'app/Resources' => 'app/Resources',
            'app/Models' => 'app/Models',
            'app/Observers' => 'app/Observers',
            'app/Enums' => 'app/Enums',
            'app/Console' => 'app/Console',
            'app/Providers' => 'app/Providers',
            'database/migrations' => 'database/migrations',
            'database/seeders' => 'database/seeders',
            'database/factories' => 'database/factories',
            'routes/api.php' => 'routes/api.php',
            'routes/web.php' => 'routes/web.php',
            'bootstrap/app.php' => 'bootstrap/app.php',
            'config/studio.php' => 'config/studio.php',
            'config/admin.php' => 'config/admin.php',
        ];
    }

    /**
     * Get the frontend files to copy for the default starter.
     *
     * @return array<string, string> Map of source => destination paths
     */
    public static function getDefaultFrontendFiles(): array
    {
        return [
            'js/App.vue' => 'resources/js/App.vue',
            'js/app.js' => 'resources/js/app.js',
            'js/spa.js' => 'resources/js/spa.js',
            'js/guest.js' => 'resources/js/guest.js',
            'js/bootstrap.js' => 'resources/js/bootstrap.js',
            'js/theme.config.js' => 'resources/js/theme.config.js',
            'js/directives' => 'resources/js/directives',
            'js/utils' => 'resources/js/utils',
            'js/config' => 'resources/js/config',
            'js/layouts' => 'resources/js/layouts',
            'js/pages' => 'resources/js/pages',
            'js/components' => 'resources/js/components',
            'js/stores' => 'resources/js/stores',
            'js/services' => 'resources/js/services',
            'js/router' => 'resources/js/router',
            'composables' => 'resources/js/composables',
            'css' => 'resources/css',
            'views' => 'resources/views',
            'vite.config.js' => 'vite.config.js',
            'package.json' => 'package.json',
        ];
    }

    /**
     * Get files that should never be overwritten without confirmation.
     *
     * @return array<string>
     */
    public static function getProtectedFiles(): array
    {
        return [
            'routes/api.php',
            'routes/web.php',
            'bootstrap/app.php',
            'vite.config.js',
            'package.json',
            '.env',
            'composer.json',
        ];
    }
}
