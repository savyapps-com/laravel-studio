<?php

namespace SavyApps\LaravelStudio\Services;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use SavyApps\LaravelStudio\Enums\Permission as PermissionEnum;
use SavyApps\LaravelStudio\Models\Permission;

/**
 * Service for managing authorization and permissions.
 *
 * This service handles:
 * - Permission syncing from resources and Permission enum
 * - Laravel Gate registration
 * - Role permission management
 *
 * @example
 * $authService = app(AuthorizationService::class);
 * $authService->syncPermissions();
 * $authService->syncRolePermissions($role, ['users.create', 'users.update']);
 */
class AuthorizationService
{
    /**
     * Register gates for all permissions.
     *
     * @return void
     */
    public function registerGates(): void
    {
        // Check if permissions table exists
        if (!Schema::hasTable('permissions')) {
            return;
        }

        try {
            $permissions = $this->getPermissionsFromDatabase();

            foreach ($permissions as $permission) {
                $permissionName = $permission['name'];
                Gate::define($permissionName, function ($user) use ($permissionName) {
                    if (method_exists($user, 'hasPermission')) {
                        return $user->hasPermission($permissionName);
                    }

                    return false;
                });
            }
        } catch (\Illuminate\Database\QueryException $e) {
            // Database not available during migrations or initial setup - this is expected
            Log::debug('Laravel Studio: Could not register permission gates - database may not be ready', [
                'error' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            // Unexpected error - log as warning for investigation
            Log::warning('Laravel Studio: Failed to register permission gates', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Get permissions from database.
     *
     * @return array
     */
    protected function getPermissionsFromDatabase(): array
    {
        return Permission::select(['id', 'name', 'display_name', 'group'])
            ->orderBy('group')
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    /**
     * Sync base permissions from the Permission enum.
     *
     * This creates all permissions defined in the Permission enum,
     * ensuring a consistent set of base permissions.
     *
     * @return array<string> List of synced permission names
     */
    public function syncBasePermissions(): array
    {
        $synced = [];

        foreach (PermissionEnum::grouped() as $group => $permissions) {
            foreach ($permissions as $name => $displayName) {
                Permission::updateOrCreate(
                    ['name' => $name],
                    [
                        'display_name' => $displayName,
                        'group' => $group,
                    ]
                );
                $synced[] = $name;
            }
        }

        return $synced;
    }

    /**
     * Sync permissions from all registered resources.
     *
     * @return array<string> List of synced permission names
     */
    public function syncPermissions(): array
    {
        // First sync base permissions from the enum
        $synced = $this->syncBasePermissions();

        // Then sync from registered resources
        $resources = config('studio.resources', []);

        foreach ($resources as $key => $resourceConfig) {
            // Handle both old format (string) and new format (array with 'class' key)
            $resourceClass = is_array($resourceConfig)
                ? ($resourceConfig['class'] ?? null)
                : $resourceConfig;

            if (!$resourceClass || !class_exists($resourceClass)) {
                continue;
            }

            if (!method_exists($resourceClass, 'permissions')) {
                continue;
            }

            $permissions = $resourceClass::permissions();
            $group = method_exists($resourceClass, 'permissionGroup')
                ? $resourceClass::permissionGroup()
                : ($resourceClass::$label ?? str($key)->plural()->title()->toString());

            foreach ($permissions as $name => $displayName) {
                Permission::updateOrCreate(
                    ['name' => $name],
                    [
                        'display_name' => $displayName,
                        'group' => $group,
                    ]
                );

                if (!in_array($name, $synced)) {
                    $synced[] = $name;
                }
            }
        }

        return $synced;
    }

    /**
     * Get all permissions grouped for UI display.
     *
     * @return array
     */
    public function getGroupedPermissions(): array
    {
        return Permission::allGrouped();
    }

    /**
     * Get all permissions as flat array.
     *
     * @return array
     */
    public function getAllPermissions(): array
    {
        return $this->getPermissionsFromDatabase();
    }

    /**
     * Get permissions for a specific role.
     *
     * @param mixed $role
     * @return array<string>
     */
    public function getRolePermissions($role): array
    {
        if (!method_exists($role, 'permissions')) {
            return [];
        }

        return $role->permissions->pluck('name')->toArray();
    }

    /**
     * Sync permissions to a role.
     *
     * @param mixed $role
     * @param array<string> $permissionNames
     * @return void
     */
    public function syncRolePermissions($role, array $permissionNames): void
    {
        $permissionIds = Permission::whereIn('name', $permissionNames)->pluck('id');
        $role->permissions()->sync($permissionIds);
    }

    /**
     * Add permissions to a role.
     *
     * @param mixed $role
     * @param array<string> $permissionNames
     * @return void
     */
    public function addPermissionsToRole($role, array $permissionNames): void
    {
        $permissionIds = Permission::whereIn('name', $permissionNames)->pluck('id');
        $role->permissions()->syncWithoutDetaching($permissionIds);
    }

    /**
     * Remove permissions from a role.
     *
     * @param mixed $role
     * @param array<string> $permissionNames
     * @return void
     */
    public function removePermissionsFromRole($role, array $permissionNames): void
    {
        $permissionIds = Permission::whereIn('name', $permissionNames)->pluck('id');
        $role->permissions()->detach($permissionIds);
    }

    /**
     * Get permissions for a specific resource.
     *
     * @param string $resourceKey
     * @return array
     */
    public function getResourcePermissions(string $resourceKey): array
    {
        return Permission::forResource($resourceKey)
            ->orderBy('name')
            ->get()
            ->pluck('display_name', 'name')
            ->toArray();
    }

    /**
     * Clean up orphaned permissions not defined in any resource or the base enum.
     *
     * @return int Number of deleted permissions
     */
    public function cleanOrphanedPermissions(): int
    {
        $definedPermissions = collect(PermissionEnum::names());
        $resources = config('studio.resources', []);

        foreach ($resources as $key => $resourceConfig) {
            $resourceClass = is_array($resourceConfig)
                ? ($resourceConfig['class'] ?? null)
                : $resourceConfig;

            if (!$resourceClass || !class_exists($resourceClass)) {
                continue;
            }

            if (method_exists($resourceClass, 'permissions')) {
                $definedPermissions = $definedPermissions->merge(
                    array_keys($resourceClass::permissions())
                );
            }
        }

        return Permission::whereNotIn('name', $definedPermissions->unique())->delete();
    }

    /**
     * Check if authorization is enabled globally.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return config('studio.authorization.enabled', true);
    }

    /**
     * Get the super admin role slug from config.
     *
     * @return string
     */
    public function getSuperAdminRole(): string
    {
        return config('studio.authorization.super_admin_role', 'super_admin');
    }

    /**
     * @deprecated Caching has been removed. This method is kept for backwards compatibility.
     * @return void
     */
    public function clearPermissionCaches(): void
    {
        // No-op: caching has been removed
    }

    /**
     * @deprecated Caching has been removed. This method is kept for backwards compatibility.
     * @param mixed $role
     * @return void
     */
    public function clearRoleUsersCaches($role): void
    {
        // No-op: caching has been removed
    }

    /**
     * @deprecated Caching has been removed. This method is kept for backwards compatibility.
     * @return void
     */
    public function clearAllPermissionCaches(): void
    {
        // No-op: caching has been removed
    }
}
