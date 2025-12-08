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
        'timezones' => \App\Resources\TimezoneResource::class
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

];
