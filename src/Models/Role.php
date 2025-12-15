<?php

namespace SavyApps\LaravelStudio\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use SavyApps\LaravelStudio\Enums\Permission as PermissionEnum;

/**
 * Role model for Laravel Studio RBAC system.
 *
 * This model manages user roles and their associated permissions.
 * Roles can be assigned to users via the role_user pivot table.
 *
 * Default roles:
 * - super_admin: Unrestricted access, bypasses all permission checks
 * - admin: Full access to all features except permission management
 * - user: Regular user with limited access
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Permission> $permissions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 */
class Role extends Model
{
    use HasFactory;

    /**
     * System role slugs that cannot be deleted.
     */
    public const SYSTEM_ROLES = ['super_admin', 'admin', 'user'];

    /**
     * Boot the model.
     *
     * Registers event listeners to protect system roles from deletion
     * and prevent slug changes on system roles.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Prevent deletion of system roles
        static::deleting(function (Role $role) {
            if ($role->isSystemRole()) {
                throw new \RuntimeException(
                    "Cannot delete system role: {$role->slug}. System roles (super_admin, admin, user) are protected."
                );
            }
        });

        // Prevent slug changes on system roles
        static::updating(function (Role $role) {
            if ($role->isSystemRole() && $role->isDirty('slug')) {
                throw new \RuntimeException(
                    "Cannot change slug of system role: {$role->getOriginal('slug')}. System role slugs are protected."
                );
            }
        });
    }

    /**
     * Super admin role slug.
     */
    public const SUPER_ADMIN = 'super_admin';

    /**
     * Admin role slug.
     */
    public const ADMIN = 'admin';

    /**
     * User role slug.
     */
    public const USER = 'user';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
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
        $userModel = config('studio.authorization.models.user', \App\Models\User::class);

        return $this->belongsToMany($userModel, 'role_user')
            ->withTimestamps();
    }

    /**
     * Get the permissions assigned to this role.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')
            ->withTimestamps();
    }

    /**
     * Check if role has a specific permission.
     *
     * @param string $permission The permission name to check
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        return $this->permissions()->where('name', $permission)->exists();
    }

    /**
     * Check if role has any of the given permissions.
     *
     * @param array<string> $permissions The permission names to check
     * @return bool
     */
    public function hasAnyPermission(array $permissions): bool
    {
        return $this->permissions()->whereIn('name', $permissions)->exists();
    }

    /**
     * Check if role has all of the given permissions.
     *
     * @param array<string> $permissions The permission names to check
     * @return bool
     */
    public function hasAllPermissions(array $permissions): bool
    {
        $count = $this->permissions()->whereIn('name', $permissions)->count();

        return $count === count($permissions);
    }

    /**
     * Grant a permission to this role.
     *
     * @param string|Permission $permission The permission name or model
     * @throws \InvalidArgumentException If permission is invalid
     */
    public function grantPermission(string|Permission $permission): void
    {
        if (is_string($permission)) {
            // Validate permission name
            if (!PermissionEnum::isValid($permission)) {
                throw new \InvalidArgumentException("Invalid permission: {$permission}");
            }

            $permission = Permission::where('name', $permission)->firstOrFail();
        }

        if (!$this->hasPermission($permission->name)) {
            $this->permissions()->attach($permission->id);
        }
    }

    /**
     * Grant multiple permissions to this role.
     *
     * @param array<string|Permission> $permissions The permissions to grant
     */
    public function grantPermissions(array $permissions): void
    {
        foreach ($permissions as $permission) {
            $this->grantPermission($permission);
        }
    }

    /**
     * Revoke a permission from this role.
     *
     * @param string|Permission $permission The permission name or model
     */
    public function revokePermission(string|Permission $permission): void
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->first();
        }

        if ($permission) {
            $this->permissions()->detach($permission->id);
        }
    }

    /**
     * Revoke multiple permissions from this role.
     *
     * @param array<string|Permission> $permissions The permissions to revoke
     */
    public function revokePermissions(array $permissions): void
    {
        foreach ($permissions as $permission) {
            $this->revokePermission($permission);
        }
    }

    /**
     * Sync permissions for this role (replaces all existing permissions).
     *
     * @param array<string> $permissions Array of permission names
     */
    public function syncPermissions(array $permissions): void
    {
        $permissionIds = Permission::whereIn('name', $permissions)
            ->pluck('id')
            ->toArray();

        $this->permissions()->sync($permissionIds);
    }

    /**
     * Get all permission names for this role.
     *
     * @return array<string>
     */
    public function getPermissionNames(): array
    {
        return $this->permissions()->pluck('name')->toArray();
    }

    /**
     * Check if this is a system role that cannot be deleted.
     *
     * @return bool
     */
    public function isSystemRole(): bool
    {
        return in_array($this->slug, self::SYSTEM_ROLES, true);
    }

    /**
     * Check if this is the super admin role.
     *
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        return $this->slug === self::SUPER_ADMIN;
    }

    /**
     * Check if this is the admin role.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->slug === self::ADMIN;
    }

    /**
     * Check if this is the user role.
     *
     * @return bool
     */
    public function isUser(): bool
    {
        return $this->slug === self::USER;
    }

    /**
     * Find a role by its slug.
     *
     * @param string $slug The role slug
     * @return static|null
     */
    public static function findBySlug(string $slug): ?static
    {
        return static::where('slug', $slug)->first();
    }

    /**
     * Get the super admin role.
     *
     * @return static|null
     */
    public static function superAdmin(): ?static
    {
        return static::findBySlug(self::SUPER_ADMIN);
    }

    /**
     * Get the admin role.
     *
     * @return static|null
     */
    public static function admin(): ?static
    {
        return static::findBySlug(self::ADMIN);
    }

    /**
     * Get the user role.
     *
     * @return static|null
     */
    public static function user(): ?static
    {
        return static::findBySlug(self::USER);
    }

    /**
     * Scope to only system roles.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSystemRoles($query)
    {
        return $query->whereIn('slug', self::SYSTEM_ROLES);
    }

    /**
     * Scope to exclude system roles.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCustomRoles($query)
    {
        return $query->whereNotIn('slug', self::SYSTEM_ROLES);
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory<static>
     */
    protected static function newFactory()
    {
        return \SavyApps\LaravelStudio\Database\Factories\RoleFactory::new();
    }
}
