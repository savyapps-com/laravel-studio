# Multi-Panel System Architecture for Laravel Studio

## Current State Analysis

Your current system has:
- **2 hardcoded panels**: `admin` and `user`
- Resources are **global** - all registered resources appear in admin panel
- Menu items are **hardcoded** per panel
- Middleware is **panel-specific** (`EnsureUserCanAccessAdminPanel`, `EnsureUserCanAccessUserPanel`)

## Proposed Multi-Panel Architecture

A flexible system where you can define unlimited panels with configurable resources.

---

## Authentication Approach: Single Guard + Roles

This system uses **Option A: Single Auth Guard with Role-Based Access Control**.

### Key Concepts

| Term | Meaning |
|------|---------|
| **Auth Guard** | Laravel authentication mechanism (`sanctum`) - HOW users authenticate |
| **Role** | User's role in the system (`admin`, `vendor`, `user`) - WHO can access what |

### How It Works

```
┌─────────────────────────────────────────────────────────────────┐
│                        Single User Model                         │
│                     (App\Models\User)                           │
├─────────────────────────────────────────────────────────────────┤
│  user_id: 1  │  roles: ['super_admin']  │  → Super Admin Panel  │
│  user_id: 2  │  roles: ['admin']        │  → Admin Panel        │
│  user_id: 3  │  roles: ['vendor']       │  → Vendor Panel       │
│  user_id: 4  │  roles: ['user']         │  → User Panel         │
│  user_id: 5  │  roles: ['admin','user'] │  → Admin + User Panel │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
                    ┌─────────────────┐
                    │  Auth: Sanctum  │  ← Single auth guard
                    │  (token-based)  │
                    └─────────────────┘
                              │
                              ▼
                    ┌─────────────────┐
                    │ Panel Middleware │  ← Checks user ROLE
                    │  panel:{name}   │
                    └─────────────────┘
```

### Why This Approach?

1. **Single User Model** - No need for separate `Admin`, `Vendor`, `User` models
2. **Flexible Roles** - Users can have multiple roles (access multiple panels)
3. **Simple Auth** - One authentication system (Sanctum) for all panels
4. **Easy to Extend** - Add new panels by creating roles, not new user tables

### No Changes to `config/auth.php`

Your existing auth configuration works as-is:

```php
// config/auth.php - NO CHANGES NEEDED
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    // Sanctum adds its own guard automatically
],

'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\User::class,  // Single user model
    ],
],
```

---

