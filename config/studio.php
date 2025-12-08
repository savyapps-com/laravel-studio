<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Panels Configuration
    |--------------------------------------------------------------------------
    |
    | Define multiple admin panels with their own resources, middleware,
    | and access control. Each panel requires a specific ROLE (not auth guard).
    |
    | - 'role': The role name required to access this panel (from roles table)
    | - 'roles': Array of roles that can access this panel (alternative)
    | - All panels use the same auth guard (sanctum)
    |
    */

    'panels' => [
        'admin' => [
            'label' => 'Admin Panel',
            'path' => '/admin',
            'icon' => 'cog',
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

        'user' => [
            'label' => 'User Dashboard',
            'path' => '/user',
            'icon' => 'user',
            'middleware' => ['api', 'auth:sanctum', 'panel:user'],
            'role' => 'user',
            'resources' => [],
            'features' => [],
            'menu' => [
                ['type' => 'link', 'label' => 'Dashboard', 'route' => 'user.dashboard', 'icon' => 'home'],
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

    'panel_priority' => ['admin', 'user'],

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
    | Default: 'api/studio'
    |
    | Examples:
    | - 'api/studio' => /api/studio/users
    | - 'api/admin' => /api/admin/users
    |
    */

    'prefix' => 'api/studio',

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
    */

    'authorization' => [
        // Enable/disable authorization checks globally
        'enabled' => true,

        // Role that bypasses all permission checks
        'super_admin_role' => 'super_admin',

        // Permission caching
        'cache' => [
            'enabled' => true,
            'ttl' => 3600, // seconds
            'prefix' => 'studio_permissions_',
        ],

        // Auto-register Laravel Gates for permissions
        'register_gates' => true,

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
    */

    'activity_log' => [
        // Enable/disable activity logging globally
        'enabled' => true,

        // Default number of activities per page
        'per_page' => 25,

        // Number of days to keep activities (used by cleanup command)
        // Set to 0 to never auto-cleanup
        'cleanup_days' => 90,

        // Events to log by default (can be overridden per model)
        'default_events' => ['created', 'updated', 'deleted'],

        // Attributes to ignore by default
        'ignore_attributes' => ['password', 'remember_token', 'updated_at'],

        // Log IP address and user agent
        'log_ip' => true,
        'log_user_agent' => true,

        // Activity model class (can be overridden if using custom model)
        'model' => \SavyApps\LaravelStudio\Models\Activity::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Global Search Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the global search functionality for searching across resources.
    |
    */

    'global_search' => [
        // Enable/disable global search
        'enabled' => true,

        // Minimum characters required before search triggers
        'min_characters' => 2,

        // Debounce time in milliseconds for search input
        'debounce_ms' => 300,

        // Maximum total results to return
        'max_results' => 20,

        // Results per resource type (can be overridden per resource)
        'results_per_resource' => 5,

        // Cache search results (in seconds, 0 to disable)
        'cache_ttl' => 60,

        // Keyboard shortcut to open search palette
        'shortcut' => [
            'key' => 'k',
            'modifier' => 'meta', // 'meta' for Cmd/Win, 'ctrl' for Ctrl
        ],
    ],

];
