<?php

use Illuminate\Support\Facades\Route;
use SavyApps\LaravelStudio\Http\Controllers\PanelController;
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

// Panel API Routes - Accessible to authenticated users
Route::middleware(['api', 'auth:sanctum'])
    ->prefix('api/panels')
    ->name('api.panels.')
    ->group(function () {
        Route::get('/', [PanelController::class, 'index'])->name('index');
        Route::get('{panel}', [PanelController::class, 'show'])->name('show');
        Route::get('{panel}/menu', [PanelController::class, 'menu'])->name('menu');
        Route::get('{panel}/resources', [PanelController::class, 'resources'])->name('resources');
        Route::post('{panel}/switch', [PanelController::class, 'switch'])->name('switch');
    });

// Panel-specific Resource Routes
// Each panel gets its own namespaced resource routes
Route::prefix('api/panels')
    ->name('api.panels.')
    ->group(function () {
        $panels = config('studio.panels', []);

        foreach ($panels as $panelKey => $panelConfig) {
            Route::middleware($panelConfig['middleware'] ?? ['api', 'auth:sanctum', "panel:{$panelKey}"])
                ->prefix($panelKey)
                ->name("{$panelKey}.")
                ->group(function () use ($panelKey, $panelConfig) {
                    // Resource routes for this panel
                    Route::prefix('resources/{resource}')
                        ->name('resources.')
                        ->group(function () {
                            Route::get('meta', [ResourceController::class, 'meta'])->name('meta');
                            Route::get('search', [ResourceController::class, 'searchRelated'])->name('search');
                            Route::get('/', [ResourceController::class, 'index'])->name('index');
                            Route::post('/', [ResourceController::class, 'store'])->name('store');
                            Route::get('{id}', [ResourceController::class, 'show'])->name('show');
                            Route::put('{id}', [ResourceController::class, 'update'])->name('update');
                            Route::patch('{id}', [ResourceController::class, 'patch'])->name('patch');
                            Route::delete('{id}', [ResourceController::class, 'destroy'])->name('destroy');
                            Route::post('bulk/delete', [ResourceController::class, 'bulkDelete'])->name('bulk.delete');
                            Route::post('bulk/update', [ResourceController::class, 'bulkUpdate'])->name('bulk.update');
                            Route::post('actions/{action}', [ResourceController::class, 'runAction'])->name('actions.run');
                        });
                });
        }
    });

// Legacy Resource Routes (backward compatibility)
// These routes are still available for existing implementations
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
