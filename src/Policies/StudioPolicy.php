<?php

namespace SavyApps\LaravelStudio\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;

/**
 * Base policy class for Laravel Studio resources.
 *
 * This abstract class provides a foundation for resource policies with:
 * - RBAC toggle support (authorization can be disabled globally)
 * - Super admin bypass (super admins always pass authorization)
 * - Permission-based authorization using the Permission enum
 *
 * Extend this class to create policies for your resources:
 *
 * @example
 * class ProductPolicy extends StudioPolicy
 * {
 *     protected string $resource = 'products';
 *
 *     // Override methods for custom logic if needed
 *     public function update($user, Model $model): bool
 *     {
 *         // Custom logic: users can only update their own products
 *         if ($model->user_id === $user->id) {
 *             return true;
 *         }
 *
 *         return parent::update($user, $model);
 *     }
 * }
 */
abstract class StudioPolicy
{
    use HandlesAuthorization;

    /**
     * The resource key used for permission names.
     * This should match the resource key in your permission names.
     *
     * @example For 'users.create' permission, set $resource = 'users'
     *
     * @var string
     */
    protected string $resource;

    /**
     * Perform pre-authorization checks.
     *
     * This method runs before any other policy method and handles:
     * - RBAC disabled: Returns true (all access allowed)
     * - Super admin: Returns true (bypasses all checks)
     *
     * @param mixed $user The authenticated user
     * @param string $ability The ability being checked
     * @return bool|null Null to continue to specific check, true/false to stop
     */
    public function before($user, string $ability): ?bool
    {
        // If RBAC is disabled, allow everything
        if (!$this->isAuthorizationEnabled()) {
            return true;
        }

        // Super admin bypasses all checks
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // Continue to specific policy method
        return null;
    }

    /**
     * Determine whether the user can view any models.
     *
     * @param mixed $user
     * @return bool
     */
    public function viewAny($user): bool
    {
        return $this->checkPermission($user, 'list');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param mixed $user
     * @param Model $model
     * @return bool
     */
    public function view($user, Model $model): bool
    {
        return $this->checkPermission($user, 'view');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param mixed $user
     * @return bool
     */
    public function create($user): bool
    {
        return $this->checkPermission($user, 'create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param mixed $user
     * @param Model $model
     * @return bool
     */
    public function update($user, Model $model): bool
    {
        return $this->checkPermission($user, 'update');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param mixed $user
     * @param Model $model
     * @return bool
     */
    public function delete($user, Model $model): bool
    {
        return $this->checkPermission($user, 'delete');
    }

    /**
     * Determine whether the user can bulk delete models.
     *
     * @param mixed $user
     * @return bool
     */
    public function deleteAny($user): bool
    {
        return $this->checkPermission($user, 'delete');
    }

    /**
     * Determine whether the user can bulk update models.
     *
     * @param mixed $user
     * @return bool
     */
    public function updateAny($user): bool
    {
        return $this->checkPermission($user, 'update');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param mixed $user
     * @param Model $model
     * @return bool
     */
    public function restore($user, Model $model): bool
    {
        return $this->checkPermission($user, 'update');
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param mixed $user
     * @param Model $model
     * @return bool
     */
    public function forceDelete($user, Model $model): bool
    {
        return $this->checkPermission($user, 'delete');
    }

    /**
     * Determine whether the user can export models.
     *
     * @param mixed $user
     * @return bool
     */
    public function export($user): bool
    {
        return $this->checkPermission($user, 'export');
    }

    /**
     * Check if the user has the specified permission for this resource.
     *
     * @param mixed $user The user to check
     * @param string $action The action (list, view, create, update, delete, etc.)
     * @return bool
     */
    protected function checkPermission($user, string $action): bool
    {
        $permission = "{$this->resource}.{$action}";

        if (method_exists($user, 'hasPermission')) {
            return $user->hasPermission($permission);
        }

        return false;
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
     * Check if the user is a super admin.
     *
     * @param mixed $user
     * @return bool
     */
    protected function isSuperAdmin($user): bool
    {
        if (method_exists($user, 'isSuperAdmin')) {
            return $user->isSuperAdmin();
        }

        return false;
    }

    /**
     * Get the resource key for this policy.
     *
     * @return string
     */
    public function getResource(): string
    {
        return $this->resource;
    }
}
