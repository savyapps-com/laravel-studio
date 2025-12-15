# Laravel Studio - AI Assistant Guide

> **A free, open-source Laravel Nova alternative** - craft beautiful admin panels with a fluent resource-based CRUD system, powered by Vue 3.

This file helps AI coding assistants (Claude, Cursor, Copilot) understand Laravel Studio for both **using** the package and **contributing** to it.

---

## Quick Reference

### Package Namespace
```php
SavyApps\LaravelStudio
```

### Key Directories
```
src/
├── Resources/           # Resource system (base classes)
│   ├── Fields/         # 21 field types
│   ├── Filters/        # 5 filter types
│   └── Actions/        # Action classes
├── Services/           # 6 backend services
├── Traits/             # 5 reusable traits
├── Console/Commands/   # 10 Artisan commands
├── Http/               # Controllers & middleware
└── Models/             # Activity, Permission models

starters/default/
├── backend/            # Laravel starter template
│   └── app/Resources/  # Example resources
└── frontend/           # Vue 3 starter template
    └── js/
        ├── components/ # 56 Vue components
        ├── services/   # API service layer
        ├── stores/     # 5 Pinia stores
        └── composables/# 11 composables
```

---

## For Users: Building with Laravel Studio

### Creating a Resource

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
            Text::make('SKU')->sortable(),
            Number::make('Price')->sortable(),
            Boolean::make('Active')->toggleable(),
            BelongsTo::make('Category')->searchable(),
        ];
    }

    public function formFields(): array
    {
        return [
            Section::make('Basic Information')->fields([
                Text::make('Name')->rules('required', 'max:255'),
                Text::make('SKU')->rules('required', 'unique:products,sku'),
                Number::make('Price')->rules('required', 'min:0')->step(0.01),
            ]),
            Section::make('Settings')->collapsible()->fields([
                Boolean::make('Active')->default(true),
                BelongsTo::make('Category')->rules('required'),
            ]),
        ];
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Category')->options(Category::pluck('name', 'id')),
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

### Register in config/studio.php

```php
'resources' => [
    'products' => \App\Resources\ProductResource::class,
],
```

---

## Field Types (21 Total)

| Field | Import | Common Methods |
|-------|--------|----------------|
| `ID` | `Fields\ID` | Hidden from forms automatically |
| `Text` | `Fields\Text` | `placeholder()`, `maxLength()`, `minLength()` |
| `Email` | `Fields\Email` | `placeholder()` |
| `Password` | `Fields\Password` | `requiredOnCreate()`, `requiredOnUpdate()` |
| `Number` | `Fields\Number` | `min()`, `max()`, `step()` |
| `Boolean` | `Fields\Boolean` | `trueLabel()`, `falseLabel()`, `toggleable()` |
| `Date` | `Fields\Date` | `format()`, `min()`, `max()` |
| `Textarea` | `Fields\Textarea` | `rows()`, `maxLength()` |
| `Select` | `Fields\Select` | `options()`, `multiple()`, `searchable()` |
| `JSON` | `Fields\Json` | `expectedType()`, `autoFormat()` |
| `Image` | `Fields\Image` | `displayType()`, `width()`, `height()` |
| `Media` | `Fields\Media` | `single()`, `multiple()`, `collection()` |
| `IconPicker` | `Fields\IconPicker` | `icons()`, `columns()` |
| `TagInput` | `Fields\TagInput` | `suggestions()`, `maxTags()`, `allowNew()` |
| `MultiSelectServer` | `Fields\MultiSelectServer` | `endpoint()`, `searchable()` |
| `BelongsTo` | `Fields\BelongsTo` | `resource()`, `titleAttribute()`, `searchable()` |
| `BelongsToMany` | `Fields\BelongsToMany` | `resource()`, `titleAttribute()` |
| `HasMany` | `Fields\HasMany` | `resource()` |
| `Group` | `Fields\Group` | `cols()`, `gap()` |
| `Section` | `Fields\Section` | `description()`, `icon()`, `collapsible()` |

### Common Field Methods (All Fields)

```php
Field::make('Name')
    ->sortable()              // Enable column sorting
    ->searchable()            // Include in search
    ->rules('required')       // Validation rules
    ->default('value')        // Default value
    ->nullable()              // Allow null
    ->placeholder('text')     // Placeholder
    ->help('Help text')       // Help text
    ->cols(6)                 // Grid span (1-12)
    ->showOnIndex()           // Show on list view
    ->showOnDetail()          // Show on detail view
    ->showOnCreate()          // Show on create form
    ->showOnUpdate()          // Show on edit form
    ->dependsOn('field')      // Conditional visibility
    ->showWhen('field', 'value')
    ->hideWhen('field', 'value')
    ->requiredWhen('field', 'value')
    ->disabledWhen('field', 'value');
```

---

## Filter Types (5 Total)

```php
use SavyApps\LaravelStudio\Resources\Filters\{
    SelectFilter,
    BooleanFilter,
    DateRangeFilter,
    BelongsToManyFilter
};

// Select with enum
SelectFilter::make('Status')->options(UserStatus::class);

// Select with array
SelectFilter::make('Type')->options(['draft' => 'Draft', 'published' => 'Published']);

// Boolean
BooleanFilter::make('Active')->trueLabel('Active')->falseLabel('Inactive');

// Date range
DateRangeFilter::make('Created')->column('created_at');

// Relationship
BelongsToManyFilter::make('Tags')->relationship('tags');
```

---

## Actions

### Built-in Actions

```php
use SavyApps\LaravelStudio\Resources\Actions\{
    BulkDeleteAction,
    BulkUpdateAction,
    ExportAction
};

BulkDeleteAction::make()->confirmable();
BulkUpdateAction::make()->fields(['status', 'active']);
ExportAction::make()->formats(['csv', 'xlsx', 'pdf']);
```

### Custom Action

```bash
php artisan studio:make-action ActivateUsersAction
```

```php
<?php

namespace App\Actions;

use SavyApps\LaravelStudio\Resources\Actions\Action;
use Illuminate\Support\Collection;

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

        return [
            'message' => 'Users activated successfully',
            'count' => $models->count(),
        ];
    }
}
```

---

## Backend Services

| Service | Purpose | Usage |
|---------|---------|-------|
| `ResourceService` | CRUD, search, queries | `app(ResourceService::class)` |
| `PanelService` | Panel management | `app(PanelService::class)` |
| `AuthorizationService` | Permissions | `app(AuthorizationService::class)` |
| `ActivityService` | Audit logging | `app(ActivityService::class)` |
| `GlobalSearchService` | Cross-resource search | `app(GlobalSearchService::class)` |
| `CardService` | Dashboard cards | `app(CardService::class)` |

---

## Traits

| Trait | Apply To | Purpose |
|-------|----------|---------|
| `Authorizable` | Resource | Permission control |
| `LogsActivity` | Model | Audit trail |
| `HasPermissions` | User Model | Permission checking |
| `GloballySearchable` | Resource | Global search |
| `ApiResponse` | Controller | Standard responses |

---

## Frontend Services (Vue)

```javascript
import {
    resourceService,
    panelService,
    authService,
    activityService,
    searchService,
    permissionService,
    cardService,
    mediaService
} from '@/services'

// CRUD operations
await resourceService.getIndex('users', { page: 1, search: 'john' })
await resourceService.create('users', formData)
await resourceService.update('users', id, formData)
await resourceService.delete('users', id)
await resourceService.bulkDelete('users', ids)

// Global search
await searchService.globalSearch('query')

// Permissions
const can = await permissionService.checkPermission('users.create')
```

---

## Composables (Vue)

```javascript
import {
    useToast,
    useDialog,
    usePermissions,
    useGlobalSearch,
    useLightbox
} from '@/composables'

// Toast
const { success, error } = useToast()
success('Saved!')

// Dialog
const { confirm } = useDialog()
if (await confirm('Delete?')) { /* proceed */ }

// Permissions
const { can, canAny } = usePermissions()
if (can('users.create')) { /* show button */ }
```

---

## Artisan Commands

| Command | Description |
|---------|-------------|
| `studio:install` | Interactive installation |
| `studio:install --all` | Install everything without prompts |
| `studio:install --force` | Overwrite existing files |
| `studio:install --dry-run` | Preview what will be installed |
| `studio:make-resource {name}` | Create resource |
| `studio:make-action {name}` | Create action |
| `studio:make-filter {name}` | Create filter |
| `studio:make-field {name}` | Create field type |
| `studio:make-panel` | Create panel (interactive) |
| `studio:sync-permissions` | Sync permissions to DB |
| `studio:cleanup-activities` | Clean old logs |
| `studio:doctor` | Run diagnostics |

---

## API Endpoints (Auto-generated)

```
GET    /api/studio/panels/{panel}/resources/{resource}          # List
POST   /api/studio/panels/{panel}/resources/{resource}          # Create
GET    /api/studio/panels/{panel}/resources/{resource}/{id}     # Show
PUT    /api/studio/panels/{panel}/resources/{resource}/{id}     # Update
DELETE /api/studio/panels/{panel}/resources/{resource}/{id}     # Delete
POST   /api/studio/panels/{panel}/resources/{resource}/bulk/delete
POST   /api/studio/panels/{panel}/resources/{resource}/bulk/update
GET    /api/search                                               # Global search
```

---

## For Contributors: Package Development

### Adding a New Field Type

1. Create field class in `src/Resources/Fields/`:
```php
<?php

namespace SavyApps\LaravelStudio\Resources\Fields;

class CustomField extends Field
{
    public string $component = 'custom-field';

    public function customMethod($value): static
    {
        return $this->withMeta(['custom' => $value]);
    }
}
```

2. Create Vue component in `starters/default/frontend/js/components/form/`:
```vue
<template>
    <FormGroup :field="field" :errors="errors">
        <!-- Your input implementation -->
    </FormGroup>
</template>

<script setup>
defineProps(['field', 'modelValue', 'errors'])
defineEmits(['update:modelValue'])
</script>
```

3. Register in field renderer component.

### Running Tests

```bash
cd packages/laravel-studio
./vendor/bin/phpunit
```

### Code Style

```bash
./vendor/bin/pint
```

---

## Patterns to Follow

1. **Resources extend `Resource`** - Always use the base class
2. **Business logic in Services** - Keep resources thin
3. **Use named routes** - Never hardcode paths
4. **Validate in `rules()`** - Use Laravel validation
5. **Use field visibility methods** - `showOnIndex()`, `showOnCreate()`, etc.
6. **API calls through services** (frontend) - Never call API directly from components

## Anti-Patterns to Avoid

1. **Don't put business logic in resources** - Use service classes
2. **Don't skip validation rules** - Always validate user input
3. **Don't hardcode permissions** - Use the `Authorizable` trait
4. **Don't access API directly** - Use service layer
5. **Don't duplicate Vue components** - Check existing components first

---

## Configuration

Main config file: `config/studio.php`

### Key Settings

| Setting | Env Variable | Default | Description |
|---------|--------------|---------|-------------|
| `prefix` | `STUDIO_ROUTE_PREFIX` | `api/studio` | API route prefix |
| `cache.enabled` | `STUDIO_CACHE_ENABLED` | `true` | Enable caching |
| `cache.ttl` | `STUDIO_CACHE_TTL` | `3600` | Cache TTL (seconds) |
| `authorization.enabled` | `STUDIO_AUTH_ENABLED` | `true` | Enable permissions |
| `authorization.super_admin_role` | `STUDIO_SUPER_ADMIN_ROLE` | `super_admin` | Role that bypasses checks |
| `activity_log.enabled` | `STUDIO_ACTIVITY_LOG_ENABLED` | `true` | Enable audit logging |
| `activity_log.cleanup_days` | `STUDIO_ACTIVITY_LOG_CLEANUP_DAYS` | `90` | Days to keep logs |
| `global_search.enabled` | `STUDIO_SEARCH_ENABLED` | `true` | Enable global search |
| `cards.enabled` | `STUDIO_CARDS_ENABLED` | `true` | Enable dashboard cards |

### Simplified Config Structure

```php
'cache' => [
    'enabled' => env('STUDIO_CACHE_ENABLED', true),
    'ttl' => env('STUDIO_CACHE_TTL', 3600),  // Unified TTL for all caches
    'prefix' => 'studio_',
],

'authorization' => [
    'enabled' => env('STUDIO_AUTH_ENABLED', true),
    'super_admin_role' => env('STUDIO_SUPER_ADMIN_ROLE', 'super_admin'),
    'models' => [
        'user' => \App\Models\User::class,
        'role' => \App\Models\Role::class,
        'permission' => \SavyApps\LaravelStudio\Models\Permission::class,
    ],
],

'activity_log' => [
    'enabled' => env('STUDIO_ACTIVITY_LOG_ENABLED', true),
    'cleanup_days' => env('STUDIO_ACTIVITY_LOG_CLEANUP_DAYS', 90),
],
```

### Middleware Aliases

| Alias | Class | Usage |
|-------|-------|-------|
| `panel` | `EnsureUserCanAccessPanel` | `middleware('panel:admin')` |
| `permission` | `CheckResourcePermission` | `middleware('permission:users.create')` |

---

## Requirements

- PHP 8.2+
- Laravel 12.x
- Vue 3.5+
- Node.js 18+
- Tailwind CSS 4.0
