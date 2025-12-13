<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    /**
     * Determine whether the user can view any roles.
     * Admins and super_admins can view the roles list.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    /**
     * Determine whether the user can view a role.
     * Admins and super_admins can view role details.
     */
    public function view(User $user, Role $role): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    /**
     * Determine whether the user can create roles.
     * Only super_admin can create roles.
     */
    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can update a role.
     * Only super_admin can update roles.
     * Protect the super_admin role from being modified.
     */
    public function update(User $user, Role $role): bool
    {
        // Prevent modifying the super_admin role
        if ($role->slug === 'super_admin') {
            return false;
        }

        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can delete a role.
     * Only super_admin can delete roles.
     * Protect system roles from deletion.
     */
    public function delete(User $user, Role $role): bool
    {
        // Prevent deleting system roles
        $protectedRoles = ['super_admin', 'admin', 'user'];
        if (in_array($role->slug, $protectedRoles)) {
            return false;
        }

        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can bulk delete roles.
     * Only super_admin can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can bulk update roles.
     * Only super_admin can bulk update.
     */
    public function updateAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }
}
