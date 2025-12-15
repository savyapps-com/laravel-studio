<?php

namespace SavyApps\LaravelStudio\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

trait HasPermissions
{
    /**
     * Check if user has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        // Super admin bypasses all checks
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->getCachedPermissions()->contains($permission);
    }

    /**
     * Check if user has any of the given permissions.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has all of the given permissions.
     */
    public function hasAllPermissions(array $permissions): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get all permissions for user (cached).
     */
    public function getCachedPermissions(): Collection
    {
        if (!config('studio.cache.enabled', true)) {
            return $this->getAllPermissions();
        }

        $cacheKey = config('studio.cache.prefix', 'studio_') . 'user_permissions_' . $this->id;
        $ttl = config('studio.cache.ttl', 3600);

        return Cache::remember($cacheKey, $ttl, function () {
            return $this->getAllPermissions();
        });
    }

    /**
     * Get all permissions from user's roles.
     */
    public function getAllPermissions(): Collection
    {
        // Check if user has roles relationship
        if (!method_exists($this, 'roles')) {
            return collect();
        }

        return $this->roles
            ->flatMap(function ($role) {
                // Check if role has permissions relationship
                if (!method_exists($role, 'permissions')) {
                    return collect();
                }
                return $role->permissions;
            })
            ->pluck('name')
            ->unique();
    }

    /**
     * Clear permission cache for this user.
     */
    public function clearPermissionCache(): void
    {
        $cacheKey = config('studio.cache.prefix', 'studio_') . 'user_permissions_' . $this->id;
        Cache::forget($cacheKey);
    }

    /**
     * Check if user is super admin.
     */
    public function isSuperAdmin(): bool
    {
        $superAdminRole = config('studio.authorization.super_admin_role', 'super_admin');

        // Use hasRole method if available
        if (method_exists($this, 'hasRole')) {
            return $this->hasRole($superAdminRole);
        }

        // Fallback: check roles relationship
        if (method_exists($this, 'roles') || property_exists($this, 'roles')) {
            $roles = $this->roles;

            if (is_object($roles) && method_exists($roles, 'pluck')) {
                return $roles->pluck('name')->contains($superAdminRole);
            }

            if (is_array($roles)) {
                return in_array($superAdminRole, $roles);
            }
        }

        return false;
    }

    /**
     * Get permission names as array (useful for API responses).
     */
    public function getPermissionNames(): array
    {
        return $this->getCachedPermissions()->toArray();
    }

    /**
     * Refresh permissions from database and update cache.
     */
    public function refreshPermissions(): Collection
    {
        $this->clearPermissionCache();
        return $this->getCachedPermissions();
    }

    /**
     * Check if user can perform action on a resource.
     */
    public function canResource(string $resource, string $action): bool
    {
        return $this->hasPermission("{$resource}.{$action}");
    }
}
