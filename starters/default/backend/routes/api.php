<?php

use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\EmailTemplateController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\UserSettingsController;
use Illuminate\Support\Facades\Route;

// Note: Authentication routes are provided by the Laravel Studio package.
// They are automatically registered when the package is installed.
// See config/studio.php 'auth' section to configure auth features.

// Protected application-specific routes
Route::middleware('auth:sanctum')->group(function () {
    // Settings routes (admin-only for global settings)
    Route::middleware('panel:admin')->group(function () {
        Route::get('/settings', [SettingsController::class, 'index'])->name('api.settings.index');
        Route::get('/settings/groups', [SettingsController::class, 'groups'])->name('api.settings.groups');
        Route::get('/settings/{key}', [SettingsController::class, 'show'])->name('api.settings.show');
        Route::post('/settings', [SettingsController::class, 'store'])->name('api.settings.store');
        Route::put('/settings/{key}', [SettingsController::class, 'update'])->name('api.settings.update');
        Route::delete('/settings/{key}', [SettingsController::class, 'destroy'])->name('api.settings.destroy');
    });

    // Settings lists (public within auth)
    Route::get('/settings/lists/{key}', [SettingsController::class, 'lists'])->name('api.settings.lists');

    // User settings routes
    Route::get('/user/settings', [UserSettingsController::class, 'index'])->name('api.user.settings.index');
    Route::get('/user/settings/{key}', [UserSettingsController::class, 'show'])->name('api.user.settings.show');
    Route::put('/user/settings', [UserSettingsController::class, 'update'])->name('api.user.settings.update');
    Route::put('/user/settings/{key}', [UserSettingsController::class, 'updateSingle'])->name('api.user.settings.update-single');

    // Email template routes (admin-only)
    Route::middleware('panel:admin')->prefix('email-templates')->name('api.email-templates.')->group(function () {
        Route::get('/', [EmailTemplateController::class, 'index'])->name('index');
        Route::post('/', [EmailTemplateController::class, 'store'])->name('store');
        Route::get('/{template}', [EmailTemplateController::class, 'show'])->name('show');
        Route::put('/{template}', [EmailTemplateController::class, 'update'])->name('update');
        Route::delete('/{template}', [EmailTemplateController::class, 'destroy'])->name('destroy');
        Route::post('/{template}/duplicate', [EmailTemplateController::class, 'duplicate'])->name('duplicate');
        Route::post('/{template}/preview', [EmailTemplateController::class, 'preview'])->name('preview');
        Route::post('/{template}/send-test', [EmailTemplateController::class, 'sendTest'])->name('send-test');
        Route::get('/{template}/variables', [EmailTemplateController::class, 'variables'])->name('variables');
    });

    // Media upload routes
    Route::prefix('media')->name('api.media.')->group(function () {
        Route::post('/upload', [MediaController::class, 'upload'])->name('upload');
        Route::post('/upload-multiple', [MediaController::class, 'uploadMultiple'])->name('upload-multiple');
        Route::get('/{media}/download', [MediaController::class, 'download'])->name('download');
        Route::delete('/{media}', [MediaController::class, 'destroy'])->name('destroy');
    });

    // Comments (generic endpoint for any commentable model)
    Route::prefix('comments')->group(function () {
        Route::get('/{commentableType}/{commentableId}', [CommentController::class, 'index'])->name('comments.index');
        Route::post('/{commentableType}/{commentableId}', [CommentController::class, 'store'])->name('comments.store');
        Route::put('/{comment}', [CommentController::class, 'update'])->name('comments.update');
        Route::delete('/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
    });

    // Note: Generic Resource CRUD routes are now loaded from the Laravel Studio package
    // The package automatically registers routes at /api/resources/* with admin middleware
});
