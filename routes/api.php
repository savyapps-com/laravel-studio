<?php

use Illuminate\Support\Facades\Route;
use SavyApps\LaravelStudio\Http\Controllers\ResourceController;

/*
|--------------------------------------------------------------------------
| Laravel Studio API Routes
|--------------------------------------------------------------------------
|
| These routes are automatically registered by the LaravelStudioServiceProvider.
| All routes are prefixed and namespaced according to your config/studio.php
| configuration file.
|
*/

Route::middleware(config('studio.middleware', ['api', 'auth:sanctum']))
    ->prefix(config('studio.prefix', 'api/studio'))
    ->name(config('studio.name_prefix', 'studio.'))
    ->group(function () {
        // Resource metadata endpoint (fields, filters, actions definitions)
        Route::get('{resource}/meta', [ResourceController::class, 'meta'])
            ->name('meta');

        // Search related models for relationship fields
        Route::get('{resource}/search', [ResourceController::class, 'searchRelated'])
            ->name('search');

        // Standard RESTful resource endpoints
        Route::get('{resource}', [ResourceController::class, 'index'])
            ->name('index');

        Route::post('{resource}', [ResourceController::class, 'store'])
            ->name('store');

        Route::get('{resource}/{id}', [ResourceController::class, 'show'])
            ->name('show');

        Route::put('{resource}/{id}', [ResourceController::class, 'update'])
            ->name('update');

        Route::patch('{resource}/{id}', [ResourceController::class, 'patch'])
            ->name('patch');

        Route::delete('{resource}/{id}', [ResourceController::class, 'destroy'])
            ->name('destroy');

        // Bulk operations
        Route::post('{resource}/bulk/delete', [ResourceController::class, 'bulkDelete'])
            ->name('bulk.delete');

        Route::post('{resource}/bulk/update', [ResourceController::class, 'bulkUpdate'])
            ->name('bulk.update');

        // Custom actions
        Route::post('{resource}/actions/{action}', [ResourceController::class, 'runAction'])
            ->name('actions.run');
    });
