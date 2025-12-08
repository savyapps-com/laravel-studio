<?php

namespace SavyApps\LaravelStudio\Services;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use SavyApps\LaravelStudio\Models\Permission;

class AuthorizationService
{
    /**
     * Register gates for all permissions.
     */
    public function registerGates(): void
    {
        if (!config('studio.authorization.register_gates', true)) {
            return;
        }

        // Check if permissions table exists
        if (!Schema::hasTable('permissions')) {
            return;
        }

        try {
            Permission::all()->each(function ($permission) {
                Gate::define($permission->name, function ($user) use ($permission) {
                    if (method_exists($user, 'hasPermission')) {
                        return $user->hasPermission($permission->name);
                    }
                    return false;
                });
            });
        } catch (\Exception $e) {
            // Silently fail if database is not available
        }
    }

    /**
     * Sync permissions from all registered resources.
     */
    public function syncPermissions(): array
    {
        $resources = config('studio.resources', []);
        $synced = [];

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
                $synced[] = $name;
            }
        }

        return $synced;
    }

    /**
     * Get all permissions grouped for UI display.
     */
    public function getGroupedPermissions(): array
    {
        return Permission::allGrouped();
    }

    /**
     * Get all permissions as flat array.
     */
    public function getAllPermissions(): array
    {
        return Permission::orderBy('group')
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    /**
     * Get permissions for a specific role.
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
     */
    public function syncRolePermissions($role, array $permissionNames): void
    {
        $permissionIds = Permission::whereIn('name', $permissionNames)->pluck('id');
        $role->permissions()->sync($permissionIds);

        // Clear cache for all users with this role
        if (method_exists($role, 'users')) {
            $role->users->each(function ($user) {
                if (method_exists($user, 'clearPermissionCache')) {
                    $user->clearPermissionCache();
                }
            });
        }
    }

    /**
     * Add permissions to a role.
     */
    public function addPermissionsToRole($role, array $permissionNames): void
    {
        $permissionIds = Permission::whereIn('name', $permissionNames)->pluck('id');
        $role->permissions()->syncWithoutDetaching($permissionIds);

        // Clear cache
        if (method_exists($role, 'users')) {
            $role->users->each(fn($user) => $user->clearPermissionCache());
        }
    }

    /**
     * Remove permissions from a role.
     */
    public function removePermissionsFromRole($role, array $permissionNames): void
    {
        $permissionIds = Permission::whereIn('name', $permissionNames)->pluck('id');
        $role->permissions()->detach($permissionIds);

        // Clear cache
        if (method_exists($role, 'users')) {
            $role->users->each(fn($user) => $user->clearPermissionCache());
        }
    }

    /**
     * Get permissions for a specific resource.
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
     * Clean up orphaned permissions not defined in any resource.
     */
    public function cleanOrphanedPermissions(): int
    {
        $definedPermissions = collect();
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

        $deleted = Permission::whereNotIn('name', $definedPermissions->unique())->delete();

        return $deleted;
    }

    /**
     * Clear permission cache for all users.
     */
    public function clearAllPermissionCaches(): void
    {
        $userModel = config('studio.authorization.models.user', \App\Models\User::class);

        if (!class_exists($userModel)) {
            return;
        }

        $userModel::chunk(100, function ($users) {
            $users->each(function ($user) {
                if (method_exists($user, 'clearPermissionCache')) {
                    $user->clearPermissionCache();
                }
            });
        });
    }
}
