<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Panel Configuration (Fallback)
    |--------------------------------------------------------------------------
    |
    | This is a fallback configuration used when the panels table doesn't exist
    | or is empty. Panels are now stored in the database and managed dynamically.
    | Use the Panel Management API or admin interface to create/modify panels.
    |
    | Note: Database panels take precedence over config panels.
    |
    */

    'panels' => [
        'admin' => [
            'label' => 'Admin Panel',
            'path' => '/admin',
            'icon' => 'layout',
            'middleware' => ['api', 'auth:sanctum', 'panel:admin'],
            'role' => 'admin',
            'resources' => [],
            'features' => [],
            'menu' => [
                ['type' => 'link', 'label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'home'],
            ],
            'settings' => [
                'layout' => 'classic',
                'theme' => 'light',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Panel Priority
    |--------------------------------------------------------------------------
    |
    | When a user has access to multiple panels, redirect to the first one
    | they have access to based on this priority order.
    |
    */

    'panel_priority' => ['admin'],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Unified cache configuration for all Laravel Studio caching (panels,
    | permissions, search, cards). This simplifies configuration by using
    | a single TTL value across all cached data.
    |
    | Environment variables:
    | - STUDIO_CACHE_ENABLED: Enable/disable all caching (default: true)
    | - STUDIO_CACHE_TTL: Cache TTL in seconds (default: 3600)
    |
    */

    'cache' => [
        'enabled' => env('STUDIO_CACHE_ENABLED', true),
        'ttl' => env('STUDIO_CACHE_TTL', 3600),
        'prefix' => 'studio_',
    ],

    /*
    |--------------------------------------------------------------------------
    | Resource Registry
    |--------------------------------------------------------------------------
    |
    | Register your resource classes here. The key is the resource name
    | used in routes, and the value is the fully qualified class name.
    |
    | Example:
    | 'users' => [
    |     'class' => \App\Resources\UserResource::class,
    |     'label' => 'Users',
    |     'icon' => 'users',
    | ],
    |
    */

    'resources' => [
        // Register your resources here
    ],

    /*
    |--------------------------------------------------------------------------
    | Bulk Operations Configuration
    |--------------------------------------------------------------------------
    |
    | Configure limits for bulk operations to prevent abuse and performance issues.
    |
    | Environment variables:
    | - STUDIO_BULK_MAX_IDS: Maximum IDs per bulk operation (default: 1000)
    | - STUDIO_BULK_CHUNK_SIZE: Chunk size for processing (default: 100)
    |
    */

    'bulk_operations' => [
        'max_ids' => env('STUDIO_BULK_MAX_IDS', 1000),
        'chunk_size' => env('STUDIO_BULK_CHUNK_SIZE', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Default middleware to apply to all Laravel Studio resource routes.
    | This can be overridden per-panel in the panels configuration.
    |
    */

    'middleware' => ['api', 'auth:sanctum'],

    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    |
    | The URL prefix for all Laravel Studio resource routes.
    |
    | Environment variable: STUDIO_ROUTE_PREFIX (default: 'api/studio')
    |
    | Examples:
    | - 'api/studio' => /api/studio/users
    | - 'api/admin' => /api/admin/users
    |
    */

    'prefix' => env('STUDIO_ROUTE_PREFIX', 'api/studio'),

    /*
    |--------------------------------------------------------------------------
    | Route Name Prefix
    |--------------------------------------------------------------------------
    |
    | The name prefix for all Laravel Studio resource routes.
    | This is useful for generating URLs using route() helper.
    |
    */

    'name_prefix' => 'studio.',

    /*
    |--------------------------------------------------------------------------
    | Authorization Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the authorization system for resource-level, field-level,
    | and action-level permission control.
    |
    | Environment variables:
    | - STUDIO_AUTH_ENABLED: Enable/disable authorization (default: true)
    | - STUDIO_SUPER_ADMIN_ROLE: Role that bypasses checks (default: 'super_admin')
    |
    | Note: Caching uses the unified 'cache' configuration above.
    |
    */

    'authorization' => [
        'enabled' => env('STUDIO_AUTH_ENABLED', true),
        'super_admin_role' => env('STUDIO_SUPER_ADMIN_ROLE', 'super_admin'),

        // Model classes (can be overridden if using custom models)
        'models' => [
            'user' => \App\Models\User::class,
            'role' => \SavyApps\LaravelStudio\Models\Role::class,
            'permission' => \SavyApps\LaravelStudio\Models\Permission::class,
        ],

        // Policy classes for authorization
        'policies' => [
            'user' => \SavyApps\LaravelStudio\Policies\UserPolicy::class,
            'role' => \SavyApps\LaravelStudio\Policies\RolePolicy::class,
            'permission' => \SavyApps\LaravelStudio\Policies\PermissionPolicy::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Activity Log Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the activity logging system for tracking changes to resources.
    |
    | Environment variables:
    | - STUDIO_ACTIVITY_LOG_ENABLED: Enable/disable activity logging (default: true)
    | - STUDIO_ACTIVITY_LOG_CLEANUP_DAYS: Days to keep activities (default: 90, 0 = never)
    |
    */

    'activity_log' => [
        'enabled' => env('STUDIO_ACTIVITY_LOG_ENABLED', true),
        'cleanup_days' => env('STUDIO_ACTIVITY_LOG_CLEANUP_DAYS', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | Global Search Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the global search functionality for searching across resources.
    |
    | Environment variables:
    | - STUDIO_SEARCH_ENABLED: Enable/disable global search (default: true)
    |
    | Note: Caching uses the unified 'cache' configuration above.
    |
    */

    'global_search' => [
        'enabled' => env('STUDIO_SEARCH_ENABLED', true),
        'min_characters' => 2,
        'debounce_ms' => 300,
        'max_results' => 20,
        'results_per_resource' => 5,
        'shortcut' => [
            'key' => 'k',
            'modifier' => 'meta',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Cards/Widgets Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the dashboard cards and widgets system for displaying
    | metrics, charts, and key performance indicators.
    |
    | Environment variables:
    | - STUDIO_CARDS_ENABLED: Enable/disable cards feature (default: true)
    |
    | Note: Caching uses the unified 'cache' configuration above.
    |
    */

    'cards' => [
        'enabled' => env('STUDIO_CARDS_ENABLED', true),
        'max_per_row' => 4,
        'colors' => [
            'blue' => '#3B82F6',
            'green' => '#10B981',
            'yellow' => '#F59E0B',
            'red' => '#EF4444',
            'purple' => '#8B5CF6',
            'pink' => '#EC4899',
            'indigo' => '#6366F1',
            'cyan' => '#06B6D4',
            'orange' => '#F97316',
            'teal' => '#14B8A6',
            'gray' => '#6B7280',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the default dashboard widgets. These will be shown on the
    | main dashboard unless a panel specifies its own widgets.
    |
    */

    'dashboard' => [
        'widgets' => [
            // Define default dashboard widgets here
            // Example:
            // ['component' => 'StatsOverview', 'width' => 'full'],
            // ['component' => 'RecentActivity', 'width' => '1/2'],
        ],
    ],

];
