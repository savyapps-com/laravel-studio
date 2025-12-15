<?php

namespace SavyApps\LaravelStudio\Observers;

use SavyApps\LaravelStudio\Models\Role;
use SavyApps\LaravelStudio\Services\AuthorizationService;

/**
 * Observer for Role model to handle cache invalidation.
 *
 * This observer automatically clears permission caches when roles are
 * created, updated, or deleted to ensure users always have accurate
 * permission data.
 */
class RoleObserver
{
    /**
     * Handle the Role "created" event.
     *
     * @param Role $role
     * @return void
     */
    public function created(Role $role): void
    {
        $this->clearCaches($role);
    }

    /**
     * Handle the Role "updated" event.
     *
     * @param Role $role
     * @return void
     */
    public function updated(Role $role): void
    {
        $this->clearCaches($role);
    }

    /**
     * Handle the Role "deleted" event.
     *
     * @param Role $role
     * @return void
     */
    public function deleted(Role $role): void
    {
        // Clear all permission caches since users may have had this role
        $this->getAuthorizationService()->clearAllPermissionCaches();
        $this->getAuthorizationService()->clearPermissionCaches();
    }

    /**
     * Handle pivot table changes (when permissions are attached/detached).
     *
     * This is triggered via the BelongsToMany relationship events.
     *
     * @param Role $role
     * @param array<int> $changes
     * @return void
     */
    public function pivotAttached(Role $role, array $changes): void
    {
        $this->clearCaches($role);
    }

    /**
     * Handle pivot table changes (when permissions are attached/detached).
     *
     * @param Role $role
     * @param array<int> $changes
     * @return void
     */
    public function pivotDetached(Role $role, array $changes): void
    {
        $this->clearCaches($role);
    }

    /**
     * Handle pivot table sync.
     *
     * @param Role $role
     * @param array<int> $changes
     * @return void
     */
    public function pivotSynced(Role $role, array $changes): void
    {
        $this->clearCaches($role);
    }

    /**
     * Clear caches for role and its users.
     *
     * @param Role $role
     * @return void
     */
    protected function clearCaches(Role $role): void
    {
        $authService = $this->getAuthorizationService();

        // Clear permission caches for all users with this role
        $authService->clearRoleUsersCaches($role);

        // Clear global permission cache
        $authService->clearPermissionCaches();
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
