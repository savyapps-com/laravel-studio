<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    /**
     * Get the users that have this role.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user')
            ->withTimestamps();
    }

    /**
     * Get the permissions assigned to this role.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            \SavyApps\LaravelStudio\Models\Permission::class,
            'role_permissions'
        )->withTimestamps();
    }

    /**
     * Check if role has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        return $this->permissions()->where('name', $permission)->exists();
    }

    /**
     * Grant a permission to this role.
     */
    public function grantPermission(string|\SavyApps\LaravelStudio\Models\Permission $permission): void
    {
        if (is_string($permission)) {
            $permission = \SavyApps\LaravelStudio\Models\Permission::where('name', $permission)->firstOrFail();
        }

        if (!$this->hasPermission($permission->name)) {
            $this->permissions()->attach($permission->id);
        }
    }

    /**
     * Revoke a permission from this role.
     */
    public function revokePermission(string|\SavyApps\LaravelStudio\Models\Permission $permission): void
    {
        if (is_string($permission)) {
            $permission = \SavyApps\LaravelStudio\Models\Permission::where('name', $permission)->first();
        }

        if ($permission) {
            $this->permissions()->detach($permission->id);
        }
    }

    /**
     * Sync permissions for this role.
     */
    public function syncPermissions(array $permissions): void
    {
        $permissionIds = \SavyApps\LaravelStudio\Models\Permission::whereIn('name', $permissions)
            ->pluck('id')
            ->toArray();

        $this->permissions()->sync($permissionIds);
    }
}
