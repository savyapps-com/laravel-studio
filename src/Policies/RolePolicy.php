<?php

namespace SavyApps\LaravelStudio\Policies;

use Illuminate\Database\Eloquent\Model;
use SavyApps\LaravelStudio\Models\Role;

/**
 * Policy for Role resource authorization.
 *
 * This policy handles authorization for role management operations.
 * System roles (super_admin, admin, user) have special protections.
 */
class RolePolicy extends StudioPolicy
{
    /**
     * The resource key for permission names.
     *
     * @var string
     */
    protected string $resource = 'roles';

    /**
     * Determine whether the user can create models.
     *
     * Only super admins can create new roles.
     *
     * @param mixed $user
     * @return bool
     */
    public function create($user): bool
    {
        // Only super admins can create roles
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return parent::create($user);
    }

    /**
     * Determine whether the user can update the model.
     *
     * Super admin role cannot be modified.
     * Only super admins can modify system roles.
     *
     * @param mixed $user
     * @param Model $model
     * @return bool
     */
    public function update($user, Model $model): bool
    {
        // Super admin role cannot be modified
        if ($model instanceof Role && $model->isSuperAdmin()) {
            return false;
        }

        // Only super admins can modify system roles
        if ($model instanceof Role && $model->isSystemRole()) {
            return $this->isSuperAdmin($user);
        }

        return parent::update($user, $model);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * System roles (super_admin, admin, user) cannot be deleted.
     * Only super admins can delete roles.
     *
     * @param mixed $user
     * @param Model $model
     * @return bool
     */
    public function delete($user, Model $model): bool
    {
        // System roles cannot be deleted
        if ($model instanceof Role && $model->isSystemRole()) {
            return false;
        }

        // Only super admins can delete roles
        if (!$this->isSuperAdmin($user)) {
            return false;
        }

        return parent::delete($user, $model);
    }

    /**
     * Determine whether the user can bulk delete roles.
     *
     * Only super admins can bulk delete roles.
     *
     * @param mixed $user
     * @return bool
     */
    public function deleteAny($user): bool
    {
        return $this->isSuperAdmin($user);
    }

    /**
     * Determine whether the user can bulk update roles.
     *
     * Only super admins can bulk update roles.
     *
     * @param mixed $user
     * @return bool
     */
    public function updateAny($user): bool
    {
        return $this->isSuperAdmin($user);
    }

    /**
     * Determine whether the user can assign this role to users.
     *
     * Super admin role can only be assigned by super admins.
     *
     * @param mixed $user
     * @param Model $model
     * @return bool
     */
    public function assign($user, Model $model): bool
    {
        // Super admin role can only be assigned by super admins
        if ($model instanceof Role && $model->isSuperAdmin()) {
            return $this->isSuperAdmin($user);
        }

        return $this->checkPermission($user, 'assign');
    }

    /**
     * Determine whether the user can manage permissions for this role.
     *
     * @param mixed $user
     * @param Model $model
     * @return bool
     */
    public function managePermissions($user, Model $model): bool
    {
        // Cannot manage super admin role permissions
        if ($model instanceof Role && $model->isSuperAdmin()) {
            return false;
        }

        // Only super admins can manage role permissions
        return $this->isSuperAdmin($user);
    }
}
