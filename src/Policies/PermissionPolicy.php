<?php

namespace SavyApps\LaravelStudio\Policies;

use Illuminate\Database\Eloquent\Model;

/**
 * Policy for Permission resource authorization.
 *
 * This policy handles authorization for permission management operations.
 * Permissions are read-only for most users; only super admins can manage them.
 */
class PermissionPolicy extends StudioPolicy
{
    /**
     * The resource key for permission names.
     *
     * @var string
     */
    protected string $resource = 'permissions';

    /**
     * Determine whether the user can view any models.
     *
     * @param mixed $user
     * @return bool
     */
    public function viewAny($user): bool
    {
        // Admins can view permissions
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        return $this->checkPermission($user, 'view');
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
        // Admins can view permissions
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        return $this->checkPermission($user, 'view');
    }

    /**
     * Determine whether the user can create models.
     *
     * Permissions cannot be created through the UI.
     * They are created by the sync command.
     *
     * @param mixed $user
     * @return bool
     */
    public function create($user): bool
    {
        // Permissions are created via sync command only
        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * Permissions cannot be updated through the UI.
     *
     * @param mixed $user
     * @param Model $model
     * @return bool
     */
    public function update($user, Model $model): bool
    {
        // Permissions are read-only
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * Permissions cannot be deleted through the UI.
     * They are managed by the sync command.
     *
     * @param mixed $user
     * @param Model $model
     * @return bool
     */
    public function delete($user, Model $model): bool
    {
        // Permissions are managed via sync command only
        return false;
    }

    /**
     * Determine whether the user can manage permissions.
     *
     * This includes assigning/revoking permissions to/from roles.
     *
     * @param mixed $user
     * @return bool
     */
    public function manage($user): bool
    {
        return $this->isSuperAdmin($user) || $this->checkPermission($user, 'manage');
    }

    /**
     * Determine whether the user can sync permissions.
     *
     * @param mixed $user
     * @return bool
     */
    public function sync($user): bool
    {
        return $this->isSuperAdmin($user) || $this->checkPermission($user, 'sync');
    }
}
