# Laravel Studio - GitHub Copilot Instructions

This project uses **Laravel Studio**, a free Laravel Nova alternative for building admin panels with Vue 3.

## Package Information

- **Namespace:** `SavyApps\LaravelStudio`
- **Package:** `savyapps-com/laravel-studio`
- **Stack:** Laravel 12, Vue 3, Tailwind CSS 4, Pinia

## Key Architecture Patterns

### Backend (PHP/Laravel)

1. **Resources** extend `SavyApps\LaravelStudio\Resources\Resource` and are placed in `app/Resources/`
2. **Business logic** must be placed in service classes (`app/Services/`), not in resources
3. **Always use named routes**, never hardcode URL paths
4. **Validation** is defined in the resource's `rules()` method

### Frontend (Vue 3)

1. **API calls** must go through service classes (`resources/js/services/`), never directly from components
2. **State management** uses Pinia stores (`resources/js/stores/`)
3. **Reusable logic** uses composables (`resources/js/composables/`)
4. **Components** should be reusable; check existing components before creating new ones

## Creating a Resource

```bash
php artisan studio:make-resource ProductResource
```

```php
<?php

namespace App\Resources;

use SavyApps\LaravelStudio\Resources\Resource;
use SavyApps\LaravelStudio\Resources\Fields\{ID, Text, Number, Boolean, BelongsTo, Section};
use SavyApps\LaravelStudio\Resources\Filters\{SelectFilter, BooleanFilter};
use SavyApps\LaravelStudio\Resources\Actions\{BulkDeleteAction, ExportAction};

class ProductResource extends Resource
{
    public static string $model = \App\Models\Product::class;
    public static string $title = 'name';
    public static array $search = ['name', 'sku'];

    public function indexFields(): array
    {
        return [
            ID::make()->sortable(),
            Text::make('Name')->sortable()->searchable(),
            Number::make('Price')->sortable(),
            Boolean::make('Active')->toggleable(),
        ];
    }

    public function formFields(): array
    {
        return [
            Section::make('Details')->fields([
                Text::make('Name')->rules('required', 'max:255'),
                Number::make('Price')->rules('required', 'min:0'),
                Boolean::make('Active')->default(true),
            ]),
        ];
    }

    public function filters(): array
    {
        return [
            BooleanFilter::make('Active'),
        ];
    }

    public function actions(): array
    {
        return [
            BulkDeleteAction::make(),
            ExportAction::make()->formats(['csv', 'xlsx']),
        ];
    }
}
```

Register in `config/studio.php`:
```php
'resources' => [
    'products' => \App\Resources\ProductResource::class,
],
```

## Available Field Types (21)

| Category | Fields |
|----------|--------|
| **Basic** | ID, Text, Email, Password, Number, Boolean, Date, Textarea |
| **Advanced** | Select, JSON, Image, Media, IconPicker, TagInput, MultiSelectServer |
| **Relationships** | BelongsTo, BelongsToMany, HasMany |
| **Layout** | Group, Section |

## Common Field Methods

```php
Text::make('Name')
    ->sortable()                 // Enable sorting
    ->searchable()               // Include in search
    ->rules('required')          // Validation rules
    ->default('value')           // Default value
    ->placeholder('Enter name')  // Placeholder text
    ->help('Help text')          // Help text
    ->cols(6)                    // Grid span (1-12)
    ->showOnIndex()              // Show on list
    ->showOnCreate()             // Show on create form
    ->showOnUpdate()             // Show on edit form
    ->dependsOn('field')         // Conditional visibility
    ->showWhen('field', 'value') // Show when condition
    ->requiredWhen('field', 'value'); // Required when condition
```

## Available Filters

```php
SelectFilter::make('Status')->options(StatusEnum::class);
SelectFilter::make('Type')->options(['active' => 'Active', 'inactive' => 'Inactive']);
BooleanFilter::make('Active');
DateRangeFilter::make('Created')->column('created_at');
BelongsToManyFilter::make('Tags')->relationship('tags');
```

## Built-in Actions

```php
BulkDeleteAction::make()->confirmable();
BulkUpdateAction::make()->fields(['status', 'active']);
ExportAction::make()->formats(['csv', 'xlsx']);
```

## Creating Custom Actions

```bash
php artisan studio:make-action ActivateUsersAction
```

```php
class ActivateUsersAction extends Action
{
    public static string $label = 'Activate Selected';

    public function __construct()
    {
        $this->confirmable()->success();
    }

    public function handle(Collection $models): array
    {
        $models->each->update(['active' => true]);
        return ['message' => 'Activated', 'count' => $models->count()];
    }
}
```

