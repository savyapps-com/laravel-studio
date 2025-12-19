<?php

use Illuminate\Support\Facades\Route;
use SavyApps\LaravelStudio\Http\Controllers\ActivityController;
use SavyApps\LaravelStudio\Http\Controllers\AuthController;
use SavyApps\LaravelStudio\Http\Controllers\CardController;
use SavyApps\LaravelStudio\Http\Controllers\GlobalSearchController;
use SavyApps\LaravelStudio\Http\Controllers\ImpersonationController;
use SavyApps\LaravelStudio\Http\Controllers\PanelController;
use SavyApps\LaravelStudio\Http\Controllers\PermissionController;
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

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
|
| These routes handle user authentication (login, register, password reset).
| They can be disabled via config/studio.php if your app uses custom auth.
|
*/

if (config('studio.auth.enabled', true)) {
    // Public auth routes (no authentication required)
    Route::middleware(['api'])
        ->prefix('api')
        ->name('api.')
        ->group(function () {
            // Login - always available when auth is enabled
            Route::post('login', [AuthController::class, 'login'])->name('login');

            // Registration - conditionally available
            if (config('studio.auth.registration.enabled', true)) {
                Route::post('register', [AuthController::class, 'register'])->name('register');
                Route::get('check-registration', [AuthController::class, 'checkRegistration'])->name('check-registration');
            }

            // Password reset - conditionally available
            if (config('studio.auth.password_reset.enabled', true)) {
                Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');
                Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('reset-password');
            }

            // Email check (for invitation flows)
            Route::post('check-email', [AuthController::class, 'checkEmail'])->name('check-email');
        });

    // Protected auth routes (authentication required)
    Route::middleware(['api', 'auth:sanctum'])
        ->prefix('api')
        ->name('api.')
        ->group(function () {
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
            Route::get('me', [AuthController::class, 'me'])->name('me');
            Route::put('profile', [AuthController::class, 'updateProfile'])->name('profile.update');
            Route::put('password', [AuthController::class, 'changePassword'])->name('password.change');
            Route::post('logout-all-sessions', [AuthController::class, 'logoutAllSessions'])->name('logout-all-sessions');
            Route::post('logout-other-sessions', [AuthController::class, 'logoutOtherSessions'])->name('logout-other-sessions');
        });

    // Impersonation routes - conditionally available
    if (config('studio.auth.impersonation.enabled', true)) {
        Route::middleware(['api', 'auth:sanctum'])
            ->prefix('api/impersonation')
            ->name('api.impersonation.')
            ->group(function () {
                Route::get('status', [ImpersonationController::class, 'status'])->name('status');
                Route::post('stop', [ImpersonationController::class, 'stopImpersonating'])->name('stop');
                Route::post('{userId}', [ImpersonationController::class, 'impersonate'])->name('start');
            });
    }
}

// Public Panel API Routes - For login/register pages (no auth required)
Route::middleware(['api'])
    ->prefix('api/panels')
    ->name('api.panels.')
    ->group(function () {
        Route::get('{panel}/info', [PanelController::class, 'info'])->name('info');
    });

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

// Permission API Routes
Route::middleware(['api', 'auth:sanctum'])
    ->prefix('api/permissions')
    ->name('api.permissions.')
    ->group(function () {
        Route::get('/', [PermissionController::class, 'index'])->name('index');
        Route::get('my', [PermissionController::class, 'myPermissions'])->name('my');
        Route::post('check', [PermissionController::class, 'check'])->name('check');
        Route::post('sync', [PermissionController::class, 'sync'])->name('sync');
    });

// Role Permissions Routes
Route::middleware(['api', 'auth:sanctum'])
    ->prefix('api/roles')
    ->name('api.roles.')
    ->group(function () {
        Route::get('{role}/permissions', [PermissionController::class, 'rolePermissions'])->name('permissions');
        Route::put('{role}/permissions', [PermissionController::class, 'updateRolePermissions'])->name('permissions.update');
    });

// Activity Log Routes
Route::middleware(['api', 'auth:sanctum'])
    ->prefix('api/activities')
    ->name('api.activities.')
    ->group(function () {
        Route::get('/', [ActivityController::class, 'index'])->name('index');
        Route::get('recent', [ActivityController::class, 'recent'])->name('recent');
        Route::get('statistics', [ActivityController::class, 'statistics'])->name('statistics');
        Route::get('filter-options', [ActivityController::class, 'filterOptions'])->name('filter-options');
        Route::get('my', [ActivityController::class, 'myActivities'])->name('my');
        Route::get('subject/{subjectType}/{subjectId}', [ActivityController::class, 'forSubject'])->name('subject');
        Route::get('{id}', [ActivityController::class, 'show'])->name('show');
        Route::delete('{id}', [ActivityController::class, 'destroy'])->name('destroy');
        Route::post('bulk/delete', [ActivityController::class, 'bulkDelete'])->name('bulk.delete');
        Route::post('cleanup', [ActivityController::class, 'cleanup'])->name('cleanup');
    });

// Global Search Routes
Route::middleware(['api', 'auth:sanctum'])
    ->prefix('api/search')
    ->name('api.search.')
    ->group(function () {
        Route::get('/', [GlobalSearchController::class, 'search'])->name('index');
        Route::get('suggestions', [GlobalSearchController::class, 'suggestions'])->name('suggestions');
        Route::get('resources', [GlobalSearchController::class, 'searchableResources'])->name('resources');
        Route::get('{resource}', [GlobalSearchController::class, 'searchResource'])->name('resource');
        Route::delete('recent', [GlobalSearchController::class, 'clearRecent'])->name('recent.clear');
    });

// Card/Widget Routes
Route::middleware(['api', 'auth:sanctum'])
    ->prefix('api/cards')
    ->name('api.cards.')
    ->group(function () {
        Route::get('dashboard', [CardController::class, 'dashboardCards'])->name('dashboard');
        Route::get('types', [CardController::class, 'types'])->name('types');
        Route::delete('cache', [CardController::class, 'clearAllCaches'])->name('cache.clear-all');
        Route::get('{resource}', [CardController::class, 'resourceCards'])->name('resource');
        Route::get('{resource}/{cardKey}', [CardController::class, 'show'])->name('show');
        Route::post('{resource}/{cardKey}/refresh', [CardController::class, 'refresh'])->name('refresh');
        Route::delete('{resource}/cache', [CardController::class, 'clearCache'])->name('cache.clear');
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
                ->group(function () {
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
