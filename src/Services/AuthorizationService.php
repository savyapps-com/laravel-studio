<?php

namespace SavyApps\LaravelStudio\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use SavyApps\LaravelStudio\Models\Permission;

class AuthorizationService
{
    protected const CACHE_KEY_PERMISSIONS = 'studio.permissions.all';
    protected const CACHE_KEY_GROUPED = 'studio.permissions.grouped';

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
            // Use cached permissions to avoid N+1 query on every request
            $permissions = $this->getCachedPermissions();

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
     * Get cached permissions list.
     */
    protected function getCachedPermissions(): array
    {
        if (!config('studio.authorization.cache.enabled', true)) {
            return Permission::select(['id', 'name', 'display_name', 'group'])
                ->orderBy('group')
                ->orderBy('name')
                ->get()
                ->toArray();
        }

        $ttl = config('studio.authorization.cache.ttl', 3600);
        $cacheKey = config('studio.authorization.cache.prefix', 'studio_permissions_') . 'all';

        return Cache::remember($cacheKey, $ttl, function () {
            return Permission::select(['id', 'name', 'display_name', 'group'])
                ->orderBy('group')
                ->orderBy('name')
                ->get()
                ->toArray();
        });
    }

    /**
     * Clear all permission caches.
     */
    public function clearPermissionCaches(): void
    {
        $prefix = config('studio.authorization.cache.prefix', 'studio_permissions_');
        Cache::forget($prefix . 'all');
        Cache::forget($prefix . 'grouped');
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
     * Get all permissions as flat array (cached).
     */
    public function getAllPermissions(): array
    {
        return $this->getCachedPermissions();
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
        $this->clearRoleUsersCaches($role);

        // Clear permission caches
        $this->clearPermissionCaches();
    }

    /**
     * Add permissions to a role.
     */
    public function addPermissionsToRole($role, array $permissionNames): void
    {
        $permissionIds = Permission::whereIn('name', $permissionNames)->pluck('id');
        $role->permissions()->syncWithoutDetaching($permissionIds);

        // Clear cache
        $this->clearRoleUsersCaches($role);
        $this->clearPermissionCaches();
    }

    /**
     * Remove permissions from a role.
     */
    public function removePermissionsFromRole($role, array $permissionNames): void
    {
        $permissionIds = Permission::whereIn('name', $permissionNames)->pluck('id');
        $role->permissions()->detach($permissionIds);

        // Clear cache
        $this->clearRoleUsersCaches($role);
        $this->clearPermissionCaches();
    }

    /**
     * Clear permission caches for all users with a given role.
     * Uses chunking to avoid memory issues with large user bases.
     */
    protected function clearRoleUsersCaches($role): void
    {
        if (!method_exists($role, 'users')) {
            return;
        }

        // Use chunking to avoid loading all users at once
        $role->users()->chunk(100, function ($users) {
            foreach ($users as $user) {
                if (method_exists($user, 'clearPermissionCache')) {
                    $user->clearPermissionCache();
                }
            }
        });
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
