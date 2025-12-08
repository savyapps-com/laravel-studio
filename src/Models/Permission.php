<?php

namespace SavyApps\LaravelStudio\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'group',
        'description',
    ];

    /**
     * Get the roles that have this permission.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            config('studio.authorization.models.role', \App\Models\Role::class),
            'role_permissions'
        );
    }

    /**
     * Scope to filter by group.
     */
    public function scopeInGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Scope to filter by name prefix (e.g., 'users.' for all user permissions).
     */
    public function scopeForResource($query, string $resourceKey)
    {
        return $query->where('name', 'like', "{$resourceKey}.%");
    }

    /**
     * Get all permissions grouped by their group name.
     */
    public static function allGrouped(): array
    {
        return static::orderBy('group')
            ->orderBy('name')
            ->get()
            ->groupBy('group')
            ->map(fn($permissions) => $permissions->pluck('display_name', 'name'))
            ->toArray();
    }

    /**
     * Find a permission by name.
     */
    public static function findByName(string $name): ?static
    {
        return static::where('name', $name)->first();
    }

    /**
     * Get or create a permission by name.
     */
    public static function findOrCreateByName(string $name, array $attributes = []): static
    {
        return static::firstOrCreate(
            ['name' => $name],
            array_merge(['display_name' => $name], $attributes)
        );
    }
}
