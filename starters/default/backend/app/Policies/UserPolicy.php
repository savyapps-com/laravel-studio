<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any users.
     * Admins and super_admins can view the users list.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    /**
     * Determine whether the user can view a user.
     * Admins can view any user, users can view themselves.
     */
    public function view(User $user, User $model): bool
    {
        // Users can always view their own profile
        if ($user->id === $model->id) {
            return true;
        }

        return $user->isSuperAdmin() || $user->isAdmin();
    }

    /**
     * Determine whether the user can create users.
     * Only admins and super_admins can create users.
     */
    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    /**
     * Determine whether the user can update a user.
     * - Super admin can update anyone
     * - Admin can update non-admin users and themselves
     * - Users can update themselves
     */
    public function update(User $user, User $model): bool
    {
        // Super admin can update anyone
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Users can update themselves
        if ($user->id === $model->id) {
            return true;
        }

        // Admin can update non-admin/non-super_admin users
        if ($user->isAdmin()) {
            // Cannot update super_admin or other admins
            if ($model->isSuperAdmin() || $model->isAdmin()) {
                return false;
            }
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete a user.
     * - Super admin can delete anyone except themselves
     * - Admin can delete non-admin users (not themselves)
     */
    public function delete(User $user, User $model): bool
    {
        // Cannot delete yourself
        if ($user->id === $model->id) {
            return false;
        }

        // Super admin can delete anyone else
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Admin can delete non-admin/non-super_admin users
        if ($user->isAdmin()) {
            if ($model->isSuperAdmin() || $model->isAdmin()) {
                return false;
            }
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk delete users.
     * Only super_admin can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can bulk update users.
     * Only admins and super_admins can bulk update.
     */
    public function updateAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }
}
