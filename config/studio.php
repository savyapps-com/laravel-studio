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
    | Pagination Configuration
    |--------------------------------------------------------------------------
    |
    | Configure pagination limits to prevent abuse and memory exhaustion.
    |
    | Environment variables:
    | - STUDIO_PAGINATION_DEFAULT: Default items per page (default: 15)
    | - STUDIO_PAGINATION_MAX: Maximum items per page (default: 100)
    |
    */

    'pagination' => [
        'default' => env('STUDIO_PAGINATION_DEFAULT', 15),
        'max' => env('STUDIO_PAGINATION_MAX', 100),
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

        /*
        |--------------------------------------------------------------------------
        | Hierarchical Permissions
        |--------------------------------------------------------------------------
        |
        | Enable permission inheritance where higher-level permissions automatically
        | grant access to lower-level ones. For example, if a user has 'users.delete'
        | permission, they automatically get 'users.update', 'users.view', and 'users.list'.
        |
        | This reduces the need to assign multiple permissions for common access patterns.
        |
        | Environment variable: STUDIO_PERMISSION_HIERARCHY (default: true)
        |
        */

        'use_hierarchy' => env('STUDIO_PERMISSION_HIERARCHY', true),

        // Permission hierarchy definition
        // Each key implies all permissions in its array (for the same resource)
        // Example: 'delete' implies 'update', 'view', and 'list'
        'hierarchy' => [
            'delete' => ['update', 'view', 'list'],
            'update' => ['view', 'list'],
            'create' => ['list'],
            'view' => ['list'],
            'bulk.delete' => ['delete', 'update', 'view', 'list'],
            'bulk.update' => ['update', 'view', 'list'],
            'export' => ['list'],
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

    /*
    |--------------------------------------------------------------------------
    | Authentication Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the built-in authentication system features. Each feature can
    | be enabled or disabled independently to match your application's needs.
    |
    | Environment variables:
    | - STUDIO_AUTH_ROUTES_ENABLED: Enable/disable all auth routes (default: true)
    | - STUDIO_REGISTRATION_ENABLED: Enable/disable registration (default: true)
    | - STUDIO_PASSWORD_RESET_ENABLED: Enable/disable password reset (default: true)
    | - STUDIO_IMPERSONATION_ENABLED: Enable/disable user impersonation (default: true)
    |
    */

    'auth' => [
        'enabled' => env('STUDIO_AUTH_ROUTES_ENABLED', true),

        'registration' => [
            'enabled' => env('STUDIO_REGISTRATION_ENABLED', true),
        ],

        'password_reset' => [
            'enabled' => env('STUDIO_PASSWORD_RESET_ENABLED', true),
        ],

        'impersonation' => [
            'enabled' => env('STUDIO_IMPERSONATION_ENABLED', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Settings Configuration
    |--------------------------------------------------------------------------
    |
    | Configure default settings for new users. These settings are created
    | automatically when a user is created if the UserSettingsObserver is
    | registered on your User model.
    |
    | To enable, add to your AppServiceProvider boot():
    |   User::observe(\SavyApps\LaravelStudio\Observers\UserSettingsObserver::class);
    |
    */

    'user_settings' => [
        // Setting key names (customize if your app uses different keys)
        'keys' => [
            'theme' => 'user_theme',
            'admin_layout' => 'user_admin_layout',
            'dark_mode' => 'dark_mode',
            'items_per_page' => 'items_per_page',
        ],

        // Default values for new users
        'defaults' => [
            'theme' => env('STUDIO_DEFAULT_THEME', 'default'),
            'admin_layout' => env('STUDIO_DEFAULT_LAYOUT', 'classic'),
            'dark_mode' => env('STUDIO_DEFAULT_DARK_MODE', false),
            'items_per_page' => env('STUDIO_DEFAULT_ITEMS_PER_PAGE', 25),
        ],

        // Additional custom settings (add your own settings here)
        // 'additional' => [
        //     [
        //         'key' => 'custom_setting',
        //         'value' => 'default_value',
        //         'metadata' => [
        //             'type' => 'string',
        //             'group' => 'general',
        //             'label' => 'Custom Setting',
        //             'description' => 'A custom user setting',
        //             'icon' => 'settings',
        //             'is_public' => true,
        //             'order' => 1,
        //         ],
        //     ],
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Seeder Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the PermissionSeeder behavior. These settings control
    | whether a super admin user is created and with what credentials.
    |
    | Usage:
    |   $this->call(\SavyApps\LaravelStudio\Database\Seeders\PermissionSeeder::class);
    |
    */

    'seeder' => [
        'create_super_admin' => env('STUDIO_SEEDER_CREATE_ADMIN', true),
        'super_admin_email' => env('STUDIO_SUPER_ADMIN_EMAIL', 'superadmin@app.com'),
        'super_admin_name' => env('STUDIO_SUPER_ADMIN_NAME', 'Super Admin'),
        'super_admin_password' => env('STUDIO_SUPER_ADMIN_PASSWORD', 'password'),
    ],

];
