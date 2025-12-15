<?php

namespace SavyApps\LaravelStudio\Observers;

use SavyApps\LaravelStudio\Models\Permission;
use SavyApps\LaravelStudio\Services\AuthorizationService;

/**
 * Observer for Permission model to handle cache invalidation.
 *
 * This observer automatically clears permission caches when permissions
 * are created, updated, or deleted to ensure the system always has
 * accurate permission data.
 */
class PermissionObserver
{
    /**
     * Handle the Permission "created" event.
     *
     * @param Permission $permission
     * @return void
     */
    public function created(Permission $permission): void
    {
        $this->clearCaches();
    }

    /**
     * Handle the Permission "updated" event.
     *
     * @param Permission $permission
     * @return void
     */
    public function updated(Permission $permission): void
    {
        $this->clearCaches();

        // If permission name changed, clear all user caches
        if ($permission->isDirty('name')) {
            $this->getAuthorizationService()->clearAllPermissionCaches();
        }
    }

    /**
     * Handle the Permission "deleted" event.
     *
     * @param Permission $permission
     * @return void
     */
    public function deleted(Permission $permission): void
    {
        // Clear all caches since any user might have had this permission
        $this->getAuthorizationService()->clearAllPermissionCaches();
        $this->clearCaches();
    }

    /**
     * Handle pivot table changes (when permissions are attached/detached from roles).
     *
     * @param Permission $permission
     * @param array<int> $changes
     * @return void
     */
    public function pivotAttached(Permission $permission, array $changes): void
    {
        $this->clearCaches();
    }

    /**
     * Handle pivot table changes.
     *
     * @param Permission $permission
     * @param array<int> $changes
     * @return void
     */
    public function pivotDetached(Permission $permission, array $changes): void
    {
        $this->clearCaches();
    }

    /**
     * Clear all permission-related caches.
     *
     * @return void
     */
    protected function clearCaches(): void
    {
        $this->getAuthorizationService()->clearPermissionCaches();
    }

    /**
     * Get the authorization service instance.
     *
     * @return AuthorizationService
     */
    protected function getAuthorizationService(): AuthorizationService
    {
        return app(AuthorizationService::class);
    }
}
