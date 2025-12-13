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
    | Panels Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching for panels loaded from the database. This reduces
    | database queries on every request when panels are stored in the database.
    |
    | Environment variables:
    | - STUDIO_PANELS_CACHE_ENABLED: Enable/disable panels caching (default: true)
    | - STUDIO_PANELS_CACHE_TTL: Cache TTL in seconds (default: 3600)
    |
    */

    'panels_cache' => [
        'enabled' => env('STUDIO_PANELS_CACHE_ENABLED', true),
        'ttl' => env('STUDIO_PANELS_CACHE_TTL', 3600),
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
    | Features Configuration
    |--------------------------------------------------------------------------
    |
    | Define features that can be enabled/disabled per panel.
    | Features are non-resource pages or functionality.
    |
    */

    'features' => [
        'email-templates' => [
            'label' => 'Email Templates',
            'icon' => 'mail',
            'route' => 'email-templates.index',
        ],
        'system-settings' => [
            'label' => 'System Settings',
            'icon' => 'settings',
            'route' => 'settings.system',
        ],
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
    | - STUDIO_PERMISSIONS_CACHE_ENABLED: Enable permission caching (default: true)
    | - STUDIO_PERMISSIONS_CACHE_TTL: Permission cache TTL in seconds (default: 3600)
    | - STUDIO_REGISTER_GATES: Auto-register Laravel Gates (default: true)
    |
    */

    'authorization' => [
        'enabled' => env('STUDIO_AUTH_ENABLED', true),
        'super_admin_role' => env('STUDIO_SUPER_ADMIN_ROLE', 'super_admin'),

        'cache' => [
            'enabled' => env('STUDIO_PERMISSIONS_CACHE_ENABLED', true),
            'ttl' => env('STUDIO_PERMISSIONS_CACHE_TTL', 3600),
            'prefix' => 'studio_permissions_',
        ],

        'register_gates' => env('STUDIO_REGISTER_GATES', true),

        // Model classes (can be overridden if using custom models)
        'models' => [
            'user' => \App\Models\User::class,
            'role' => \App\Models\Role::class,
            'permission' => \SavyApps\LaravelStudio\Models\Permission::class,
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
    | - STUDIO_ACTIVITY_LOG_IP: Log IP addresses (default: true)
    | - STUDIO_ACTIVITY_LOG_USER_AGENT: Log user agents (default: true)
    |
    */

    'activity_log' => [
        'enabled' => env('STUDIO_ACTIVITY_LOG_ENABLED', true),
        'per_page' => 25,
        'cleanup_days' => env('STUDIO_ACTIVITY_LOG_CLEANUP_DAYS', 90),
        'default_events' => ['created', 'updated', 'deleted'],
        'ignore_attributes' => ['password', 'remember_token', 'updated_at'],
        'log_ip' => env('STUDIO_ACTIVITY_LOG_IP', true),
        'log_user_agent' => env('STUDIO_ACTIVITY_LOG_USER_AGENT', true),
        'model' => \SavyApps\LaravelStudio\Models\Activity::class,
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
    | - STUDIO_SEARCH_MIN_CHARS: Minimum characters to trigger search (default: 2)
    | - STUDIO_SEARCH_MAX_RESULTS: Maximum total results (default: 20)
    | - STUDIO_SEARCH_CACHE_TTL: Cache TTL in seconds, 0 to disable (default: 60)
    |
    */

    'global_search' => [
        'enabled' => env('STUDIO_SEARCH_ENABLED', true),
        'min_characters' => env('STUDIO_SEARCH_MIN_CHARS', 2),
        'debounce_ms' => 300,
        'max_results' => env('STUDIO_SEARCH_MAX_RESULTS', 20),
        'results_per_resource' => 5,
        'cache_ttl' => env('STUDIO_SEARCH_CACHE_TTL', 60),
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
    | - STUDIO_CARDS_CACHE_TTL: Card cache TTL in seconds (default: 300)
    | - STUDIO_CARDS_REFRESH_INTERVAL: Auto-refresh interval in seconds (default: null)
    |
    */

    'cards' => [
        'enabled' => env('STUDIO_CARDS_ENABLED', true),
        'cache_ttl' => env('STUDIO_CARDS_CACHE_TTL', 300),
        'refresh_interval' => env('STUDIO_CARDS_REFRESH_INTERVAL'),
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
