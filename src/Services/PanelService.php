<?php

namespace SavyApps\LaravelStudio\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class PanelService
{
    /**
     * In-memory cached panels to avoid repeated cache lookups within a request.
     */
    protected ?array $cachedPanels = null;

    /**
     * Get all configured panels from config file.
     */
    public function getAllPanels(): array
    {
        if ($this->cachedPanels !== null) {
            return $this->cachedPanels;
        }

        $this->cachedPanels = config('studio.panels', []);
        return $this->cachedPanels;
    }

    /**
     * Clear cached panels.
     */
    public function clearCache(): void
    {
        $this->cachedPanels = null;
        Cache::forget('config');
    }

    /**
     * Get a specific panel configuration.
     */
    public function getPanel(string $key): ?array
    {
        $panels = $this->getAllPanels();

        return $panels[$key] ?? null;
    }

    /**
     * Get panels accessible by the current user (based on their roles).
     */
    public function getAccessiblePanels($user = null): array
    {
        $user = $user ?? Auth::user();

        if (! $user) {
            return [];
        }

        return collect($this->getAllPanels())
            ->filter(fn ($config, $key) => $this->userCanAccessPanel($user, $key))
            ->mapWithKeys(fn ($config, $key) => [$key => array_merge($config, ['key' => $key])])
            ->toArray();
    }

    /**
     * Get resources available in a panel.
     */
    public function getPanelResources(string $panel): array
    {
        $panelConfig = $this->getPanel($panel);
        if (! $panelConfig) {
            return [];
        }

        $resourceKeys = $panelConfig['resources'] ?? [];
        $allResources = config('studio.resources', []);

        return collect($resourceKeys)
            ->mapWithKeys(fn ($key) => [$key => $allResources[$key] ?? null])
            ->filter()
            ->toArray();
    }

    /**
     * Check if a resource is available in a panel.
     */
    public function panelHasResource(string $panel, string $resource): bool
    {
        $panelConfig = $this->getPanel($panel);

        return in_array($resource, $panelConfig['resources'] ?? []);
    }

    /**
     * Get menu items for a panel.
     */
    public function getPanelMenu(string $panel): array
    {
        $panelConfig = $this->getPanel($panel);

        return $this->resolveMenuItems($panelConfig['menu'] ?? [], $panel);
    }

    /**
     * Resolve menu items with resource/feature details.
     */
    protected function resolveMenuItems(array $items, string $panel): array
    {
        return collect($items)->map(function ($item) use ($panel) {
            if ($item['type'] === 'resource') {
                $resourceConfig = config("studio.resources.{$item['resource']}");

                return [
                    'type' => 'link',
                    'label' => $resourceConfig['label'] ?? str($item['resource'])->title()->toString(),
                    'icon' => $resourceConfig['icon'] ?? 'folder',
                    'route' => "api.panels.{$panel}.resources.{$item['resource']}.index",
                    'resource' => $item['resource'],
                ];
            }

            if ($item['type'] === 'feature') {
                $featureConfig = config("studio.features.{$item['feature']}");

                return [
                    'type' => 'link',
                    'label' => $featureConfig['label'] ?? str($item['feature'])->title()->toString(),
                    'icon' => $featureConfig['icon'] ?? 'star',
                    'route' => "{$panel}.{$featureConfig['route']}",
                    'feature' => $item['feature'],
                ];
            }

            if ($item['type'] === 'group' && isset($item['items'])) {
                $item['items'] = $this->resolveMenuItems($item['items'], $panel);
            }

            return $item;
        })->toArray();
    }

    /**
     * Get the default panel for a user (based on priority and their roles).
     */
    public function getDefaultPanel($user = null): ?string
    {
        $user = $user ?? Auth::user();

        if (! $user) {
            return null;
        }

        $priority = config('studio.panel_priority', []);

        foreach ($priority as $panel) {
            if ($this->userCanAccessPanel($user, $panel)) {
                return $panel;
            }
        }

        // Fallback to first accessible panel
        $accessiblePanels = $this->getAccessiblePanels($user);

        return array_key_first($accessiblePanels);
    }

    /**
     * Check if user can access a panel based on their ROLE.
     */
    public function userCanAccessPanel($user, string $panel): bool
    {
        if (! $user) {
            return false;
        }

        $config = $this->getPanel($panel);
        if (! $config) {
            return false;
        }

        // Super admin can access ALL panels
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        // Get required role(s) from config
        $requiredRole = $config['role'] ?? null;
        $requiredRoles = $config['roles'] ?? ($requiredRole ? [$requiredRole] : [$panel]);

        // Check using hasRole method if available
        if (method_exists($user, 'hasRole')) {
            foreach ($requiredRoles as $role) {
                if ($user->hasRole($role)) {
                    return true;
                }
            }

            return false;
        }

        // Check if user has custom access method (e.g., canAccessAdminPanel)
        $method = 'canAccess'.str($panel)->studly().'Panel';
        if (method_exists($user, $method)) {
            return $user->{$method}();
        }

        // Fallback: check roles relationship if exists
        if (method_exists($user, 'roles') || property_exists($user, 'roles')) {
            $userRoles = $user->roles;

            // Handle collection of Role models
            if (is_object($userRoles) && method_exists($userRoles, 'pluck')) {
                $roleNames = $userRoles->pluck('slug')->merge($userRoles->pluck('name'))->toArray();
                foreach ($requiredRoles as $role) {
                    if (in_array($role, $roleNames)) {
                        return true;
                    }
                }
            }

            // Handle array of role names
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
     * Get all roles that can access any panel.
     */
    public function getAllPanelRoles(): array
    {
        return collect($this->getAllPanels())
            ->flatMap(function ($config) {
                $role = $config['role'] ?? null;
                $roles = $config['roles'] ?? [];

                return $role ? array_merge([$role], $roles) : $roles;
            })
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Get features available in a panel.
     */
    public function getPanelFeatures(string $panel): array
    {
        $panelConfig = $this->getPanel($panel);
        if (! $panelConfig) {
            return [];
        }

        $featureKeys = $panelConfig['features'] ?? [];
        $allFeatures = config('studio.features', []);

        return collect($featureKeys)
            ->mapWithKeys(fn ($key) => [$key => $allFeatures[$key] ?? null])
            ->filter()
            ->toArray();
    }

    /**
     * Check if a feature is available in a panel.
     */
    public function panelHasFeature(string $panel, string $feature): bool
    {
        $panelConfig = $this->getPanel($panel);

        return in_array($feature, $panelConfig['features'] ?? []);
    }

    /**
     * Get panel settings.
     */
    public function getPanelSettings(string $panel): array
    {
        $panelConfig = $this->getPanel($panel);

        return $panelConfig['settings'] ?? [];
    }

    /**
     * Get all panel keys.
     */
    public function getPanelKeys(): array
    {
        return array_keys($this->getAllPanels());
    }

    /**
     * Check if a panel exists.
     */
    public function panelExists(string $panel): bool
    {
        return $this->getPanel($panel) !== null;
    }
}
