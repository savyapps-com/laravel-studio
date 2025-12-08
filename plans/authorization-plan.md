# Authorization Layer - Laravel Studio Package

## Overview

**Purpose:** Granular permission control at resource, field, and action levels using Laravel's native Gates/Policies.

**Approach:** Code/Config-based (not UI-managed)
- Permissions defined in Resource classes
- `php artisan studio:sync-permissions` generates from resources
- Version controlled, simpler to maintain

**Dependencies:** None (uses Laravel's built-in authorization)

---

## API Design

### Resource-Level Authorization

```php
// In Resource class
class UserResource extends Resource
{
    /**
     * Optional: Link to a Policy class
     * If not specified, uses closure methods below
     */
    public static function policy(): ?string
    {
        return UserPolicy::class;
    }

    /**
     * Can user see the resource list?
     */
    public static function canViewAny($user): bool
    {
        return $user->hasPermission('users.list');
    }

    /**
     * Can user view a specific record?
     */
    public static function canView($user, $model): bool
    {
        return $user->hasPermission('users.view');
    }

    /**
     * Can user create new records?
     */
    public static function canCreate($user): bool
    {
        return $user->hasPermission('users.create');
    }

    /**
     * Can user update a specific record?
     */
    public static function canUpdate($user, $model): bool
    {
        return $user->hasPermission('users.update');
    }

    /**
     * Can user delete a specific record?
     */
    public static function canDelete($user, $model): bool
    {
        return $user->hasPermission('users.delete');
    }

    /**
     * Define permissions this resource generates
     * Used by studio:sync-permissions command
     */
    public static function permissions(): array
    {
        return [
            'users.list' => 'View Users List',
            'users.view' => 'View User Details',
            'users.create' => 'Create Users',
            'users.update' => 'Update Users',
            'users.delete' => 'Delete Users',
            'users.bulk-delete' => 'Bulk Delete Users',
            'users.view-salary' => 'View User Salary',
            'users.edit-salary' => 'Edit User Salary',
        ];
    }
}
```

### Field-Level Authorization

```php
// In Resource fields() method
public function fields(): array
{
    return [
        Text::make('Name'),

        Text::make('Email'),

        // Field only visible to users with permission
        Text::make('Salary')
            ->canSee(fn($user) => $user->hasPermission('users.view-salary'))
            ->canEdit(fn($user) => $user->hasPermission('users.edit-salary')),

        // Field visible but read-only for certain users
        Text::make('SSN')
            ->canSee(fn($user) => $user->hasPermission('users.view-ssn'))
            ->readOnly(fn($user) => !$user->hasPermission('users.edit-ssn')),
    ];
}
```

### Action-Level Authorization

```php
// In Resource actions() method
public function actions(): array
{
    return [
        BulkDeleteAction::make()
            ->canRun(fn($user) => $user->hasPermission('users.bulk-delete')),

        BulkUpdateAction::make()
            ->canRun(fn($user) => $user->hasPermission('users.bulk-update')),

        ExportAction::make()
            ->canRun(fn($user) => $user->hasPermission('users.export')),
    ];
}
```

### Configuration

```php
// config/studio.php
'authorization' => [
    // Enable/disable authorization checks
    'enabled' => true,

    // Role that bypasses all permission checks
    'super_admin_role' => 'super_admin',

    // Cache permissions for performance
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // seconds
        'prefix' => 'studio_permissions_',
    ],

    // Auto-register gates for permissions
    'register_gates' => true,
],
```

---

## Database Schema

### Permissions Table

```php
// database/migrations/xxxx_create_permissions_table.php
Schema::create('permissions', function (Blueprint $table) {
    $table->id();
    $table->string('name')->unique();           // 'users.create'
    $table->string('display_name');             // 'Create Users'
    $table->string('group')->nullable();        // 'User Management'
    $table->text('description')->nullable();
    $table->timestamps();

    $table->index('group');
});
```

### Role-Permissions Pivot

```php
// database/migrations/xxxx_create_role_permissions_table.php
Schema::create('role_permissions', function (Blueprint $table) {
    $table->foreignId('role_id')->constrained()->cascadeOnDelete();
    $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
    $table->primary(['role_id', 'permission_id']);
});
```

---

## Backend Implementation

### File Structure

```
packages/laravel-studio/
├── src/
│   ├── Models/
│   │   └── Permission.php
│   ├── Services/
│   │   └── AuthorizationService.php
│   ├── Traits/
│   │   ├── HasPermissions.php              # For User model
│   │   └── Authorizable.php                # For Resource class
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── PermissionController.php
│   │   └── Middleware/
│   │       └── CheckResourcePermission.php
│   └── Console/Commands/
│       └── SyncPermissionsCommand.php
├── database/migrations/
│   ├── xxxx_create_permissions_table.php
│   └── xxxx_create_role_permissions_table.php
└── config/studio.php
```

### Permission Model

```php
// src/Models/Permission.php
namespace SavyApps\LaravelStudio\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = ['name', 'display_name', 'group', 'description'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            config('studio.models.role', \App\Models\Role::class),
            'role_permissions'
        );
    }

    /**
     * Scope by group
     */
    public function scopeInGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Get permissions grouped by group name
     */
    public static function allGrouped(): array
    {
        return static::all()
            ->groupBy('group')
            ->map(fn($permissions) => $permissions->pluck('display_name', 'name'))
            ->toArray();
    }
}
```

### HasPermissions Trait (for User Model)

```php
// src/Traits/HasPermissions.php
namespace SavyApps\LaravelStudio\Traits;

use Illuminate\Support\Facades\Cache;
use SavyApps\LaravelStudio\Models\Permission;

trait HasPermissions
{
    /**
     * Check if user has a specific permission
     */
    public function hasPermission(string $permission): bool
    {
        // Super admin bypasses all checks
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->getCachedPermissions()->contains($permission);
    }

    /**
     * Check if user has any of the given permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has all of the given permissions
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get all permissions for user (cached)
     */
    public function getCachedPermissions()
    {
        if (!config('studio.authorization.cache.enabled', true)) {
            return $this->getAllPermissions();
        }

        $cacheKey = config('studio.authorization.cache.prefix', 'studio_permissions_') . $this->id;
        $ttl = config('studio.authorization.cache.ttl', 3600);

        return Cache::remember($cacheKey, $ttl, function () {
            return $this->getAllPermissions();
        });
    }

    /**
     * Get all permissions from user's roles
     */
    public function getAllPermissions()
    {
        return $this->roles
            ->flatMap(fn($role) => $role->permissions)
            ->pluck('name')
            ->unique();
    }

    /**
     * Clear permission cache for this user
     */
    public function clearPermissionCache(): void
    {
        $cacheKey = config('studio.authorization.cache.prefix', 'studio_permissions_') . $this->id;
        Cache::forget($cacheKey);
    }

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin(): bool
    {
        $superAdminRole = config('studio.authorization.super_admin_role', 'super_admin');
        return $this->hasRole($superAdminRole);
    }
}
```

### Authorizable Trait (for Resource Class)

```php
// src/Traits/Authorizable.php
namespace SavyApps\LaravelStudio\Traits;

trait Authorizable
{
    /**
     * Check if authorization is enabled
     */
    protected static function authorizationEnabled(): bool
    {
        return config('studio.authorization.enabled', true);
    }

    /**
     * Authorize an action, throw exception if denied
     */
    protected static function authorize(string $ability, $model = null): void
    {
        if (!static::authorizationEnabled()) {
            return;
        }

        $user = auth()->user();

        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        $allowed = match ($ability) {
            'viewAny' => static::canViewAny($user),
            'view' => static::canView($user, $model),
            'create' => static::canCreate($user),
            'update' => static::canUpdate($user, $model),
            'delete' => static::canDelete($user, $model),
            default => false,
        };

        if (!$allowed) {
            abort(403, "You do not have permission to {$ability} this resource.");
        }
    }

    /**
     * Default authorization methods (can be overridden in Resource)
     */
    public static function canViewAny($user): bool
    {
        return true;
    }

    public static function canView($user, $model): bool
    {
        return true;
    }

    public static function canCreate($user): bool
    {
        return true;
    }

    public static function canUpdate($user, $model): bool
    {
        return true;
    }

    public static function canDelete($user, $model): bool
    {
        return true;
    }

    /**
     * Get permissions defined by this resource
     * Override in Resource class to define custom permissions
     */
    public static function permissions(): array
    {
        $resourceKey = static::uriKey();

        return [
            "{$resourceKey}.list" => 'View ' . static::label() . ' List',
            "{$resourceKey}.view" => 'View ' . static::singularLabel() . ' Details',
            "{$resourceKey}.create" => 'Create ' . static::singularLabel(),
            "{$resourceKey}.update" => 'Update ' . static::singularLabel(),
            "{$resourceKey}.delete" => 'Delete ' . static::singularLabel(),
        ];
    }
}
```

### Authorization Service

```php
// src/Services/AuthorizationService.php
namespace SavyApps\LaravelStudio\Services;

use SavyApps\LaravelStudio\Models\Permission;
use Illuminate\Support\Facades\Gate;

class AuthorizationService
{
    /**
     * Register gates for all permissions
     */
    public function registerGates(): void
    {
        if (!config('studio.authorization.register_gates', true)) {
            return;
        }

        Permission::all()->each(function ($permission) {
            Gate::define($permission->name, function ($user) use ($permission) {
                return $user->hasPermission($permission->name);
            });
        });
    }

    /**
     * Sync permissions from all registered resources
     */
    public function syncPermissions(): array
    {
        $resources = config('studio.resources', []);
        $synced = [];

        foreach ($resources as $key => $resourceClass) {
            if (!method_exists($resourceClass, 'permissions')) {
                continue;
            }

            $permissions = $resourceClass::permissions();
            $group = $resourceClass::label();

            foreach ($permissions as $name => $displayName) {
                Permission::updateOrCreate(
                    ['name' => $name],
                    [
                        'display_name' => $displayName,
                        'group' => $group,
                    ]
                );
                $synced[] = $name;
            }
        }

        return $synced;
    }

    /**
     * Get all permissions grouped for UI display
     */
    public function getGroupedPermissions(): array
    {
        return Permission::allGrouped();
    }

    /**
     * Get permissions for a specific role
     */
    public function getRolePermissions($role): array
    {
        return $role->permissions->pluck('name')->toArray();
    }

    /**
     * Sync permissions to a role
     */
    public function syncRolePermissions($role, array $permissions): void
    {
        $permissionIds = Permission::whereIn('name', $permissions)->pluck('id');
        $role->permissions()->sync($permissionIds);

        // Clear cache for all users with this role
        $role->users->each(fn($user) => $user->clearPermissionCache());
    }
}
```

### Sync Permissions Command

```php
// src/Console/Commands/SyncPermissionsCommand.php
namespace SavyApps\LaravelStudio\Console\Commands;

use Illuminate\Console\Command;
use SavyApps\LaravelStudio\Services\AuthorizationService;

class SyncPermissionsCommand extends Command
{
    protected $signature = 'studio:sync-permissions
                            {--fresh : Delete all permissions before syncing}';

    protected $description = 'Sync permissions from all registered resources';

    public function handle(AuthorizationService $service): int
    {
        if ($this->option('fresh')) {
            $this->warn('Deleting all existing permissions...');
            Permission::query()->delete();
        }

        $this->info('Syncing permissions from resources...');

        $synced = $service->syncPermissions();

        $this->info('Synced ' . count($synced) . ' permissions:');

        foreach ($synced as $permission) {
            $this->line("  - {$permission}");
        }

        return Command::SUCCESS;
    }
}
```

### Permission Middleware

```php
// src/Http/Middleware/CheckResourcePermission.php
namespace SavyApps\LaravelStudio\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckResourcePermission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = $request->user();

        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        if (!$user->hasPermission($permission)) {
            abort(403, 'You do not have permission to perform this action.');
        }

        return $next($request);
    }
}
```

---

## Frontend Implementation

### File Structure

```
packages/laravel-studio/resources/js/
├── components/
│   └── permissions/
│       ├── PermissionManager.vue           # Main permission management
│       ├── RolePermissionMatrix.vue        # Matrix UI for assigning
│       ├── PermissionGuard.vue             # Conditional render wrapper
│       └── PermissionCheckbox.vue          # Single permission toggle
├── composables/
│   └── usePermissions.js                   # Permission checking
└── services/
    └── permissionService.js                # API calls
```

### Permission Service

```javascript
// services/permissionService.js
import api from '@/services/api'

export const permissionService = {
  /**
   * Get all permissions grouped
   */
  async getAll() {
    const response = await api.get('/api/permissions')
    return response.data
  },

  /**
   * Get permissions for a specific role
   */
  async getRolePermissions(roleId) {
    const response = await api.get(`/api/roles/${roleId}/permissions`)
    return response.data
  },

  /**
   * Update permissions for a role
   */
  async updateRolePermissions(roleId, permissions) {
    const response = await api.put(`/api/roles/${roleId}/permissions`, {
      permissions,
    })
    return response.data
  },

  /**
   * Sync permissions from resources
   */
  async sync() {
    const response = await api.post('/api/permissions/sync')
    return response.data
  },
}

export default permissionService
```

### usePermissions Composable

```javascript
// composables/usePermissions.js
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

export function usePermissions() {
  const authStore = useAuthStore()

  /**
   * Check if current user has a permission
   */
  const can = (permission) => {
    const user = authStore.user
    if (!user) return false

    // Super admin check
    if (user.is_super_admin) return true

    return user.permissions?.includes(permission) ?? false
  }

  /**
   * Check if current user has any of the permissions
   */
  const canAny = (permissions) => {
    return permissions.some(p => can(p))
  }

  /**
   * Check if current user has all of the permissions
   */
  const canAll = (permissions) => {
    return permissions.every(p => can(p))
  }

  /**
   * Check if user can perform action on resource
   */
  const canResource = (resource, action) => {
    return can(`${resource}.${action}`)
  }

  return {
    can,
    canAny,
    canAll,
    canResource,
  }
}
```

### PermissionGuard Component

```vue
<!-- components/permissions/PermissionGuard.vue -->
<script setup>
import { computed } from 'vue'
import { usePermissions } from '@/composables/usePermissions'

const props = defineProps({
  permission: {
    type: [String, Array],
    required: true,
  },
  mode: {
    type: String,
    default: 'any', // 'any' or 'all'
    validator: (v) => ['any', 'all'].includes(v),
  },
  fallback: {
    type: String,
    default: null,
  },
})

const { can, canAny, canAll } = usePermissions()

const hasPermission = computed(() => {
  if (Array.isArray(props.permission)) {
    return props.mode === 'all'
      ? canAll(props.permission)
      : canAny(props.permission)
  }
  return can(props.permission)
})
</script>

<template>
  <slot v-if="hasPermission" />
  <slot v-else-if="fallback" name="fallback">
    <span>{{ fallback }}</span>
  </slot>
</template>
```

### RolePermissionMatrix Component

```vue
<!-- components/permissions/RolePermissionMatrix.vue -->
<script setup>
import { ref, onMounted, computed } from 'vue'
import { permissionService } from '@/services/permissionService'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  roleId: {
    type: [Number, String],
    required: true,
  },
})

const emit = defineEmits(['updated'])

const { showToast } = useToast()
const loading = ref(false)
const saving = ref(false)
const allPermissions = ref({})
const rolePermissions = ref([])

// Load data
onMounted(async () => {
  loading.value = true
  try {
    const [permissions, role] = await Promise.all([
      permissionService.getAll(),
      permissionService.getRolePermissions(props.roleId),
    ])
    allPermissions.value = permissions.grouped
    rolePermissions.value = role.permissions
  } finally {
    loading.value = false
  }
})

// Toggle permission
const togglePermission = (permission) => {
  const index = rolePermissions.value.indexOf(permission)
  if (index === -1) {
    rolePermissions.value.push(permission)
  } else {
    rolePermissions.value.splice(index, 1)
  }
}

// Check if permission is enabled
const hasPermission = (permission) => {
  return rolePermissions.value.includes(permission)
}

// Toggle all in group
const toggleGroup = (group, permissions) => {
  const permissionNames = Object.keys(permissions)
  const allEnabled = permissionNames.every(p => hasPermission(p))

  if (allEnabled) {
    rolePermissions.value = rolePermissions.value.filter(
      p => !permissionNames.includes(p)
    )
  } else {
    permissionNames.forEach(p => {
      if (!hasPermission(p)) {
        rolePermissions.value.push(p)
      }
    })
  }
}

// Save changes
const save = async () => {
  saving.value = true
  try {
    await permissionService.updateRolePermissions(props.roleId, rolePermissions.value)
    showToast('Permissions updated successfully', 'success')
    emit('updated')
  } catch (error) {
    showToast('Failed to update permissions', 'error')
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="permission-matrix">
    <div v-if="loading" class="loading">Loading permissions...</div>

    <div v-else>
      <div
        v-for="(permissions, group) in allPermissions"
        :key="group"
        class="permission-group"
      >
        <div class="group-header">
          <label class="group-toggle">
            <input
              type="checkbox"
              :checked="Object.keys(permissions).every(p => hasPermission(p))"
              @change="toggleGroup(group, permissions)"
            />
            <span class="group-name">{{ group }}</span>
          </label>
        </div>

        <div class="permissions-list">
          <label
            v-for="(label, name) in permissions"
            :key="name"
            class="permission-item"
          >
            <input
              type="checkbox"
              :checked="hasPermission(name)"
              @change="togglePermission(name)"
            />
            <span>{{ label }}</span>
          </label>
        </div>
      </div>

      <div class="actions">
        <button
          @click="save"
          :disabled="saving"
          class="btn-primary"
        >
          {{ saving ? 'Saving...' : 'Save Permissions' }}
        </button>
      </div>
    </div>
  </div>
</template>
```

---

## Integration with ResourceController

```php
// Update ResourceController to use authorization
class ResourceController extends Controller
{
    public function index(Request $request, string $resource)
    {
        $resourceClass = $this->resolveResourceClass($resource);

        // Authorization check
        $resourceClass::authorize('viewAny');

        // ... rest of method
    }

    public function show(Request $request, string $resource, $id)
    {
        $resourceClass = $this->resolveResourceClass($resource);
        $model = $resourceClass::model()::findOrFail($id);

        // Authorization check
        $resourceClass::authorize('view', $model);

        // ... rest of method
    }

    public function store(Request $request, string $resource)
    {
        $resourceClass = $this->resolveResourceClass($resource);

        // Authorization check
        $resourceClass::authorize('create');

        // ... rest of method
    }

    public function update(Request $request, string $resource, $id)
    {
        $resourceClass = $this->resolveResourceClass($resource);
        $model = $resourceClass::model()::findOrFail($id);

        // Authorization check
        $resourceClass::authorize('update', $model);

        // ... rest of method
    }

    public function destroy(Request $request, string $resource, $id)
    {
        $resourceClass = $this->resolveResourceClass($resource);
        $model = $resourceClass::model()::findOrFail($id);

        // Authorization check
        $resourceClass::authorize('delete', $model);

        // ... rest of method
    }
}
```

---

## Integration with Field Visibility

```php
// In ResourceService - filter fields based on permissions
public function getVisibleFields(Resource $resource, $user, string $context = 'index'): array
{
    $fields = match ($context) {
        'index' => $resource->indexFields(),
        'show' => $resource->showFields(),
        'form' => $resource->formFields(),
        default => $resource->fields(),
    };

    return collect($fields)
        ->filter(function ($field) use ($user) {
            // Check canSee callback if defined
            if ($field->canSeeCallback) {
                return call_user_func($field->canSeeCallback, $user);
            }
            return true;
        })
        ->map(function ($field) use ($user) {
            // Check canEdit callback for form context
            if ($field->canEditCallback) {
                $canEdit = call_user_func($field->canEditCallback, $user);
                if (!$canEdit) {
                    $field->readOnly(true);
                }
            }
            return $field;
        })
        ->values()
        ->toArray();
}
```

---

## Usage Examples

### Setting Up User Model

```php
// app/Models/User.php
use SavyApps\LaravelStudio\Traits\HasPermissions;

class User extends Authenticatable
{
    use HasPermissions;

    // ... rest of model
}
```

### Setting Up Role Model

```php
// app/Models/Role.php
use SavyApps\LaravelStudio\Models\Permission;

class Role extends Model
{
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }
}
```

### Creating a Resource with Permissions

```php
// app/Resources/UserResource.php
class UserResource extends Resource
{
    public static function permissions(): array
    {
        return [
            'users.list' => 'View Users List',
            'users.view' => 'View User Details',
            'users.create' => 'Create Users',
            'users.update' => 'Update Users',
            'users.delete' => 'Delete Users',
            'users.view-salary' => 'View Salary Information',
            'users.edit-salary' => 'Edit Salary Information',
            'users.impersonate' => 'Impersonate Users',
        ];
    }

    public static function canViewAny($user): bool
    {
        return $user->hasPermission('users.list');
    }

    public static function canCreate($user): bool
    {
        return $user->hasPermission('users.create');
    }

    // ... other authorization methods

    public function fields(): array
    {
        return [
            Text::make('Name'),
            Email::make('Email'),

            // Only visible to users with salary permission
            Number::make('Salary')
                ->canSee(fn($user) => $user->hasPermission('users.view-salary'))
                ->canEdit(fn($user) => $user->hasPermission('users.edit-salary')),
        ];
    }
}
```

### Frontend Usage

```vue
<template>
  <!-- Conditional rendering based on permission -->
  <PermissionGuard permission="users.create">
    <button @click="createUser">Create User</button>
  </PermissionGuard>

  <!-- Multiple permissions (any) -->
  <PermissionGuard :permission="['users.update', 'users.delete']">
    <ActionButtons />
  </PermissionGuard>

  <!-- Using composable directly -->
  <button v-if="can('users.delete')" @click="deleteUser">Delete</button>
</template>

<script setup>
import { usePermissions } from '@/composables/usePermissions'
import PermissionGuard from '@/components/permissions/PermissionGuard.vue'

const { can } = usePermissions()
</script>
```

---

## Artisan Commands

```bash
# Sync permissions from all resources
php artisan studio:sync-permissions

# Sync with fresh start (delete existing)
php artisan studio:sync-permissions --fresh
```

---

## Implementation Checklist

### Backend
- [ ] Create Permission model
- [ ] Create migrations (permissions, role_permissions)
- [ ] Create HasPermissions trait
- [ ] Create Authorizable trait
- [ ] Create AuthorizationService
- [ ] Create SyncPermissionsCommand
- [ ] Create CheckResourcePermission middleware
- [ ] Create PermissionController
- [ ] Update ResourceController with authorization
- [ ] Update ResourceService for field filtering
- [ ] Update config/studio.php
- [ ] Register in ServiceProvider

### Frontend
- [ ] Create permissionService.js
- [ ] Create usePermissions composable
- [ ] Create PermissionGuard component
- [ ] Create RolePermissionMatrix component
- [ ] Update auth store to include permissions
- [ ] Update ResourceManager to respect permissions

### Testing
- [ ] Unit tests for HasPermissions trait
- [ ] Unit tests for AuthorizationService
- [ ] Feature tests for permission-based access
- [ ] Test permission caching