## 1. Panel Configuration (`config/studio.php`)

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Panels Configuration
    |--------------------------------------------------------------------------
    |
    | Define multiple admin panels with their own resources, middleware,
    | and access control. Each panel requires a specific ROLE (not auth guard).
    |
    | - 'role': The role name required to access this panel (from roles table)
    | - 'roles': Array of roles that can access this panel (alternative)
    | - All panels use the same auth guard (sanctum)
    |
    */
    'panels' => [
        'super-admin' => [
            'label' => 'Super Admin',
            'path' => '/super-admin',
            'icon' => 'shield-check',
            'middleware' => ['auth:sanctum', 'panel:super-admin'],
            'role' => 'super_admin',  // Role required to access this panel
            'resources' => ['users', 'roles', 'countries', 'timezones', 'settings', 'audit-logs'],
            'features' => ['email-templates', 'system-settings', 'activity-logs'],
            'menu' => [
                ['type' => 'link', 'label' => 'Dashboard', 'route' => 'super-admin.dashboard', 'icon' => 'home'],
                ['type' => 'group', 'label' => 'User Management', 'items' => [
                    ['type' => 'resource', 'resource' => 'users'],
                    ['type' => 'resource', 'resource' => 'roles'],
                ]],
                ['type' => 'group', 'label' => 'System', 'items' => [
                    ['type' => 'resource', 'resource' => 'countries'],
                    ['type' => 'resource', 'resource' => 'timezones'],
                    ['type' => 'feature', 'feature' => 'email-templates'],
                    ['type' => 'feature', 'feature' => 'system-settings'],
                ]],
            ],
            'settings' => [
                'layout' => 'classic',
                'theme' => 'dark',
            ],
        ],

        'admin' => [
            'label' => 'Admin Panel',
            'path' => '/admin',
            'icon' => 'cog',
            'middleware' => ['auth:sanctum', 'panel:admin'],
            'role' => 'admin',  // Role required to access this panel
            'resources' => ['users', 'roles', 'countries', 'timezones'],
            'features' => ['email-templates'],
            'menu' => [
                ['type' => 'link', 'label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'home'],
                ['type' => 'resource', 'resource' => 'users'],
                ['type' => 'resource', 'resource' => 'roles'],
                ['type' => 'resource', 'resource' => 'countries'],
                ['type' => 'resource', 'resource' => 'timezones'],
                ['type' => 'feature', 'feature' => 'email-templates'],
            ],
        ],

        'vendor' => [
            'label' => 'Vendor Portal',
            'path' => '/vendor',
            'icon' => 'store',
            'middleware' => ['auth:sanctum', 'panel:vendor'],
            'role' => 'vendor',  // Role required to access this panel
            'resources' => ['products', 'orders', 'customers'],
            'features' => ['analytics'],
            'menu' => [
                ['type' => 'link', 'label' => 'Dashboard', 'route' => 'vendor.dashboard', 'icon' => 'home'],
                ['type' => 'resource', 'resource' => 'products'],
                ['type' => 'resource', 'resource' => 'orders'],
                ['type' => 'resource', 'resource' => 'customers'],
            ],
        ],

        'user' => [
            'label' => 'User Dashboard',
            'path' => '/user',
            'icon' => 'user',
            'middleware' => ['auth:sanctum', 'panel:user'],
            'role' => 'user',  // Role required to access this panel
            'resources' => [],  // No resource management for users
            'features' => [],
            'menu' => [
                ['type' => 'link', 'label' => 'Dashboard', 'route' => 'user.dashboard', 'icon' => 'home'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resources Configuration
    |--------------------------------------------------------------------------
    */
    'resources' => [
        'users' => [
            'class' => \App\Resources\UserResource::class,
            'label' => 'Users',
            'icon' => 'users',
        ],
        'roles' => [
            'class' => \App\Resources\RoleResource::class,
            'label' => 'Roles',
            'icon' => 'shield',
        ],
        'countries' => [
            'class' => \App\Resources\CountryResource::class,
            'label' => 'Countries',
            'icon' => 'globe',
        ],
        'timezones' => [
            'class' => \App\Resources\TimezoneResource::class,
            'label' => 'Timezones',
            'icon' => 'clock',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Features Configuration
    |--------------------------------------------------------------------------
    */
    'features' => [
        'email-templates' => [
            'label' => 'Email Templates',
            'icon' => 'mail',
            'route' => 'email-templates.index',
        ],
        'system-settings' => [
            'label' => 'System Settings',
            'icon' => 'settings',
            'route' => 'settings.system',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Panel Priority (for redirection)
    |--------------------------------------------------------------------------
    | When a user has access to multiple panels, redirect to the first one
    | they have access to based on this priority order.
    */
    'panel_priority' => ['super-admin', 'admin', 'vendor', 'user'],
];
```

---

## 2. Dynamic Panel Middleware

```php
<?php
// packages/laravel-studio/src/Http/Middleware/EnsureUserCanAccessPanel.php

namespace SavyApps\LaravelStudio\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanAccessPanel
{
    /**
     * Handle an incoming request.
     *
     * @param  string  $panel  The panel key (e.g., 'admin', 'vendor', 'user')
     */
    public function handle(Request $request, Closure $next, string $panel): Response
    {
        $user = Auth::user();

        if (!$user) {
            return $this->unauthorized($request, $panel);
        }

        $panelConfig = config("studio.panels.{$panel}");

        if (!$panelConfig) {
            abort(404, "Panel not found: {$panel}");
        }

        // Check if user can access this panel based on their ROLE
        if (!$this->userCanAccessPanel($user, $panel, $panelConfig)) {
            return $this->forbidden($request, $panel);
        }

        // Share panel context with the request
        $request->merge(['_panel' => $panel, '_panel_config' => $panelConfig]);

        return $next($request);
    }

    /**
     * Check if user has the required ROLE to access the panel.
     */
    protected function userCanAccessPanel($user, string $panel, array $config): bool
    {
        // Get required role(s) from config
        $requiredRole = $config['role'] ?? null;
        $requiredRoles = $config['roles'] ?? ($requiredRole ? [$requiredRole] : [$panel]);

        // Check if user has any of the required roles
        if (method_exists($user, 'hasRole')) {
            foreach ($requiredRoles as $role) {
                if ($user->hasRole($role)) {
                    return true;
                }
            }
            return false;
        }

        // Check if user has custom access method (e.g., canAccessVendorPanel)
        $method = 'canAccess' . str($panel)->studly() . 'Panel';
        if (method_exists($user, $method)) {
            return $user->{$method}();
        }

        // Default: check if user's roles contain any required role
        foreach ($requiredRoles as $role) {
            if ($user->roles->contains('name', $role)) {
                return true;
            }
        }

        return false;
    }

    protected function unauthorized(Request $request, string $panel): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        return redirect()->route('login');
    }

    protected function forbidden(Request $request, string $panel): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Access denied to this panel'], 403);
        }

        // Redirect to the panel they CAN access
        $redirectPanel = $this->findAccessiblePanel($request->user());
        if ($redirectPanel) {
            return redirect($redirectPanel['path']);
        }

        return redirect('/');
    }

    protected function findAccessiblePanel($user): ?array
    {
        $priority = config('studio.panel_priority', []);

        foreach ($priority as $panelKey) {
            $config = config("studio.panels.{$panelKey}");
            if ($config && $this->userCanAccessPanel($user, $panelKey, $config)) {
                return array_merge($config, ['key' => $panelKey]);
            }
        }

        return null;
    }
}
```

---

## 3. Panel Service

```php
<?php
// packages/laravel-studio/src/Services/PanelService.php

namespace SavyApps\LaravelStudio\Services;

use Illuminate\Support\Facades\Auth;

class PanelService
{
    /**
     * Get all configured panels
     */
    public function getAllPanels(): array
    {
        return config('studio.panels', []);
    }

    /**
     * Get a specific panel configuration
     */
    public function getPanel(string $key): ?array
    {
        return config("studio.panels.{$key}");
    }

    /**
     * Get panels accessible by the current user (based on their roles)
     */
    public function getAccessiblePanels($user = null): array
    {
        $user = $user ?? Auth::user();

        if (!$user) {
            return [];
        }

        return collect($this->getAllPanels())
            ->filter(fn($config, $key) => $this->userCanAccessPanel($user, $key))
            ->toArray();
    }

    /**
     * Get resources available in a panel
     */
    public function getPanelResources(string $panel): array
    {
        $panelConfig = $this->getPanel($panel);
        if (!$panelConfig) {
            return [];
        }

        $resourceKeys = $panelConfig['resources'] ?? [];
        $allResources = config('studio.resources', []);

        return collect($resourceKeys)
            ->mapWithKeys(fn($key) => [$key => $allResources[$key] ?? null])
            ->filter()
            ->toArray();
    }

    /**
     * Check if a resource is available in a panel
     */
    public function panelHasResource(string $panel, string $resource): bool
    {
        $panelConfig = $this->getPanel($panel);
        return in_array($resource, $panelConfig['resources'] ?? []);
    }

    /**
     * Get menu items for a panel
     */
    public function getPanelMenu(string $panel): array
    {
        $panelConfig = $this->getPanel($panel);
        return $this->resolveMenuItems($panelConfig['menu'] ?? [], $panel);
    }

    /**
     * Resolve menu items with resource/feature details
     */
    protected function resolveMenuItems(array $items, string $panel): array
    {
        return collect($items)->map(function ($item) use ($panel) {
            if ($item['type'] === 'resource') {
                $resourceConfig = config("studio.resources.{$item['resource']}");
                return [
                    'type' => 'link',
                    'label' => $resourceConfig['label'] ?? str($item['resource'])->title(),
                    'icon' => $resourceConfig['icon'] ?? 'folder',
                    'route' => "{$panel}.resources.{$item['resource']}.index",
                ];
            }

            if ($item['type'] === 'feature') {
                $featureConfig = config("studio.features.{$item['feature']}");
                return [
                    'type' => 'link',
                    'label' => $featureConfig['label'] ?? str($item['feature'])->title(),
                    'icon' => $featureConfig['icon'] ?? 'star',
                    'route' => "{$panel}.{$featureConfig['route']}",
                ];
            }

            if ($item['type'] === 'group' && isset($item['items'])) {
                $item['items'] = $this->resolveMenuItems($item['items'], $panel);
            }

            return $item;
        })->toArray();
    }

    /**
     * Get the default panel for a user (based on priority and their roles)
     */
    public function getDefaultPanel($user = null): ?string
    {
        $user = $user ?? Auth::user();
        $priority = config('studio.panel_priority', []);

        foreach ($priority as $panel) {
            if ($this->userCanAccessPanel($user, $panel)) {
                return $panel;
            }
        }

        return null;
    }

    /**
     * Check if user can access a panel based on their ROLE
     */
    public function userCanAccessPanel($user, string $panel): bool
    {
        $config = $this->getPanel($panel);
        if (!$config) {
            return false;
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

        // Fallback: check roles relationship
        foreach ($requiredRoles as $role) {
            if ($user->roles->contains('name', $role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all roles that can access any panel
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
}
```

---

## 4. Panel API Controller

```php
<?php
// packages/laravel-studio/src/Http/Controllers/PanelController.php

namespace SavyApps\LaravelStudio\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use SavyApps\LaravelStudio\Services\PanelService;

class PanelController extends Controller
{
    public function __construct(
        protected PanelService $panelService
    ) {}

    /**
     * Get all panels the current user can access (based on their roles)
     */
    public function index(Request $request): JsonResponse
    {
        $panels = $this->panelService->getAccessiblePanels();

        return response()->json([
            'panels' => collect($panels)->map(fn($config, $key) => [
                'key' => $key,
                'label' => $config['label'],
                'path' => $config['path'],
                'icon' => $config['icon'],
            ])->values(),
            'default' => $this->panelService->getDefaultPanel(),
        ]);
    }

    /**
     * Get configuration for a specific panel
     */
    public function show(Request $request, string $panel): JsonResponse
    {
        $config = $this->panelService->getPanel($panel);

        if (!$config) {
            return response()->json(['message' => 'Panel not found'], 404);
        }

        if (!$this->panelService->userCanAccessPanel($request->user(), $panel)) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        return response()->json([
            'panel' => $panel,
            'label' => $config['label'],
            'path' => $config['path'],
            'icon' => $config['icon'],
            'menu' => $this->panelService->getPanelMenu($panel),
            'resources' => array_keys($this->panelService->getPanelResources($panel)),
            'features' => $config['features'] ?? [],
            'settings' => $config['settings'] ?? [],
        ]);
    }
}
```

---

## 5. Updated Resource Controller

```php
<?php
// packages/laravel-studio/src/Http/Controllers/ResourceController.php

// Add panel-aware resource access
public function index(Request $request, string $resource)
{
    $panel = $request->get('_panel');

    // Verify resource is available in this panel
    if ($panel && !app(PanelService::class)->panelHasResource($panel, $resource)) {
        abort(403, "Resource '{$resource}' is not available in panel '{$panel}'");
    }

    // ... rest of the method
}
```

---

## 6. Dynamic Route Registration

```php
<?php
// packages/laravel-studio/src/LaravelStudioServiceProvider.php

protected function registerPanelRoutes(): void
{
    $panels = config('studio.panels', []);

    // Register panel info routes (accessible to authenticated users)
    Route::prefix('api/panels')
        ->middleware(['api', 'auth:sanctum'])
        ->name('api.panels.')
        ->group(function () {
            Route::get('/', [PanelController::class, 'index'])->name('index');
            Route::get('{panel}', [PanelController::class, 'show'])->name('show');
        });

    // Register panel-specific resource routes
    foreach ($panels as $key => $config) {
        Route::prefix("api/panels/{$key}")
            ->middleware($config['middleware'] ?? ['api', 'auth:sanctum', "panel:{$key}"])
            ->name("api.panels.{$key}.")
            ->group(function () use ($key, $config) {
                // Resource routes
                foreach ($config['resources'] ?? [] as $resource) {
                    $this->registerResourceRoutes($resource, $key);
                }
            });
    }
}

protected function registerResourceRoutes(string $resource, string $panel): void
{
    Route::prefix("resources/{$resource}")
        ->name("resources.{$resource}.")
        ->group(function () {
            Route::get('meta', [ResourceController::class, 'meta'])->name('meta');
            Route::get('/', [ResourceController::class, 'index'])->name('index');
            Route::post('/', [ResourceController::class, 'store'])->name('store');
            Route::get('{id}', [ResourceController::class, 'show'])->name('show');
            Route::put('{id}', [ResourceController::class, 'update'])->name('update');
            Route::patch('{id}', [ResourceController::class, 'patch'])->name('patch');
            Route::delete('{id}', [ResourceController::class, 'destroy'])->name('destroy');
            Route::post('bulk/delete', [ResourceController::class, 'bulkDelete'])->name('bulk.delete');
            Route::post('bulk/update', [ResourceController::class, 'bulkUpdate'])->name('bulk.update');
            Route::post('actions/{action}', [ResourceController::class, 'runAction'])->name('actions');
        });
}
```

---

## 7. Middleware Registration

```php
<?php
// bootstrap/app.php

use SavyApps\LaravelStudio\Http\Middleware\EnsureUserCanAccessPanel;

return Application::configure(basePath: dirname(__DIR__))
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'panel' => EnsureUserCanAccessPanel::class,
            // Keep legacy aliases for backward compatibility
            'admin' => EnsureUserCanAccessPanel::class,
            'user' => EnsureUserCanAccessPanel::class,
        ]);
    })
    ->create();
```

---

## 8. Frontend Panel Store

```javascript
// resources/js/core/stores/panel.js
import { defineStore } from 'pinia'
import { panelService } from '@/core/services/panelService'

export const usePanelStore = defineStore('panel', {
  state: () => ({
    currentPanel: null,
    panelConfig: null,
    accessiblePanels: [],
    loading: false,
  }),

  getters: {
    menuItems: (state) => state.panelConfig?.menu ?? [],
    resources: (state) => state.panelConfig?.resources ?? [],
    features: (state) => state.panelConfig?.features ?? [],
    panelLabel: (state) => state.panelConfig?.label ?? '',
    panelPath: (state) => state.panelConfig?.path ?? '',

    // Check if user has access to multiple panels
    hasMultiplePanels: (state) => state.accessiblePanels.length > 1,

    // Get other panels user can switch to
    otherPanels: (state) =>
      state.accessiblePanels.filter(p => p.key !== state.currentPanel),
  },

  actions: {
    async loadAccessiblePanels() {
      this.loading = true
      try {
        const response = await panelService.getAccessiblePanels()
        this.accessiblePanels = response.panels
        return response
      } finally {
        this.loading = false
      }
    },

    async loadPanelConfig(panel) {
      this.loading = true
      try {
        const config = await panelService.getPanelConfig(panel)
        this.currentPanel = panel
        this.panelConfig = config
        return config
      } finally {
        this.loading = false
      }
    },

    hasResource(resource) {
      return this.resources.includes(resource)
    },

    hasFeature(feature) {
      return this.features.includes(feature)
    },

    // Switch to another panel
    async switchPanel(panelKey) {
      const panel = this.accessiblePanels.find(p => p.key === panelKey)
      if (panel) {
        await this.loadPanelConfig(panelKey)
        return panel.path
      }
      return null
    },
  },
})
```

---

## 9. Frontend Panel Service

```javascript
// resources/js/core/services/panelService.js
import api from '@/services/api'

export const panelService = {
  /**
   * Get all panels accessible by the current user
   */
  async getAccessiblePanels() {
    const response = await api.get('/api/panels')
    return response.data
  },

  /**
   * Get configuration for a specific panel
   */
  async getPanelConfig(panel) {
    const response = await api.get(`/api/panels/${panel}`)
    return response.data
  },

  /**
   * Get resource API base URL for a panel
   */
  getResourceUrl(panel, resource) {
    return `/api/panels/${panel}/resources/${resource}`
  },
}

export default panelService
```

---

## 10. Dynamic Layout Component

```vue
<!-- resources/js/core/layouts/PanelLayout.vue -->
<script setup>
import { computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { usePanelStore } from '@/core/stores/panel'
import ClassicLayout from '@/layouts/admin/ClassicLayout.vue'
import CompactLayout from '@/layouts/admin/CompactLayout.vue'
import MiniLayout from '@/layouts/admin/MiniLayout.vue'
import HorizontalLayout from '@/layouts/admin/HorizontalLayout.vue'

const route = useRoute()
const router = useRouter()
const panelStore = usePanelStore()

// Detect current panel from route
const currentPanel = computed(() => {
  const path = route.path
  const panels = panelStore.accessiblePanels
  return panels.find(p => path.startsWith(p.path))?.key
})

// Load panel config when panel changes
watch(currentPanel, async (panel) => {
  if (panel && panel !== panelStore.currentPanel) {
    await panelStore.loadPanelConfig(panel)
  }
}, { immediate: true })

// Dynamic layout based on panel settings
const layoutComponent = computed(() => {
  const layout = panelStore.panelConfig?.settings?.layout ?? 'classic'
  const layouts = {
    classic: ClassicLayout,
    compact: CompactLayout,
    mini: MiniLayout,
    horizontal: HorizontalLayout,
  }
  return layouts[layout] ?? ClassicLayout
})

// Handle panel switching
const handlePanelSwitch = async (panelKey) => {
  const path = await panelStore.switchPanel(panelKey)
  if (path) {
    router.push(path)
  }
}

onMounted(async () => {
  await panelStore.loadAccessiblePanels()
})
</script>

<template>
  <component
    :is="layoutComponent"
    :menu-items="panelStore.menuItems"
    :panel-label="panelStore.panelLabel"
    :other-panels="panelStore.otherPanels"
    :show-panel-switcher="panelStore.hasMultiplePanels"
    @switch-panel="handlePanelSwitch"
  >
    <router-view />
  </component>
</template>
```

---

## 11. Dynamic Router Generation

```javascript
// resources/js/core/router/panelRoutes.js
export function generatePanelRoutes(panelConfig) {
  const { key, path, resources, features } = panelConfig

  const routes = [
    {
      path: path,
      component: () => import('@/core/layouts/PanelLayout.vue'),
      meta: { auth: key, panel: key },
      children: [
        {
          path: '',
          name: `${key}.dashboard`,
          component: () => import(`@/pages/${key}/Dashboard.vue`).catch(() =>
            import('@/core/pages/DefaultDashboard.vue')
          ),
        },
        // Dynamic resource routes
        ...resources.flatMap(resource => [
          {
            path: resource,
            name: `${key}.resources.${resource}.index`,
            component: () => import('@/core/pages/ResourceIndex.vue'),
            props: { resource, panel: key },
          },
          {
            path: `${resource}/create`,
            name: `${key}.resources.${resource}.create`,
            component: () => import('@/core/pages/ResourceCreate.vue'),
            props: { resource, panel: key },
          },
          {
            path: `${resource}/:id`,
            name: `${key}.resources.${resource}.show`,
            component: () => import('@/core/pages/ResourceShow.vue'),
            props: { resource, panel: key },
          },
          {
            path: `${resource}/:id/edit`,
            name: `${key}.resources.${resource}.edit`,
            component: () => import('@/core/pages/ResourceEdit.vue'),
            props: { resource, panel: key },
          },
        ]),
        // Common routes available in all panels
        {
          path: 'profile',
          name: `${key}.profile`,
          component: () => import('@/pages/common/Profile.vue'),
          props: { panel: key },
        },
        {
          path: 'settings',
          name: `${key}.settings`,
          component: () => import('@/pages/common/Settings.vue'),
          props: { panel: key },
        },
      ],
    },
  ]

  return routes
}

/**
 * Load panels from API and generate routes dynamically
 */
export async function loadDynamicPanelRoutes(router) {
  try {
    const response = await fetch('/api/panels', {
      headers: {
        'Authorization': `Bearer ${getAuthToken()}`,
        'Accept': 'application/json',
      },
    })

    if (!response.ok) return []

    const { panels } = await response.json()

    const routes = panels.flatMap(panel => generatePanelRoutes(panel))

    routes.forEach(route => router.addRoute(route))

    return routes
  } catch (error) {
    console.error('Failed to load panel routes:', error)
    return []
  }
}
```

---

## 12. Panel Switcher Component

```vue
<!-- resources/js/core/components/layout/PanelSwitcher.vue -->
<script setup>
import { computed } from 'vue'

const props = defineProps({
  currentPanel: {
    type: Object,
    required: true,
  },
  otherPanels: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits(['switch'])

const hasOtherPanels = computed(() => props.otherPanels.length > 0)
</script>

<template>
  <div v-if="hasOtherPanels" class="panel-switcher">
    <div class="current-panel">
      <span class="panel-icon">{{ currentPanel.icon }}</span>
      <span class="panel-label">{{ currentPanel.label }}</span>
    </div>

    <div class="panel-dropdown">
      <span class="dropdown-label">Switch to:</span>
      <button
        v-for="panel in otherPanels"
        :key="panel.key"
        class="panel-option"
        @click="emit('switch', panel.key)"
      >
        <span class="panel-icon">{{ panel.icon }}</span>
        <span class="panel-label">{{ panel.label }}</span>
      </button>
    </div>
  </div>
</template>
```

---

## Summary

This multi-panel system provides:

| Feature | Description |
|---------|-------------|
| **Single Auth Guard** | All panels use `sanctum` - no separate auth systems |
| **Role-Based Access** | Panel access determined by user roles from `roles` table |
| **Unlimited Panels** | Define as many panels as needed in config |
| **Per-Panel Resources** | Each panel shows only its configured resources |
| **Per-Panel Menu** | Fully customizable menu per panel |
| **Multi-Role Users** | Users can have multiple roles and access multiple panels |
| **Panel Switching** | UI component to switch between accessible panels |
| **Dynamic Routes** | Auto-generated routes per panel |
| **Feature Flags** | Enable/disable features per panel |
| **Layout Settings** | Different layout per panel |

---

## Role Setup

To use this system, ensure your roles are set up in the database:

```php
// database/seeders/RolesSeeder.php
public function run(): void
{
    $roles = [
        ['name' => 'super_admin', 'display_name' => 'Super Administrator'],
        ['name' => 'admin', 'display_name' => 'Administrator'],
        ['name' => 'vendor', 'display_name' => 'Vendor'],
        ['name' => 'user', 'display_name' => 'User'],
    ];

    foreach ($roles as $role) {
        Role::updateOrCreate(['name' => $role['name']], $role);
    }
}
```

---

## Implementation Steps

1. **Backend First**
   - Create `PanelService`
   - Create `EnsureUserCanAccessPanel` middleware
   - Create `PanelController`
   - Update `config/studio.php` with panels configuration
   - Update `LaravelStudioServiceProvider` for dynamic route registration
   - Register middleware alias in `bootstrap/app.php`

2. **Frontend Second**
   - Create `panelService.js`
   - Create `usePanelStore`
   - Create `PanelLayout.vue`
   - Create `PanelSwitcher.vue`
   - Update router to use dynamic panel routes
   - Update navigation components to use panel store

3. **Migration**
   - Create roles in database via seeder
   - Assign roles to existing users
   - Migrate existing admin/user panels to new config format
   - Update existing middleware references
   - Test all panel access scenarios
