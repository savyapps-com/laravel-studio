<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Resource Registry
    |--------------------------------------------------------------------------
    |
    | Register your resource classes here. The key is the resource name
    | used in routes, and the value is the fully qualified class name.
    |
    | Example:
    | 'users' => \App\Resources\UserResource::class,
    | 'posts' => \App\Resources\PostResource::class,
    |
    */

    'resources' => [
        'users' => \App\Resources\UserResource::class,
        'roles' => \App\Resources\RoleResource::class,
        'countries' => \App\Resources\CountryResource::class,
        'timezones' => \App\Resources\TimezoneResource::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware to apply to all Laravel Studio resource routes.
    | By default, we use 'api' and 'auth:sanctum' for authentication.
    |
    */

    'middleware' => ['api', 'auth:sanctum', 'admin'],

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

    'prefix' => 'api/resources',

    /*
    |--------------------------------------------------------------------------
    | Route Name Prefix
    |--------------------------------------------------------------------------
    |
    | The name prefix for all Laravel Studio resource routes.
    | This is useful for generating URLs using route() helper.
    |
    */

    'name_prefix' => 'api.resources.',

    /*
    |--------------------------------------------------------------------------
    | Panels Configuration
    |--------------------------------------------------------------------------
    |
    | Configure multiple admin panels with different resources, middleware,
    | and branding. Each panel can have its own navigation and dashboard.
    |
    */

    'panels' => [
        'admin' => [
            'name' => 'Admin Panel',
            'path' => '/admin',
            'middleware' => ['auth:sanctum', 'admin'],
            'resources' => ['users', 'roles', 'countries', 'timezones'],
            'navigation' => [
                ['label' => 'Dashboard', 'icon' => 'dashboard', 'route' => 'admin.dashboard'],
                ['label' => 'Users', 'icon' => 'users', 'resource' => 'users'],
                ['label' => 'Roles', 'icon' => 'shield', 'resource' => 'roles'],
                ['label' => 'Countries', 'icon' => 'globe', 'resource' => 'countries'],
                ['label' => 'Timezones', 'icon' => 'clock', 'resource' => 'timezones'],
            ],
        ],
        'user' => [
            'name' => 'User Panel',
            'path' => '/user',
            'middleware' => ['auth:sanctum'],
            'resources' => [],
            'navigation' => [
                ['label' => 'Dashboard', 'icon' => 'dashboard', 'route' => 'user.dashboard'],
                ['label' => 'Profile', 'icon' => 'user', 'route' => 'user.profile'],
                ['label' => 'Settings', 'icon' => 'settings', 'route' => 'user.settings'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Authorization Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the authorization system for resources, fields, and actions.
    | Uses Laravel's native Gates and Policies for permission checks.
    |
    */

    'authorization' => [
        'enabled' => true,
        'super_admin_role' => 'admin',  // Role that bypasses all permission checks
        'cache_ttl' => 3600,            // Permission cache TTL in seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Activity Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure automatic activity logging for models and resources.
    | Activities are stored in the activities table and can be viewed
    | in the admin panel.
    |
    */

    'activity' => [
        'enabled' => true,
        'log_name' => 'default',
        'log_ip_address' => true,
        'log_user_agent' => true,
        'cleanup_days' => null,         // Days to keep logs, null = forever
    ],

    /*
    |--------------------------------------------------------------------------
    | Global Search Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the global search feature (Cmd+K / Ctrl+K).
    | Search across all resources from a single search box.
    |
    */

    'global_search' => [
        'enabled' => true,
        'min_characters' => 2,
        'debounce_ms' => 300,
        'max_results' => 20,
        'results_per_resource' => 5,
        'cache_ttl' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Cards Configuration
    |--------------------------------------------------------------------------
    |
    | Configure dashboard cards/widgets that display metrics and data.
    | Cards can be added to resources or panel dashboards.
    |
    */

    'cards' => [
        'enabled' => true,
        'cache_ttl' => 300,             // Card data cache TTL in seconds
        'refresh_interval' => null,     // Auto-refresh interval (null = manual)
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
    | Dashboard Widgets Configuration
    |--------------------------------------------------------------------------
    |
    | Configure panel-level dashboard widgets that appear on the main
    | dashboard page for each panel.
    |
    */

    'dashboard' => [
        'widgets' => [
            // Example widget configurations:
            // [
            //     'component' => 'StatsOverview',
            //     'width' => 'full',
            //     'props' => ['resources' => ['users', 'orders']],
            // ],
            // [
            //     'component' => 'RecentActivity',
            //     'width' => '1/2',
            // ],
        ],
    ],

];
