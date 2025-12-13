<?php

namespace App\Policies;

use App\Models\User;
use SavyApps\LaravelStudio\Models\Panel;

class PanelPolicy
{
    /**
     * Determine whether the user can view any panels.
     * Only super_admin can view panels list.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can view a panel.
     * Only super_admin can view panel details.
     */
    public function view(User $user, Panel $panel): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can create panels.
     * Only super_admin can create panels.
     */
    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can update a panel.
     * Only super_admin can update panels.
     */
    public function update(User $user, Panel $panel): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can delete a panel.
     * Only super_admin can delete panels.
     * Cannot delete the default panel.
     */
    public function delete(User $user, Panel $panel): bool
    {
        // Cannot delete the default panel
        if ($panel->is_default) {
            return false;
        }

        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can bulk delete panels.
     * Only super_admin can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can bulk update panels.
     * Only super_admin can bulk update.
     */
    public function updateAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }
}