## Available Traits

| Trait | Apply To | Purpose |
|-------|----------|---------|
| `Authorizable` | Resource | Permission control |
| `LogsActivity` | Model | Audit trail |
| `HasPermissions` | User Model | Permission checking |
| `GloballySearchable` | Resource | Global search |
| `ApiResponse` | Controller | Standard responses |

## Permission System

Permissions are defined as constants in `SavyApps\LaravelStudio\Enums\Permission`:

```php
use SavyApps\LaravelStudio\Enums\Permission;

// Use constants instead of strings (prevents typos)
Permission::USERS_LIST;      // 'users.list'
Permission::USERS_CREATE;    // 'users.create'
Permission::ROLES_MANAGE;    // 'roles.manage'

// Validation
Permission::isValid('users.create');  // true
```

Role model is in the package (not app/Models):

```php
use SavyApps\LaravelStudio\Models\Role;

Role::SUPER_ADMIN;  // 'super_admin'
Role::ADMIN;        // 'admin'
Role::USER;         // 'user'

$role->hasPermission('users.create');
$role->isSystemRole();  // Protected from deletion
```

RBAC can be disabled entirely with `STUDIO_AUTH_ENABLED=false`.

## Backend Services

- `ResourceService` - CRUD operations
- `PanelService` - Panel management
- `AuthorizationService` - Permissions
- `ActivityService` - Audit logging
- `GlobalSearchService` - Cross-resource search
- `CardService` - Dashboard cards

## Frontend Services (Vue)

```javascript
import { resourceService, searchService, permissionService } from '@/services'

// CRUD
await resourceService.getIndex('users', { page: 1, search: 'john' })
await resourceService.create('users', formData)
await resourceService.update('users', id, formData)
await resourceService.delete('users', id)

// Search
await searchService.globalSearch('query')

// Permissions
await permissionService.checkPermission('users.create')
```

## Vue Composables

```javascript
import { useToast, useDialog, usePermissions } from '@/composables'

const { success, error } = useToast()
const { confirm } = useDialog()
const { can, canAny } = usePermissions()
```

## Artisan Commands

| Command | Description |
|---------|-------------|
| `studio:install` | Interactive installation |
| `studio:install --all` | Install everything without prompts |
| `studio:install --force` | Overwrite existing files |
| `studio:install --dry-run` | Preview installation |
| `studio:make-resource {name}` | Create resource |
| `studio:make-action {name}` | Create action |
| `studio:make-filter {name}` | Create filter |
| `studio:make-field {name}` | Create custom field |
| `studio:make-panel` | Create panel (interactive) |
| `studio:sync-permissions` | Sync permissions to DB |
| `studio:cleanup-activities` | Clean old activity logs |
| `studio:doctor` | Run diagnostics |

## Configuration

Main config file: `config/studio.php`

### Key Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `STUDIO_ROUTE_PREFIX` | `api/studio` | API route prefix |
| `STUDIO_CACHE_ENABLED` | `true` | Enable caching |
| `STUDIO_CACHE_TTL` | `3600` | Cache TTL (unified) |
| `STUDIO_AUTH_ENABLED` | `true` | Enable permissions |
| `STUDIO_SUPER_ADMIN_ROLE` | `super_admin` | Bypass role |
| `STUDIO_ACTIVITY_LOG_ENABLED` | `true` | Enable audit logging |
| `STUDIO_ACTIVITY_LOG_CLEANUP_DAYS` | `90` | Days to keep logs |
| `STUDIO_SEARCH_ENABLED` | `true` | Enable global search |
| `STUDIO_CARDS_ENABLED` | `true` | Enable dashboard cards |

### Middleware Aliases

- `panel` - Check panel access: `middleware('panel:admin')`
- `permission` - Check permission: `middleware('permission:users.create')`

## API Endpoints

```
GET    /api/studio/panels/{panel}/resources/{resource}
POST   /api/studio/panels/{panel}/resources/{resource}
GET    /api/studio/panels/{panel}/resources/{resource}/{id}
PUT    /api/studio/panels/{panel}/resources/{resource}/{id}
DELETE /api/studio/panels/{panel}/resources/{resource}/{id}
POST   /api/studio/panels/{panel}/resources/{resource}/bulk/delete
POST   /api/studio/panels/{panel}/resources/{resource}/bulk/update
GET    /api/search
```

## Anti-Patterns to Avoid

1. **Don't put business logic in resources** - Use service classes instead
2. **Don't call API directly from Vue components** - Use service layer
3. **Don't hardcode routes** - Use named routes
4. **Don't skip validation** - Always define rules
5. **Don't duplicate components** - Check existing ones first
