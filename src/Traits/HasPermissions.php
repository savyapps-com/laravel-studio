<?php

namespace SavyApps\LaravelStudio\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use SavyApps\LaravelStudio\Enums\Permission as PermissionEnum;
use SavyApps\LaravelStudio\Exceptions\InvalidPermissionException;

/**
 * Trait for adding permission checking capabilities to User model.
 *
 * This trait provides methods to check if a user has specific permissions
 * based on their assigned roles. It includes caching for performance and
 * validation to prevent permission typos.
 *
 * Features:
 * - Permission caching with configurable TTL
 * - Super admin bypass (super admins always have all permissions)
 * - RBAC toggle (authorization can be disabled globally)
 * - Permission validation using Permission enum
 *
 * @example
 * use SavyApps\LaravelStudio\Traits\HasPermissions;
 * use SavyApps\LaravelStudio\Enums\Permission;
 *
 * class User extends Authenticatable
 * {
 *     use HasPermissions;
 * }
 *
 * // Check permission
 * $user->hasPermission(Permission::USERS_CREATE);
 */
trait HasPermissions
{
    /**
     * Check if user has a specific permission.
     *
     * @param string $permission The permission name to check
     * @param bool $validate Whether to validate the permission name (default: false for BC)
     * @return bool
     * @throws InvalidPermissionException If validation is enabled and permission is invalid
     */
    public function hasPermission(string $permission, bool $validate = false): bool
    {
        // If RBAC is disabled, all users have all permissions
        if (!$this->isAuthorizationEnabled()) {
            return true;
        }

        // Validate permission name if requested
        if ($validate && !PermissionEnum::isValid($permission)) {
            throw InvalidPermissionException::unknownPermission($permission);
        }

        // Super admin bypasses all checks
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->getCachedPermissions()->contains($permission);
    }

    /**
     * Check if user has a specific permission (strict mode with validation).
     *
     * This method always validates the permission name against the Permission enum.
     * Use this when you want to ensure typos are caught during development.
     *
     * @param string $permission The permission name to check
     * @return bool
     * @throws InvalidPermissionException If permission is invalid
     */
    public function hasPermissionStrict(string $permission): bool
    {
        return $this->hasPermission($permission, true);
    }

    /**
     * Check if user has any of the given permissions.
     *
     * @param array<string> $permissions The permission names to check
     * @return bool
     */
    public function hasAnyPermission(array $permissions): bool
    {
        if (!$this->isAuthorizationEnabled()) {
            return true;
        }

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
     *
     * @param array<string> $permissions The permission names to check
     * @return bool
     */
    public function hasAllPermissions(array $permissions): bool
    {
        if (!$this->isAuthorizationEnabled()) {
            return true;
        }

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
     *
     * @return Collection<int, string>
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
     *
     * @return Collection<int, string>
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
     *
     * @return void
     */
    public function clearPermissionCache(): void
    {
        $cacheKey = config('studio.cache.prefix', 'studio_') . 'user_permissions_' . $this->id;
        Cache::forget($cacheKey);
    }

    /**
     * Check if user is super admin.
     *
     * @return bool
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
                return $roles->pluck('slug')->contains($superAdminRole);
            }

            if (is_array($roles)) {
                return in_array($superAdminRole, $roles);
            }
        }

        return false;
    }

    /**
     * Get permission names as array (useful for API responses).
     *
     * @return array<string>
     */
    public function getPermissionNames(): array
    {
        // Super admin gets all permissions
        if ($this->isSuperAdmin()) {
            return PermissionEnum::names();
        }

        return $this->getCachedPermissions()->toArray();
    }

    /**
     * Refresh permissions from database and update cache.
     *
     * @return Collection<int, string>
     */
    public function refreshPermissions(): Collection
    {
        $this->clearPermissionCache();

        return $this->getCachedPermissions();
    }

    /**
     * Check if user can perform action on a resource.
     *
     * @param string $resource The resource name (e.g., 'users')
     * @param string $action The action name (e.g., 'create')
     * @return bool
     */
    public function canResource(string $resource, string $action): bool
    {
        return $this->hasPermission("{$resource}.{$action}");
    }

    /**
     * Check if authorization is enabled globally.
     *
     * @return bool
     */
    protected function isAuthorizationEnabled(): bool
    {
        return config('studio.authorization.enabled', true);
    }

    /**
     * Check if user can perform an action using Permission enum constant.
     *
     * @param string $permission A Permission enum constant
     * @return bool
     *
     * @example
     * use SavyApps\LaravelStudio\Enums\Permission;
     * $user->can(Permission::USERS_CREATE);
     */
    public function hasPermissionFor(string $permission): bool
    {
        return $this->hasPermission($permission);
    }
}
