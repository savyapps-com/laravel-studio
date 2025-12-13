# Laravel Studio

[![Latest Version on Packagist](https://img.shields.io/packagist/v/savyapps-com/laravel-studio.svg?style=flat-square)](https://packagist.org/packages/savyapps-com/laravel-studio)
[![Total Downloads](https://img.shields.io/packagist/dt/savyapps-com/laravel-studio.svg?style=flat-square)](https://packagist.org/packages/savyapps-com/laravel-studio)
[![License](https://img.shields.io/packagist/l/savyapps-com/laravel-studio.svg?style=flat-square)](https://packagist.org/packages/savyapps-com/laravel-studio)

> **A free, open-source Laravel Nova alternative** - craft beautiful admin panels with a fluent resource-based CRUD system, powered by Vue 3.

## Features

- **21 Field Types** - Text, Email, Password, Number, Boolean, Date, Select, Textarea, JSON, Image, Media, ID, BelongsTo, BelongsToMany, HasMany, Group, Section, IconPicker, TagInput, MultiSelectServer, and more
- **5 Filter Types** - SelectFilter, BooleanFilter, DateRangeFilter, BelongsToManyFilter with enum support
- **4 Built-in Actions** - BulkDelete, BulkUpdate, Export, and Custom actions
- **56 Vue Components** - Complete UI components for forms, tables, cards, modals, and more (14,600+ lines)
- **Auto-generated REST API** - Full CRUD endpoints with search, filters, and bulk operations (30+ endpoints)
- **Permission System** - Role-based authorization with caching and Laravel Gates integration
- **Activity Logging** - Audit trail with change tracking, IP logging, and cleanup
- **Global Search** - Cross-resource search with keyboard shortcuts (Cmd/Ctrl + K)
- **Dashboard Cards** - Value, Trend, Chart, Partition, and Table cards with caching
- **Panel Management** - Multi-panel support with role-based access
- **Media Library** - Spatie Media Library integration with image editing
- **6 Backend Services** - ResourceService, PanelService, AuthorizationService, ActivityService, GlobalSearchService, CardService
- **5 Reusable Traits** - Authorizable, LogsActivity, HasPermissions, GloballySearchable, ApiResponse
- **10 Artisan Commands** - Install, resource/panel/action/filter/field generators, diagnostics, and more
- **Modern Stack** - Laravel 12, Vue 3, Tailwind CSS 4, Pinia

## Why Laravel Studio?

- **Free & Open Source** - Unlike Laravel Nova ($199-$799 per site)
- **Modern Stack** - Built with the latest Laravel 12, Vue 3, and Tailwind CSS 4
- **Production Ready** - Battle-tested architecture from real projects
- **Developer Friendly** - Clean, intuitive API that feels like Laravel
- **Full Stack** - Backend + frontend in one package
- **Extensible** - Easy to add custom fields, filters, actions, and cards

## Requirements

- PHP 8.2+
- Laravel 12.x
- Vue 3.5+
- Node.js 18+

## Installation

**Quick Install (Recommended):**

```bash
# 1. Install the package
composer require savyapps-com/laravel-studio --dev

# 2. IMPORTANT: Regenerate autoload files
composer dump-autoload

# 3. Run the installer (includes all starter files)
php artisan studio:install --all
```

> **Important:** Always run `composer dump-autoload` after installing to avoid "Trait not found" errors.

**See [INSTALLATION.md](INSTALLATION.md) for:**
- Detailed installation steps
- Troubleshooting guide
- Manual installation instructions
- Common error solutions

## Quick Start

### 1. Create a Resource

```bash
php artisan studio:make-resource UserResource
```

### 2. Define Your Resource

```php
<?php

namespace App\Resources;

use SavyApps\LaravelStudio\Resources\Resource;
use SavyApps\LaravelStudio\Resources\Fields\{ID, Text, Email, Password, Boolean, BelongsTo, Section};
use App\Models\User;

class UserResource extends Resource
{
    public static string $model = User::class;
    public static string $title = 'name';
    public static array $search = ['name', 'email'];

    public function indexFields(): array
    {
        return [
            ID::make()->sortable(),
            Text::make('Name')->sortable()->searchable(),
            Email::make('Email')->sortable()->searchable(),
            BelongsTo::make('Role')->searchable(),
            Boolean::make('Active')->toggleable(),
        ];
    }

    public function formFields(): array
    {
        return [
            Section::make('Basic Information')
                ->description('User account details')
                ->icon('user')
                ->fields([
                    Text::make('Name')
                        ->rules('required', 'max:255')
                        ->placeholder('Enter full name'),
                    Email::make('Email')
                        ->rules('required', 'email', 'unique:users,email')
                        ->placeholder('user@example.com'),
                    Password::make('Password')
                        ->requiredOnCreate()
                        ->creationRules('min:8'),
                ]),

            Section::make('Permissions')
                ->collapsible()
                ->fields([
                    BelongsTo::make('Role')
                        ->rules('required')
                        ->searchable(),
                    Boolean::make('Active')
                        ->default(true)
                        ->help('Inactive users cannot login'),
                ]),
        ];
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Role')->options(Role::pluck('name', 'id')),
            BooleanFilter::make('Active'),
        ];
    }

    public function actions(): array
    {
        return [
            BulkDeleteAction::make(),
            BulkUpdateAction::make()->fields(['active']),
            ExportAction::make()->formats(['csv', 'xlsx']),
        ];
    }
}
```

### 3. Register the Resource

In `config/studio.php`:

```php
'resources' => [
    'users' => \App\Resources\UserResource::class,
    'roles' => \App\Resources\RoleResource::class,
],
```

### 4. Use in Vue

```vue
<template>
    <ResourceManager resource="users" />
</template>

<script setup>
import { ResourceManager } from 'laravel-studio'
</script>
```

That's it! You now have a fully functional CRUD interface with:
- List view with search, filters, sorting, pagination
- Create/Edit forms with validation
- Delete with confirmation
- Bulk operations
- Relationship handling

---

## Field Types

Laravel Studio provides **21 field types** for building rich forms and data displays.

### Basic Fields

| Field | Description | Key Methods |
|-------|-------------|-------------|
| `ID` | Auto-incrementing ID | Hidden from forms |
| `Text` | Text input | `placeholder()`, `maxLength()`, `minLength()` |
| `Email` | Email input with validation | `placeholder()` |
| `Password` | Secure password field | `requiredOnCreate()`, `requiredOnUpdate()` |
| `Number` | Numeric input | `min()`, `max()`, `step()` |
| `Boolean` | Toggle/checkbox | `trueLabel()`, `falseLabel()`, `toggleable()` |
| `Date` | Date picker | `format()`, `min()`, `max()` |
| `Textarea` | Multi-line text | `rows()`, `maxLength()` |

### Advanced Fields

| Field | Description | Key Methods |
|-------|-------------|-------------|
| `Select` | Dropdown select | `options()`, `multiple()`, `serverSide()`, `searchable()` |
| `JSON` | JSON editor | `expectedType()`, `autoFormat()`, `showPreview()` |
| `Image` | Image display | `displayType()`, `width()`, `height()`, `rounded()` |
| `Media` | File uploads | `single()`, `multiple()`, `collection()`, `acceptedTypes()` |
| `IconPicker` | Icon selection | `icons()`, `columns()` |
| `TagInput` | Tag input | `suggestions()`, `maxTags()`, `allowNew()` |
| `MultiSelectServer` | Server-side multi-select | `endpoint()`, `searchable()`, `minChars()` |

### Relationship Fields

| Field | Description | Key Methods |
|-------|-------------|-------------|
| `BelongsTo` | Belongs-to relationship | `resource()`, `titleAttribute()`, `searchable()` |
| `BelongsToMany` | Many-to-many relationship | `resource()`, `titleAttribute()` |
| `HasMany` | Has-many relationship | `resource()` |

### Layout Fields

| Field | Description | Key Methods |
|-------|-------------|-------------|
| `Group` | Field grouping | `cols()`, `gap()` |
| `Section` | Collapsible sections | `description()`, `icon()`, `collapsible()`, `collapsed()` |

### Common Field Methods

All fields support these methods:

```php
Text::make('Name')
    ->sortable()              // Enable column sorting
    ->searchable()            // Include in search
    ->rules('required')       // Validation rules
    ->default('value')        // Default value
    ->nullable()              // Allow null
    ->placeholder('text')     // Placeholder text
    ->help('Help text')       // Help text below field
    ->cols(6)                 // Grid column span (1-12)
    ->creatable(false)        // Hide on create form
    ->dependsOn('field')      // Conditional visibility
    ->showWhen('field', 'value')
    ->hideWhen('field', 'value')
    ->requiredWhen('field', 'value')
    ->disabledWhen('field', 'value');
```

---

## Filter Types

| Filter | Description | Methods |
|--------|-------------|---------|
| `SelectFilter` | Dropdown filter | `options()`, `column()` |
| `BooleanFilter` | True/false filter | `column()`, `trueLabel()`, `falseLabel()` |
| `DateRangeFilter` | Date range filter | `column()` |
| `BelongsToManyFilter` | Relationship filter | `options()`, `relationship()` |

### Filter Examples

```php
use SavyApps\LaravelStudio\Resources\Filters\{SelectFilter, BooleanFilter, DateRangeFilter};

public function filters(): array
{
    return [
        // Enum-based options
        SelectFilter::make('Status')
            ->options(UserStatus::class),

        // Array options
        SelectFilter::make('Role')
            ->options([
                'admin' => 'Administrator',
                'user' => 'Regular User',
            ]),

        // Boolean filter
        BooleanFilter::make('Active')
            ->trueLabel('Active Users')
            ->falseLabel('Inactive Users'),

        // Date range
        DateRangeFilter::make('Created At')
            ->column('created_at'),
    ];
}
```

---

## Actions

| Action | Description | Methods |
|--------|-------------|---------|
| `BulkDeleteAction` | Delete multiple records | `confirmable()`, `danger()` |
| `BulkUpdateAction` | Update multiple records | `fields()` |
| `ExportAction` | Export to CSV/XLSX | `formats()` |

### Custom Actions

```bash
php artisan studio:make-action ActivateUsersAction
```

```php
use SavyApps\LaravelStudio\Resources\Actions\Action;

class ActivateUsersAction extends Action
{
    public static string $label = 'Activate Selected';

    public function __construct()
    {
        $this->confirmable()
             ->success(); // Green button style
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

Laravel Studio includes 6 backend services for handling core functionality:

| Service | Description |
|---------|-------------|
| `ResourceService` | CRUD operations, metadata handling, search, and query building |
| `PanelService` | Panel management, caching, menu building, and panel switching |
| `AuthorizationService` | Permission checking, gate registration, and authorization logic |
| `ActivityService` | Activity logging, change tracking, and cleanup operations |
| `GlobalSearchService` | Cross-resource search, indexing, and result caching |
| `CardService` | Dashboard card rendering, caching, and refresh logic |

### Using Services

Services are registered as singletons and can be resolved from the container:

```php
use SavyApps\LaravelStudio\Services\ResourceService;
use SavyApps\LaravelStudio\Services\ActivityService;

// Via dependency injection
public function __construct(
    protected ResourceService $resourceService,
    protected ActivityService $activityService
) {}

// Via app helper
$panelService = app(\SavyApps\LaravelStudio\Services\PanelService::class);
```

---

## Traits

Laravel Studio provides 5 reusable traits for extending your models and resources:

### Authorizable

Add authorization capabilities to resources:

```php
use SavyApps\LaravelStudio\Traits\Authorizable;

class UserResource extends Resource
{
    use Authorizable;

    public static function permissionGroup(): string
    {
        return 'users';
    }

    public static function permissions(): array
    {
        return [
            'viewAny' => 'View user list',
            'view' => 'View user details',
            'create' => 'Create users',
            'update' => 'Update users',
            'delete' => 'Delete users',
            'bulkDelete' => 'Bulk delete users',
        ];
    }
}
```

### LogsActivity

Track model changes with audit trail:

```php
use SavyApps\LaravelStudio\Traits\LogsActivity;

class User extends Model
{
    use LogsActivity;

    protected static function getLoggedAttributes(): array
    {
        return ['name', 'email', 'active'];
    }

    protected static function getIgnoreAttributeNames(): array
    {
        return ['password', 'remember_token'];
    }
}
```

### HasPermissions

Add permission checking to user models:

```php
use SavyApps\LaravelStudio\Traits\HasPermissions;

class User extends Authenticatable
{
    use HasPermissions;

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }
}
```

### GloballySearchable

Enable global search for resources:

```php
use SavyApps\LaravelStudio\Traits\GloballySearchable;

class UserResource extends Resource
{
    use GloballySearchable;

    public static function globallySearchable(): bool
    {
        return true;
    }

    public static function globallySearchableColumns(): array
    {
        return ['name', 'email'];
    }

    public static function globalSearchTitleColumn(): string
    {
        return 'name';
    }

    public static function globalSearchSubtitleColumn(): string
    {
        return 'email';
    }
}
```

### ApiResponse

Standard API response formatting for controllers:

```php
use SavyApps\LaravelStudio\Traits\ApiResponse;

class CustomController extends Controller
{
    use ApiResponse;

    public function index()
    {
        return $this->successResponse($data, 'Data retrieved successfully');
    }

    public function store(Request $request)
    {
        return $this->errorResponse('Validation failed', 422, $errors);
    }
}
```

---

## Middleware

Laravel Studio includes 2 middleware classes for access control:

| Middleware | Description |
|------------|-------------|
| `EnsureUserCanAccessPanel` | Validates user has required role for panel access |
| `CheckResourcePermission` | Validates permission for resource operation before execution |

These are automatically applied to routes but can be customized in config:

```php
// config/studio.php
'middleware' => ['api', 'auth:sanctum'],
```

---

## Models

The package includes 2 Eloquent models:

### Activity Model

Stores activity logs with full audit trail:

```php
// Fields: user_id, subject_type, subject_id, event, changes, ip_address, user_agent, timestamps

// Query recent activities
$activities = Activity::latest()->limit(10)->get();

// Activities for a specific model
$userActivities = Activity::forSubject($user)->get();
```

### Permission Model

Manages permissions with role relationships:

```php
// Fields: name, description, timestamps
// Relations: belongsToMany Role via role_permissions

$permission = Permission::where('name', 'users.create')->first();
$rolesWithPermission = $permission->roles;
```

---

## Dashboard Cards

Create dashboard widgets with 6 card types:

| Card Type | Description |
|-----------|-------------|
| `ValueCard` | Display single metric with optional icon |
| `TrendCard` | Show metric with trend line and percentage change |
| `ChartCard` | Interactive charts (Line, Bar, Area) |
| `PartitionCard` | Pie and donut charts |
| `TableCard` | Tabular data display |

### Card Examples

```php
use SavyApps\LaravelStudio\Cards\{ValueCard, TrendCard, ChartCard};

class UserResource extends Resource
{
    public function cards(): array
    {
        return [
            ValueCard::make('Total Users')
                ->value(User::count())
                ->icon('users')
                ->color('blue'),

            TrendCard::make('New Users')
                ->value(User::whereMonth('created_at', now()->month)->count())
                ->previousValue(User::whereMonth('created_at', now()->subMonth()->month)->count())
                ->format('number'),

            ChartCard::make('User Growth')
                ->type('line')
                ->data($this->getUserGrowthData()),
        ];
    }
}
```

### Card Colors

11 predefined colors: `blue`, `green`, `yellow`, `red`, `purple`, `pink`, `indigo`, `cyan`, `orange`, `teal`, `gray`

### Card Caching

Cards support caching for performance:

```php
// config/studio.php
'cards' => [
    'enabled' => true,
    'cache_ttl' => 300, // 5 minutes
    'refresh_interval' => 60,
    'max_per_row' => 4,
],
```

---

## Global Search

Enable cross-resource search with keyboard shortcuts:

```php
// config/studio.php
'global_search' => [
    'enabled' => true,
    'shortcut' => 'cmd+k', // or 'ctrl+k'
    'min_characters' => 2,
    'max_results' => 20,
    'debounce' => 300,
    'cache_ttl' => 60,
],
```

---

## Activity Logging

Configure activity logging in `config/studio.php`:

```php
'activity_log' => [
    'enabled' => true,
    'cleanup_days' => 90,
    'log_ip' => true,
    'log_user_agent' => true,
    'default_events' => ['created', 'updated', 'deleted'],
    'ignore_attributes' => ['password', 'remember_token', 'updated_at'],
],
```

Clean old logs:

```bash
php artisan studio:cleanup-activities
```

---

## Artisan Commands

Laravel Studio provides **10 Artisan commands**:

| Command | Description |
|---------|-------------|
| `studio:install` | Install Laravel Studio with starter files |
| `studio:make-resource {name}` | Create a new resource class |
| `studio:make-panel` | Create a new panel (interactive) |
| `studio:make-action {name}` | Create a custom action |
| `studio:make-filter {name}` | Create a custom filter |
| `studio:make-field {name}` | Create a custom field type |
| `studio:panel` | Panel management (interactive) |
| `studio:sync-permissions` | Sync permissions to database |
| `studio:cleanup-activities` | Clean old activity logs |
| `studio:doctor` | Run diagnostics and system health checks |

### Install Options

```bash
# Full installation with all features
php artisan studio:install --all

# Minimal installation
php artisan studio:install --minimal

# Skip specific steps
php artisan studio:install --skip-migrations --skip-seeders --skip-npm

# Dry run to preview changes
php artisan studio:install --dry-run

# Force overwrite existing files
php artisan studio:install --force
```

### Panel Creation

```bash
# Interactive mode (recommended)
php artisan studio:make-panel

# With panel key argument
php artisan studio:make-panel manager

# Non-interactive with options
php artisan studio:make-panel support \
    --label="Support Panel" \
    --path="/support" \
    --icon="inbox" \
    --role="support" \
    --default \
    -n

# Overwrite existing panel
php artisan studio:make-panel admin --force
```

**Panel Options:**

| Option | Description |
|--------|-------------|
| `key` | Panel key/slug (argument) |
| `--label` | Panel display name |
| `--path` | Frontend URL path (e.g., `/admin`) |
| `--icon` | Panel icon (25 icons available) |
| `--role` | Required role for access |
| `--default` | Set as default panel |
| `--inactive` | Create as inactive |
| `--force` | Overwrite existing panel |

### System Diagnostics

```bash
# Run health checks
php artisan studio:doctor
```

---

## API Endpoints

Laravel Studio auto-generates **30+ RESTful endpoints** organized by category:

### Resource Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/panels/{panel}/resources/{resource}/meta` | Resource metadata |
| `GET` | `/api/panels/{panel}/resources/{resource}` | List resources |
| `POST` | `/api/panels/{panel}/resources/{resource}` | Create resource |
| `GET` | `/api/panels/{panel}/resources/{resource}/{id}` | Get resource |
| `PUT` | `/api/panels/{panel}/resources/{resource}/{id}` | Update resource |
| `PATCH` | `/api/panels/{panel}/resources/{resource}/{id}` | Partial update |
| `DELETE` | `/api/panels/{panel}/resources/{resource}/{id}` | Delete resource |
| `POST` | `/api/panels/{panel}/resources/{resource}/bulk/delete` | Bulk delete |
| `POST` | `/api/panels/{panel}/resources/{resource}/bulk/update` | Bulk update |
| `POST` | `/api/panels/{panel}/resources/{resource}/actions/{action}` | Run action |
| `GET` | `/api/panels/{panel}/resources/{resource}/search` | Search related |

### Panel Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/panels/{panel}/info` | Public panel info |
| `GET` | `/api/panels/` | List user's panels |
| `GET` | `/api/panels/{panel}` | Panel details |
| `GET` | `/api/panels/{panel}/menu` | Panel menu structure |
| `GET` | `/api/panels/{panel}/resources` | Panel resources |
| `POST` | `/api/panels/{panel}/switch` | Switch active panel |

### Permission Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/permissions/` | All permissions |
| `GET` | `/api/permissions/my` | User's permissions |
| `POST` | `/api/permissions/check` | Check specific permission |
| `POST` | `/api/permissions/sync` | Sync permissions |
| `GET` | `/api/roles/{role}/permissions` | Role permissions |
| `PUT` | `/api/roles/{role}/permissions` | Update role permissions |

### Activity Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/activities/` | List activities |
| `GET` | `/api/activities/{id}` | Activity details |
| `GET` | `/api/activities/recent` | Recent activities |
| `GET` | `/api/activities/statistics` | Activity statistics |
| `GET` | `/api/activities/filter-options` | Filter options |
| `GET` | `/api/activities/my` | User's activities |
| `GET` | `/api/activities/subject/{type}/{id}` | Activities for subject |
| `DELETE` | `/api/activities/{id}` | Delete activity |
| `POST` | `/api/activities/bulk/delete` | Bulk delete |
| `POST` | `/api/activities/cleanup` | Cleanup old activities |

### Search Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/search/` | Global search |
| `GET` | `/api/search/suggestions` | Search suggestions |
| `GET` | `/api/search/resources` | Searchable resources |
| `GET` | `/api/search/{resource}` | Search single resource |
| `DELETE` | `/api/search/recent` | Clear recent searches |

### Card Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/cards/dashboard` | Dashboard cards |
| `GET` | `/api/cards/types` | Available card types |
| `GET` | `/api/cards/{resource}` | Resource cards |
| `GET` | `/api/cards/{resource}/{cardKey}` | Specific card |
| `POST` | `/api/cards/{resource}/{cardKey}/refresh` | Refresh card |
| `DELETE` | `/api/cards/{resource}/cache` | Clear card cache |
| `DELETE` | `/api/cards/cache` | Clear all caches |

---

## Vue Components

Laravel Studio provides **56 Vue components** (14,600+ lines of code):

### Resource Components
- `ResourceManager` - Main CRUD interface
- `ResourceTable` - Data table with sorting/filtering
- `ResourceForm` - Create/edit form
- `FieldRenderer` - Dynamic field rendering
- `FilterBar` - Filter controls
- `ActionButtons` - Bulk action buttons
- `QuickCreateModal` - Quick create dialog

### Form Components (18)
- `FormGroup`, `FormLabel`, `FormInput`, `FormError`, `FormSuccess`
- `FormSection`, `FormHelpText`, `FormActions`
- `TextareaInput`, `PasswordInput`, `SelectInput`, `CheckboxInput`, `RadioGroup`
- `DateInput`, `FileInput`, `VirtualSelectInput`
- `ResourceSelectInput`, `ServerSelectInput`, `MultiSelectServer`
- `TagInput`, `IconPicker`, `JsonEditor`, `MediaUpload`

### Card Components (6)
- `CardGrid`, `ValueCard`, `TrendCard`, `ChartCard`, `PartitionCard`, `TableCard`

### Common Components (11)
- `Icon`, `ImageWithBlurPlaceholder`, `ImageEditor`, `ImageLightbox`
- `ToggleSwitch`, `ConfirmDialog`, `ConfirmDialogContainer`
- `Toast`, `ToastContainer`, `VirtualScroll`

### Layout Components
- `PanelLayout`, `PanelSwitcher`

### Activity Components
- `ActivityItem`, `ActivityTimeline`, `ActivityDiff`, `ActivityDetailsModal`

### Permission Components
- `PermissionGuard` - Conditional rendering based on permissions
- `RolePermissionMatrix` - Role management UI

---

## Vue Services

Frontend API services in `resources/js/services/`:

```javascript
import {
    resourceService,
    panelService,
    authService,
    activityService,
    searchService,
    permissionService,
    cardService,
    mediaService,
    settingsService,
    impersonationService
} from 'laravel-studio'

// Resource operations
await resourceService.getIndex('users', { page: 1, search: 'john' })
await resourceService.create('users', formData)
await resourceService.update('users', id, formData)
await resourceService.delete('users', id)
await resourceService.bulkDelete('users', ids)

// Global search
await searchService.globalSearch('query')

// Permissions
const canCreate = await permissionService.checkPermission('users.create')

// Media uploads
await mediaService.upload(file, { collection: 'avatars' })
```

---

## Vue Composables

11 composable hooks for common functionality:

```javascript
import {
    useToast,
    useDialog,
    useModal,
    usePermissions,
    useGlobalSearch,
    useLightbox,
    useCards,
    useImageEditor,
    useImageEditHistory,
    usePasswordToggle,
    useUploadProgress
} from 'laravel-studio'

// Toast notifications
const { success, error, info, warning } = useToast()
success('User created successfully')

// Confirmation dialogs
const { confirm, alert } = useDialog()
if (await confirm('Delete this user?')) {
    // proceed
}

// Permission checking
const { can, canAny, canAll } = usePermissions()
if (can('users.create')) {
    // show create button
}

// Global search
const { query, results, search, isSearching } = useGlobalSearch()

// Image lightbox
const { open, close, isOpen } = useLightbox()
open(imageUrl)

// Upload progress tracking
const { progress, isUploading, start, complete } = useUploadProgress()
```

---

## Pinia Stores

5 Pinia stores for state management:

| Store | Description |
|-------|-------------|
| `auth` | Authentication state (user, token, isAuthenticated) |
| `panel` | Active panel state and switching |
| `dialog` | Dialog/modal state management |
| `settings` | User and application settings |
| `toast` | Toast notification queue |

```javascript
import { useAuthStore, usePanelStore, useToastStore } from 'laravel-studio'

const authStore = useAuthStore()
const panelStore = usePanelStore()
const toastStore = useToastStore()

// Check authentication
if (authStore.isAuthenticated) {
    console.log(authStore.user.name)
}

// Switch panels
await panelStore.switchPanel('admin')

// Add toast
toastStore.add({ type: 'success', message: 'Saved!' })
```

---

## Utility Modules

9 utility modules in `resources/js/utils/`:

| Module | Description |
|--------|-------------|
| `debouncedValidation` | Validation with debouncing |
| `httpErrorHandler` | HTTP error handling and formatting |
| `imageManipulation` | Image processing utilities |
| `laravelErrorMapper` | Map Laravel errors to form fields |
| `lazyValidation` | Lazy/on-demand validation |
| `memoization` | Result caching utilities |
| `validationMessages` | Custom validation message templates |
| `validationRules` | Validation rule definitions |
| `validationSchemas` | Reusable validation schemas |

---

## Database Migrations

The package includes 5 migrations:

1. **`create_roles_table`** - Roles with name, timestamps
2. **`create_permissions_table`** - Permissions with name, description
3. **`create_role_permissions_table`** - Pivot table for role-permission relationships
4. **`create_activities_table`** - Activity logging with all audit fields
5. **`add_performance_indexes`** - Indexes for query optimization

Run migrations:

```bash
php artisan migrate
```

---

## Starters

The package includes a default starter template in `/starters/default/`:

### Backend Starter
- Complete Laravel 12 application structure
- Example resources: `UserResource`, `RoleResource`, `PanelResource`
- Example models: User, Role, Setting, SettingList, EmailTemplate
- Service layer examples
- Full directory structure

### Frontend Starter
- Vue 3 SPA with Vite
- Pages: auth, admin, settings, profile
- Complete component library
- Services, stores, composables
- Theme configuration
- Router setup

---

## Testing

### Running Package Tests

```bash
cd packages/laravel-studio
./vendor/bin/phpunit
```

### Test Categories

- **Feature Tests**: Integration tests for controllers and API endpoints
- **Unit Tests**: Tests for fields, resources, services, and traits

### Test Database

Uses SQLite in-memory database (configured in `phpunit.xml`)

---

## Configuration

All options in `config/studio.php`:

```php
return [
    // Resource registry
    'resources' => [
        'users' => \App\Resources\UserResource::class,
    ],

    // Panel configuration
    'panels' => [
        'admin' => [
            'name' => 'Admin Panel',
            'resources' => ['users', 'roles'],
            'middleware' => ['auth:sanctum'],
        ],
    ],

    // Route configuration
    'prefix' => 'api/studio',
    'name_prefix' => 'studio.',

    // Middleware
    'middleware' => ['api', 'auth:sanctum'],

    // Bulk operations
    'bulk' => [
        'max_ids' => 1000,
        'chunk_size' => 100,
    ],

    // Authorization
    'authorization' => [
        'enabled' => true,
        'super_admin_role' => 'super-admin',
        'cache' => [
            'enabled' => true,
            'ttl' => 3600,
        ],
        'gate_registration' => true,
    ],

    // Activity logging
    'activity_log' => [
        'enabled' => true,
        'cleanup_days' => 90,
        'log_ip' => true,
        'log_user_agent' => true,
        'default_events' => ['created', 'updated', 'deleted'],
        'ignore_attributes' => ['password', 'remember_token', 'updated_at'],
    ],

    // Global search
    'global_search' => [
        'enabled' => true,
        'shortcut' => 'cmd+k',
        'min_characters' => 2,
        'max_results' => 20,
        'debounce' => 300,
        'cache_ttl' => 60,
    ],

    // Dashboard cards
    'cards' => [
        'enabled' => true,
        'cache_ttl' => 300,
        'max_per_row' => 4,
        'colors' => ['blue', 'green', 'yellow', 'red', 'purple', 'pink', 'indigo', 'cyan', 'orange', 'teal', 'gray'],
    ],
];
```

---

## Roadmap

- [x] Resource-based CRUD system
- [x] 21 field types with conditional visibility
- [x] 5 filter types with enum support
- [x] Bulk actions (delete, update, export)
- [x] Permission and authorization system
- [x] Activity logging with audit trail
- [x] Global search across resources
- [x] Dashboard cards and widgets
- [x] Panel management
- [x] Media library integration
- [x] Image editing capabilities
- [ ] Chart integration enhancements
- [ ] Import functionality
- [ ] Real-time notifications
- [ ] API documentation generator
- [ ] Multi-tenancy support

---

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

---

## Credits

- **SavyApps** - [https://savyapps.com](https://savyapps.com)
- Built with love by the SavyApps team

---

## License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

---

## Alternatives

- [Laravel Nova](https://nova.laravel.com) - Official Laravel admin panel (paid)
- [Filament](https://filamentphp.com) - Beautiful admin panel with Livewire
- [Laravel Backpack](https://backpackforlaravel.com) - Admin panel builder

---

**Laravel Studio** is proudly developed by [SavyApps](https://savyapps.com)
