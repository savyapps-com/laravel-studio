<?php

namespace SavyApps\LaravelStudio\Policies;

use Illuminate\Database\Eloquent\Model;
use SavyApps\LaravelStudio\Enums\Permission;

/**
 * Policy for User resource authorization.
 *
 * This policy handles authorization for user management operations.
 * Users can always view and update their own profile.
 */
class UserPolicy extends StudioPolicy
{
    /**
     * The resource key for permission names.
     *
     * @var string
     */
    protected string $resource = 'users';

    /**
     * Determine whether the user can view the model.
     *
     * Users can always view their own profile.
     *
     * @param mixed $user
     * @param Model $model
     * @return bool
     */
    public function view($user, Model $model): bool
    {
        // Users can view their own profile
        if ($user->id === $model->id) {
            return true;
        }

        return parent::view($user, $model);
    }

    /**
     * Determine whether the user can update the model.
     *
     * Users can update their own profile (limited fields).
     * Full update requires the users.update permission.
     *
     * @param mixed $user
     * @param Model $model
     * @return bool
     */
    public function update($user, Model $model): bool
    {
        // Users can update their own profile
        if ($user->id === $model->id) {
            return true;
        }

        return parent::update($user, $model);
    }

    /**
     * Determine whether the user can update email addresses.
     *
     * @param mixed $user
     * @param Model $model
     * @return bool
     */
    public function updateEmail($user, Model $model): bool
    {
        // Users can update their own email
        if ($user->id === $model->id) {
            return true;
        }

        return $this->checkPermission($user, 'update.email');
    }

    /**
     * Determine whether the user can update passwords.
     *
     * @param mixed $user
     * @param Model $model
     * @return bool
     */
    public function updatePassword($user, Model $model): bool
    {
        // Users can update their own password
        if ($user->id === $model->id) {
            return true;
        }

        return $this->checkPermission($user, 'update.password');
    }

    /**
     * Determine whether the user can update roles.
     *
     * @param mixed $user
     * @param Model $model
     * @return bool
     */
    public function updateRoles($user, Model $model): bool
    {
        // Users cannot update their own roles
        if ($user->id === $model->id) {
            return false;
        }

        return $this->checkPermission($user, 'update.roles');
    }

    /**
     * Determine whether the user can impersonate another user.
     *
     * @param mixed $user
     * @param Model $model
     * @return bool
     */
    public function impersonate($user, Model $model): bool
    {
        // Cannot impersonate self
        if ($user->id === $model->id) {
            return false;
        }

        // Cannot impersonate super admins unless you are one
        if (method_exists($model, 'isSuperAdmin') && $model->isSuperAdmin()) {
            if (!method_exists($user, 'isSuperAdmin') || !$user->isSuperAdmin()) {
                return false;
            }
        }

        return $this->checkPermission($user, 'impersonate');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * Users cannot delete themselves.
     * Super admins cannot be deleted by non-super admins.
     *
     * @param mixed $user
     * @param Model $model
     * @return bool
     */
    public function delete($user, Model $model): bool
    {
        // Users cannot delete themselves
        if ($user->id === $model->id) {
            return false;
        }

        // Non-super admins cannot delete super admins
        if (method_exists($model, 'isSuperAdmin') && $model->isSuperAdmin()) {
            if (!method_exists($user, 'isSuperAdmin') || !$user->isSuperAdmin()) {
                return false;
            }
        }

        return parent::delete($user, $model);
    }
}
