<?php

namespace SavyApps\LaravelStudio\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Panel extends Model
{
    /**
     * Cache keys used by PanelService.
     */
    protected const CACHE_KEY_ALL_PANELS = 'studio_panels_all';
    protected const CACHE_KEY_DB_PANELS = 'studio_panels_db';

    /**
     * Boot the model and register event listeners.
     */
    protected static function booted(): void
    {
        // Clear panel cache when any panel is created, updated, or deleted
        static::saved(function () {
            static::clearPanelCache();
        });

        static::deleted(function () {
            static::clearPanelCache();
        });
    }

    /**
     * Clear panel cache.
     */
    public static function clearPanelCache(): void
    {
        Cache::forget(self::CACHE_KEY_ALL_PANELS);
        Cache::forget(self::CACHE_KEY_DB_PANELS);
    }

    protected $fillable = [
        'key',
        'label',
        'path',
        'icon',
        'role',
        'roles',
        'middleware',
        'resources',
        'features',
        'menu',
        'settings',
        'is_active',
        'is_default',
        'priority',
        'allow_registration',
        'default_role',
    ];

    protected $casts = [
        'roles' => 'array',
        'middleware' => 'array',
        'resources' => 'array',
        'features' => 'array',
        'menu' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'priority' => 'integer',
        'allow_registration' => 'boolean',
    ];

    /**
     * Scope to filter active panels.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter default panel.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope to filter panels by role.
     */
    public function scopeForRole($query, string $role)
    {
        return $query->where(function ($q) use ($role) {
            $q->where('role', $role)
                ->orWhereJsonContains('roles', $role);
        });
    }

    /**
     * Scope to order by priority.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('priority')->orderBy('label');
    }

    /**
     * Get full middleware array including default API middleware.
     */
    public function getFullMiddleware(): array
    {
        $defaultMiddleware = ['api', 'auth:sanctum'];
        $customMiddleware = $this->middleware ?? [];
        $panelMiddleware = ["panel:{$this->key}"];

        return array_merge($defaultMiddleware, $customMiddleware, $panelMiddleware);
    }

    /**
     * Convert model to config array format (compatible with config/studio.php panels).
     */
    public function toConfig(): array
    {
        return [
            'label' => $this->label,
            'path' => $this->path,
            'icon' => $this->icon,
            'role' => $this->role,
            'roles' => $this->roles ?? [],
            'middleware' => $this->getFullMiddleware(),
            'resources' => $this->resources ?? [],
            'features' => $this->features ?? [],
            'menu' => $this->menu ?? [],
            'settings' => $this->settings ?? [
                'layout' => 'classic',
                'theme' => 'light',
            ],
            'allow_registration' => $this->allow_registration ?? false,
            'default_role' => $this->default_role,
        ];
    }

    /**
     * Find a panel by key.
     */
    public static function findByKey(string $key): ?static
    {
        return static::where('key', $key)->first();
    }

    /**
     * Get default panel.
     */
    public static function getDefault(): ?static
    {
        return static::active()->default()->first()
            ?? static::active()->ordered()->first();
    }

    /**
     * Check if user can access this panel.
     */
    public function userCanAccess($user): bool
    {
        if (! $user) {
            return false;
        }

        $requiredRoles = $this->roles ?? ($this->role ? [$this->role] : [$this->key]);

        if (method_exists($user, 'hasRole')) {
            foreach ($requiredRoles as $role) {
                if ($user->hasRole($role)) {
                    return true;
                }
            }

            return false;
        }

        if (method_exists($user, 'roles') || property_exists($user, 'roles')) {
            $userRoles = $user->roles;

            if (is_object($userRoles) && method_exists($userRoles, 'pluck')) {
                $roleNames = $userRoles->pluck('slug')->merge($userRoles->pluck('name'))->toArray();
                foreach ($requiredRoles as $role) {
                    if (in_array($role, $roleNames)) {
                        return true;
                    }
                }
            }

            if (is_array($userRoles)) {
                foreach ($requiredRoles as $role) {
                    if (in_array($role, $userRoles)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Set as default panel (unsets other defaults).
     */
    public function setAsDefault(): void
    {
        static::where('is_default', true)->update(['is_default' => false]);
        $this->update(['is_default' => true]);
    }

    /**
     * Add a resource to this panel.
     */
    public function addResource(string $resource): void
    {
        $resources = $this->resources ?? [];
        if (! in_array($resource, $resources)) {
            $resources[] = $resource;
            $this->update(['resources' => $resources]);
        }
    }

    /**
     * Remove a resource from this panel.
     */
    public function removeResource(string $resource): void
    {
        $resources = $this->resources ?? [];
        $this->update(['resources' => array_values(array_diff($resources, [$resource]))]);
    }

    /**
     * Check if panel has a specific resource.
     */
    public function hasResource(string $resource): bool
    {
        return in_array($resource, $this->resources ?? []);
    }

    /**
     * Add a feature to this panel.
     */
    public function addFeature(string $feature): void
    {
        $features = $this->features ?? [];
        if (! in_array($feature, $features)) {
            $features[] = $feature;
            $this->update(['features' => $features]);
        }
    }

    /**
     * Remove a feature from this panel.
     */
    public function removeFeature(string $feature): void
    {
        $features = $this->features ?? [];
        $this->update(['features' => array_values(array_diff($features, [$feature]))]);
    }

    /**
     * Check if panel has a specific feature.
     */
    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }
}
