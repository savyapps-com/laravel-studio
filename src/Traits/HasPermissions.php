<?php

namespace SavyApps\LaravelStudio\Traits;

use Illuminate\Support\Collection;
use SavyApps\LaravelStudio\Enums\Permission as PermissionEnum;
use SavyApps\LaravelStudio\Exceptions\InvalidPermissionException;

/**
 * Trait for adding permission checking capabilities to User model.
 *
 * This trait provides methods to check if a user has specific permissions
 * based on their assigned roles.
 *
 * Features:
 * - Super admin bypass (super admins always have all permissions)
 * - RBAC toggle (authorization can be disabled globally)
 * - Permission validation using Permission enum
 * - Hierarchical permissions (optional)
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
     * Supports hierarchical permissions where higher-level permissions
     * automatically grant access to lower-level ones (e.g., 'delete' implies 'update', 'view', 'list').
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

        $userPermissions = $this->getCachedPermissions();

        // Direct permission check
        if ($userPermissions->contains($permission)) {
            return true;
        }

        // Check hierarchical permissions if enabled
        if (config('studio.authorization.use_hierarchy', true)) {
            return $this->hasPermissionViaHierarchy($permission, $userPermissions);
        }

        return false;
    }

    /**
     * Check if user has permission via hierarchical inheritance.
     *
     * For example, if the user has 'users.delete' permission and we're checking
     * for 'users.view', this will return true because 'delete' implies 'view'.
     *
     * @param string $permission The permission name to check
     * @param Collection $userPermissions The user's direct permissions
     * @return bool
     */
    protected function hasPermissionViaHierarchy(string $permission, Collection $userPermissions): bool
    {
        // Parse the permission into resource and action
        $parsed = PermissionEnum::parse($permission);
        if (!$parsed) {
            return false;
        }

        $resource = $parsed['resource'];
        $requestedAction = $parsed['action'];
        $hierarchy = config('studio.authorization.hierarchy', []);

        // Check each higher-level permission that might imply this one
        foreach ($hierarchy as $higherAction => $impliedActions) {
            // Skip if the requested action is not implied by this higher action
            if (!in_array($requestedAction, $impliedActions, true)) {
                continue;
            }

            // Check if user has the higher-level permission for this resource
            $higherPermission = "{$resource}.{$higherAction}";
            if ($userPermissions->contains($higherPermission)) {
                return true;
            }

            // Recursively check if user has an even higher permission
            // that implies the higher permission (e.g., bulk.delete -> delete -> view)
            if ($this->hasPermissionViaHierarchy($higherPermission, $userPermissions)) {
                return true;
            }
        }

        return false;
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
     * Get all permissions for user.
     *
     * @return Collection<int, string>
     */
    public function getCachedPermissions(): Collection
    {
        return $this->getAllPermissions();
    }

    /**
     * Get all permissions from user's roles.
     *
     * Uses eager loading to prevent N+1 queries when fetching
     * permissions from multiple roles.
     *
     * @return Collection<int, string>
     */
    public function getAllPermissions(): Collection
    {
        // Check if user has roles relationship
        if (!method_exists($this, 'roles')) {
            return collect();
        }

        // Use eager loading to prevent N+1 queries
        return $this->roles()
            ->with('permissions')
            ->get()
            ->flatMap(fn($role) => $role->permissions)
            ->pluck('name')
            ->unique()
            ->values();
    }

    /**
     * Clear permission cache for this user.
     *
     * @deprecated Caching has been removed. This method is kept for backwards compatibility.
     * @return void
     */
    public function clearPermissionCache(): void
    {
        // No-op: caching has been removed
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
     * Refresh permissions from database.
     *
     * @deprecated Caching has been removed. This method now just returns getAllPermissions().
     * @return Collection<int, string>
     */
    public function refreshPermissions(): Collection
    {
        return $this->getAllPermissions();
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
